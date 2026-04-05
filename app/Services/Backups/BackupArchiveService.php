<?php

namespace App\Services\Backups;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use ZipArchive;

class BackupArchiveService
{
    public function create(): array
    {
        $timestamp = now()->format('Ymd_His');
        $archiveDirectory = Storage::disk(config('backups.archive_disk'))->path(config('backups.archive_directory'));
        $workingDirectory = $archiveDirectory . '/tmp/' . Str::uuid();

        File::ensureDirectoryExists($archiveDirectory);
        File::ensureDirectoryExists($workingDirectory);

        $archiveName = "sidainfo-backup-{$timestamp}.zip";
        $archivePath = $archiveDirectory . '/' . $archiveName;
        $manifestPath = $workingDirectory . '/manifest.json';

        $databasePath = $this->exportDatabase($workingDirectory);

        file_put_contents($manifestPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'app_url' => config('app.url'),
            'database_connection' => config('database.default'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $zip = new ZipArchive();
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            File::deleteDirectory($workingDirectory);

            throw new RuntimeException('Unable to create backup archive.');
        }

        $zip->addFile($manifestPath, 'manifest.json');
        $zip->addFile($databasePath, 'database/' . basename($databasePath));

        $releaseDirectory = Storage::disk(config('filesystems.default', 'local'))->path('releases');
        if (is_dir($releaseDirectory)) {
            $this->addDirectoryToZip($zip, $releaseDirectory, 'releases');
        }

        $zip->close();
        File::deleteDirectory($workingDirectory);

        return [
            'path' => $archivePath,
            'filename' => $archiveName,
            'size_bytes' => filesize($archivePath) ?: 0,
        ];
    }

    private function exportDatabase(string $workingDirectory): string
    {
        return match (config('database.default')) {
            'mysql' => $this->dumpMysql($workingDirectory),
            'sqlite' => $this->copySqlite($workingDirectory),
            default => throw new RuntimeException('Backup only supports mysql and sqlite connections.'),
        };
    }

    private function dumpMysql(string $workingDirectory): string
    {
        $connection = config('database.connections.mysql');
        $dumpPath = $workingDirectory . '/database.sql';
        $stderr = '';

        $handle = fopen($dumpPath, 'wb');
        if (! $handle) {
            throw new RuntimeException('Unable to create the database dump file.');
        }

        $process = new Process([
            'mysqldump',
            '--single-transaction',
            '--quick',
            '--host=' . ($connection['host'] ?? '127.0.0.1'),
            '--port=' . ($connection['port'] ?? 3306),
            '--user=' . ($connection['username'] ?? ''),
            $connection['database'] ?? '',
        ]);

        $process->setTimeout((int) config('backups.dump_timeout_seconds', 3600));
        $process->setEnv([
            'MYSQL_PWD' => $connection['password'] ?? '',
        ]);

        $process->run(function (string $type, string $buffer) use ($handle, &$stderr): void {
            if ($type === Process::OUT) {
                fwrite($handle, $buffer);
                return;
            }

            $stderr .= $buffer;
        });

        fclose($handle);

        if (! $process->isSuccessful()) {
            @unlink($dumpPath);

            throw new RuntimeException('Database dump failed: ' . trim($stderr ?: $process->getErrorOutput()));
        }

        return $dumpPath;
    }

    private function copySqlite(string $workingDirectory): string
    {
        $database = config('database.connections.sqlite.database');
        $copyPath = $workingDirectory . '/database.sqlite';

        if (! $database || $database === ':memory:' || ! is_file($database)) {
            $fallbackPath = $workingDirectory . '/database.txt';

            file_put_contents($fallbackPath, "SQLite backup metadata\nGenerated: " . now()->toIso8601String());

            return $fallbackPath;
        }

        copy($database, $copyPath);

        return $copyPath;
    }

    private function addDirectoryToZip(ZipArchive $zip, string $source, string $prefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = trim($prefix . '/' . ltrim(str_replace($source, '', $item->getPathname()), DIRECTORY_SEPARATOR), '/');

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
                continue;
            }

            $zip->addFile($item->getPathname(), $relativePath);
        }
    }
}
