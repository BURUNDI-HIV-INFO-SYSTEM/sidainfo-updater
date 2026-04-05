<?php

namespace App\Services\Backups;

use App\Models\BackupDestination;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OneDriveBackupSender
{
    public function send(BackupDestination $destination, array $archive): array
    {
        $config = $destination->config;
        $tenantId = $config['tenant_id'] ?: 'common';

        $tokenResponse = Http::asForm()
            ->timeout(60)
            ->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $config['client_id'],
                'client_secret' => $config['client_secret'],
                'refresh_token' => $config['refresh_token'],
                'grant_type' => 'refresh_token',
                'scope' => 'offline_access Files.ReadWrite User.Read',
            ]);

        if (! $tokenResponse->successful()) {
            throw new RuntimeException('OneDrive token refresh failed: ' . $tokenResponse->body());
        }

        $accessToken = $tokenResponse->json('access_token');
        if (! $accessToken) {
            throw new RuntimeException('OneDrive token refresh did not return an access token.');
        }

        $remotePath = $this->remotePath($config['folder_path'] ?? '', $archive['filename']);
        $encodedPath = collect(explode('/', $remotePath))
            ->filter()
            ->map(fn (string $segment) => rawurlencode($segment))
            ->implode('/');

        $sessionResponse = Http::withToken($accessToken)
            ->acceptJson()
            ->post("https://graph.microsoft.com/v1.0/me/drive/root:/{$encodedPath}:/createUploadSession", [
                'item' => [
                    '@microsoft.graph.conflictBehavior' => 'replace',
                ],
            ]);

        if (! $sessionResponse->successful()) {
            throw new RuntimeException('Unable to create OneDrive upload session: ' . $sessionResponse->body());
        }

        $uploadUrl = $sessionResponse->json('uploadUrl');
        if (! $uploadUrl) {
            throw new RuntimeException('OneDrive upload session did not return an upload URL.');
        }

        $this->uploadInChunks($uploadUrl, $archive['path']);

        return [
            'message' => 'Backup uploaded to OneDrive' . ($config['folder_path'] ? " ({$config['folder_path']})" : '') . '.',
            'config' => array_merge($config, [
                'refresh_token' => $tokenResponse->json('refresh_token') ?: $config['refresh_token'],
            ]),
        ];
    }

    private function uploadInChunks(string $uploadUrl, string $archivePath): void
    {
        $handle = fopen($archivePath, 'rb');
        if (! $handle) {
            throw new RuntimeException('Unable to open the backup archive for upload.');
        }

        $chunkSize = (int) config('backups.onedrive_chunk_size', 5 * 1024 * 1024);
        $fileSize = filesize($archivePath) ?: 0;
        $start = 0;

        while (! feof($handle)) {
            $chunk = fread($handle, $chunkSize);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $length = strlen($chunk);
            $end = $start + $length - 1;

            $response = Http::withHeaders([
                'Content-Length' => $length,
                'Content-Range' => "bytes {$start}-{$end}/{$fileSize}",
            ])->withBody($chunk, 'application/octet-stream')
                ->put($uploadUrl);

            if (! in_array($response->status(), [200, 201, 202], true)) {
                fclose($handle);

                throw new RuntimeException('OneDrive upload failed: ' . $response->body());
            }

            $start = $end + 1;
        }

        fclose($handle);
    }

    private function remotePath(string $folderPath, string $filename): string
    {
        $folderPath = trim($folderPath, '/');

        return $folderPath ? "{$folderPath}/{$filename}" : $filename;
    }
}
