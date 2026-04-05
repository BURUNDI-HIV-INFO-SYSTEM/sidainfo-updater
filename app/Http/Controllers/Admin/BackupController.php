<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BackupDestination;
use App\Services\Backups\BackupManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class BackupController extends Controller
{
    public function index()
    {
        $destinations = BackupDestination::orderBy('name')->get();

        return view('backups.index', compact('destinations'));
    }

    public function create()
    {
        return view('backups.create');
    }

    public function store(Request $request)
    {
        $payload = $this->validateDestination($request);

        BackupDestination::create($payload);

        return redirect()->route('backups.index')
            ->with('success', 'Backup destination created successfully.');
    }

    public function edit(BackupDestination $backupDestination)
    {
        return view('backups.edit', ['destination' => $backupDestination]);
    }

    public function update(Request $request, BackupDestination $backupDestination)
    {
        $payload = $this->validateDestination($request, $backupDestination);

        $backupDestination->update($payload);

        return redirect()->route('backups.edit', $backupDestination)
            ->with('success', 'Backup destination updated successfully.');
    }

    public function run(BackupDestination $backupDestination, BackupManager $backupManager)
    {
        try {
            $result = $backupManager->run($backupDestination);

            $backupDestination->update([
                'config' => $result['config'] ?? $backupDestination->config,
                'last_run_at' => now(),
                'last_backup_filename' => $result['filename'],
                'last_backup_size_bytes' => $result['size_bytes'],
                'last_status' => 'success',
                'last_message' => $result['message'],
            ]);

            return redirect()->route('backups.index')
                ->with('success', $result['message']);
        } catch (\Throwable $exception) {
            $backupDestination->update([
                'last_run_at' => now(),
                'last_status' => 'failed',
                'last_message' => $exception->getMessage(),
            ]);

            report($exception);

            return redirect()->route('backups.index')
                ->with('error', 'Backup failed: ' . $exception->getMessage());
        }
    }

    private function validateDestination(Request $request, ?BackupDestination $destination = null): array
    {
        $baseData = $request->validate([
            'name' => 'required|string|max:255',
            'driver' => ['required', Rule::in(['gmail', 'onedrive'])],
            'is_active' => 'nullable|boolean',
        ]);

        if ($baseData['driver'] === 'gmail') {
            $gmail = $request->validate([
                'recipient_email' => 'required|email|max:255',
            ]);

            return [
                'name' => $baseData['name'],
                'driver' => 'gmail',
                'is_active' => $request->boolean('is_active', true),
                'config' => [
                    'recipient_email' => $gmail['recipient_email'],
                ],
            ];
        }

        $validator = Validator::make($request->all(), [
            'tenant_id' => 'nullable|string|max:255',
            'client_id' => [$destination ? 'nullable' : 'required', 'string', 'max:255'],
            'client_secret' => [$destination ? 'nullable' : 'required', 'string'],
            'refresh_token' => [$destination ? 'nullable' : 'required', 'string'],
            'folder_path' => 'nullable|string|max:255',
        ]);

        $validator->after(function ($validator) use ($destination, $request) {
            if ($destination?->driver === 'onedrive') {
                $config = $destination->config ?? [];

                foreach (['client_id', 'client_secret', 'refresh_token'] as $field) {
                    if (blank($request->input($field)) && blank($config[$field] ?? null)) {
                        $validator->errors()->add($field, ucfirst(str_replace('_', ' ', $field)) . ' is required.');
                    }
                }
            }
        });

        $onedrive = $validator->validate();
        $currentConfig = $destination?->driver === 'onedrive' ? ($destination->config ?? []) : [];

        return [
            'name' => $baseData['name'],
            'driver' => 'onedrive',
            'is_active' => $request->boolean('is_active', true),
            'config' => [
                'tenant_id' => $onedrive['tenant_id'] ?: 'common',
                'client_id' => $onedrive['client_id'] ?: ($currentConfig['client_id'] ?? null),
                'client_secret' => $onedrive['client_secret'] ?: ($currentConfig['client_secret'] ?? null),
                'refresh_token' => $onedrive['refresh_token'] ?: ($currentConfig['refresh_token'] ?? null),
                'folder_path' => trim((string) ($onedrive['folder_path'] ?? ''), '/'),
            ],
        ];
    }
}
