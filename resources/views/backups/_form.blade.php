@php
    $destination = $destination ?? null;
    $driver = old('driver', $destination->driver ?? 'gmail');
    $config = $destination->config ?? [];
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="name">Destination Name</label>
            <input id="name" name="name" type="text" required value="{{ old('name', $destination->name ?? '') }}"
                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="driver">Driver</label>
            <select id="driver" name="driver"
                    class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    onchange="window.toggleBackupDriverFields?.(this.value)">
                <option value="gmail" @selected($driver === 'gmail')>Gmail</option>
                <option value="onedrive" @selected($driver === 'onedrive')>OneDrive</option>
            </select>
            @error('driver')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
        <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300"
               @checked(old('is_active', $destination->is_active ?? true))>
        Destination is active
    </label>

    <div data-backup-driver="gmail" class="{{ $driver === 'gmail' ? '' : 'hidden' }} rounded-xl border border-slate-200 p-5 bg-slate-50">
        <h3 class="font-semibold text-slate-800">Gmail Delivery</h3>
        <p class="text-sm text-slate-500 mt-1">
            Uses the app mailer. Configure Gmail SMTP in the environment, then enter the Gmail recipient below.
        </p>

        <div class="mt-4">
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="recipient_email">Recipient Gmail Address</label>
            <input id="recipient_email" name="recipient_email" type="email" value="{{ old('recipient_email', $config['recipient_email'] ?? '') }}"
                   class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('recipient_email')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <p class="mt-3 text-xs text-amber-700">
            Gmail delivery is best for smaller archives. Large backups should use OneDrive.
        </p>
    </div>

    <div data-backup-driver="onedrive" class="{{ $driver === 'onedrive' ? '' : 'hidden' }} rounded-xl border border-slate-200 p-5 bg-slate-50">
        <h3 class="font-semibold text-slate-800">OneDrive Delivery</h3>
        <p class="text-sm text-slate-500 mt-1">
            Provide Microsoft Graph delegated credentials for a OneDrive account with write access.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="tenant_id">Tenant ID</label>
                <input id="tenant_id" name="tenant_id" type="text" value="{{ old('tenant_id', $config['tenant_id'] ?? 'common') }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('tenant_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="folder_path">Folder Path</label>
                <input id="folder_path" name="folder_path" type="text" value="{{ old('folder_path', $config['folder_path'] ?? '') }}"
                       placeholder="Backups/SIDAInfo"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('folder_path')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mt-5">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="client_id">Client ID</label>
                <input id="client_id" name="client_id" type="text" value="{{ old('client_id', $config['client_id'] ?? '') }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('client_id')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="client_secret">Client Secret</label>
                <input id="client_secret" name="client_secret" type="password"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @if($destination)
                    <p class="mt-1 text-xs text-slate-400">Leave blank to keep the existing secret.</p>
                @endif
                @error('client_secret')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="mt-5">
            <label class="block text-sm font-medium text-slate-700 mb-1.5" for="refresh_token">Refresh Token</label>
            <textarea id="refresh_token" name="refresh_token" rows="4"
                      class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('refresh_token') }}</textarea>
            @if($destination)
                <p class="mt-1 text-xs text-slate-400">Leave blank to keep the existing refresh token.</p>
            @endif
            @error('refresh_token')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>

<script>
    window.toggleBackupDriverFields = function (driver) {
        document.querySelectorAll('[data-backup-driver]').forEach(function (element) {
            element.classList.toggle('hidden', element.dataset.backupDriver !== driver);
        });
    };

    window.toggleBackupDriverFields(@json($driver));
</script>
