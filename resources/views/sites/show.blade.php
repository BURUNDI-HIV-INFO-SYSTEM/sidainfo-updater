@extends('layouts.app')

@section('title', $site->site_name.' – SIDAInfo Update Server')
@section('heading', $site->site_name)

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Site info card --}}
    <div class="xl:col-span-1 bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-700 mb-4">Site Details</h2>
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-slate-500">Site ID</dt>
                <dd class="font-mono text-slate-700">{{ $site->siteid }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Province</dt>
                <dd class="text-slate-700">{{ $site->province ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">District</dt>
                <dd class="text-slate-700">{{ $site->district ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Active</dt>
                <dd>
                    @if($site->active)
                        <span class="text-green-700 font-medium">Yes</span>
                    @else
                        <span class="text-slate-400">No</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-5 pt-5 border-t border-slate-100 space-y-3 text-sm">
            <div class="flex justify-between">
                <dt class="text-slate-500">Current Version</dt>
                <dd class="font-semibold text-blue-700">
                    {{ $site->current_version ? 'v'.$site->current_version : '—' }}
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Last Status</dt>
                <dd>
                    @php
                        $sc = ['installed' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800',
                               'checked' => 'bg-blue-100 text-blue-800'];
                        $c = $sc[$site->last_status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    @if($site->last_status)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $c }}">
                            {{ $site->last_status }}
                        </span>
                    @else
                        <span class="text-slate-300 text-xs">never</span>
                    @endif
                </dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Last Checked</dt>
                <dd class="text-slate-600 text-xs">{{ $site->last_checked_at?->format('d M Y H:i') ?? '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-slate-500">Last Installed</dt>
                <dd class="text-slate-600 text-xs">{{ $site->last_installed_at?->format('d M Y H:i') ?? '—' }}</dd>
            </div>
        </div>
    </div>

    {{-- Event timeline --}}
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-semibold text-slate-700">Update Timeline</h2>
            <span class="text-slate-400 text-sm">{{ $events->total() }} events</span>
        </div>

        @if($events->isEmpty())
            <div class="px-6 py-16 text-center text-slate-400 text-sm">
                This site has not reported any events yet.
            </div>
        @else
            <div class="divide-y divide-slate-50">
                @foreach($events as $event)
                @php
                    $dotColor = ['installed' => 'bg-green-500', 'failed' => 'bg-red-500',
                                 'checked' => 'bg-blue-400', 'unknown' => 'bg-gray-300'];
                    $dc = $dotColor[$event->status] ?? 'bg-gray-300';
                    $sc = ['installed' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800',
                           'checked' => 'bg-blue-100 text-blue-800', 'unknown' => 'bg-gray-100 text-gray-500'];
                    $statusClass = $sc[$event->status] ?? 'bg-gray-100 text-gray-500';
                @endphp
                <div class="px-6 py-4 flex items-start gap-4">
                    <div class="mt-1.5 flex-shrink-0">
                        <span class="w-2.5 h-2.5 rounded-full {{ $dc }} block"></span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $statusClass }}">
                                {{ $event->status }}
                            </span>
                            <span class="text-xs text-slate-400 capitalize">{{ str_replace('_', ' ', $event->event_type) }}</span>
                            @if($event->target_version)
                                <span class="text-sm text-slate-700 font-medium">→ v{{ $event->target_version }}</span>
                            @endif
                            @if($event->current_version && $event->current_version !== $event->target_version)
                                <span class="text-xs text-slate-400">from v{{ $event->current_version }}</span>
                            @endif
                        </div>
                        @if($event->message)
                            <p class="mt-1 text-sm text-slate-600">{{ $event->message }}</p>
                        @endif
                        <div class="mt-1 flex items-center gap-3 text-xs text-slate-400">
                            <span>{{ $event->created_at->format('d M Y H:i:s') }}</span>
                            @if($event->source_ip)
                                <span>IP: {{ $event->source_ip }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            @if($events->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $events->links() }}
            </div>
            @endif
        @endif
    </div>
</div>

<div class="flex">
    <a href="{{ route('sites.index') }}"
       class="text-sm text-slate-500 hover:text-slate-700 flex items-center gap-1">
        ← Back to sites
    </a>
</div>

@endsection
