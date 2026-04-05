@extends('layouts.app')

@section('title', 'Backups – SIDAInfo Update Server')
@section('heading', 'Backups')

@section('header-actions')
    <a href="{{ route('backups.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Destination
    </a>
@endsection

@section('content')

<div class="bg-amber-50 border border-amber-200 rounded-xl p-5 mb-6 text-sm text-amber-950">
    <p class="font-semibold">Backup scope</p>
    <p class="mt-1">
        Each backup contains the database plus uploaded release files. Gmail delivery uses the configured application mailer and is limited to small archives. Use OneDrive for larger backups.
    </p>
</div>

@if($destinations->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
        <p class="text-slate-500">No backup destinations configured yet.</p>
        <a href="{{ route('backups.create') }}"
           class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
            Create first destination
        </a>
    </div>
@else
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        @foreach($destinations as $destination)
            <div class="bg-white rounded-xl border border-slate-200 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-lg font-semibold text-slate-800">{{ $destination->name }}</h2>
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $destination->is_active ? 'bg-green-100 text-green-800' : 'bg-slate-100 text-slate-500' }}">
                                {{ $destination->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mt-1">{{ $destination->driverLabel() }}</p>
                    </div>
                    <a href="{{ route('backups.edit', $destination) }}"
                       class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Edit
                    </a>
                </div>

                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Last run</dt>
                        <dd class="text-slate-700">{{ $destination->last_run_at?->format('d M Y H:i') ?? 'Never' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Last status</dt>
                        <dd class="text-slate-700">{{ $destination->last_status ? ucfirst($destination->last_status) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-500">Last archive</dt>
                        <dd class="text-slate-700">
                            {{ $destination->last_backup_filename ?? '—' }}
                            @if($destination->formattedBackupSize())
                                <span class="text-slate-400">({{ $destination->formattedBackupSize() }})</span>
                            @endif
                        </dd>
                    </div>
                </dl>

                @if($destination->last_message)
                    <div class="mt-4 rounded-lg {{ $destination->last_status === 'failed' ? 'bg-red-50 border border-red-200 text-red-700' : 'bg-slate-50 border border-slate-200 text-slate-600' }} px-4 py-3 text-sm">
                        {{ $destination->last_message }}
                    </div>
                @endif

                <form method="POST" action="{{ route('backups.run', $destination) }}" class="mt-5">
                    @csrf
                    <button type="submit"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                        Run Backup Now
                    </button>
                </form>
            </div>
        @endforeach
    </div>
@endif

@endsection
