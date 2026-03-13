@extends('layouts.app')

@section('title', 'Dashboard – SIDAInfo Update Server')
@section('heading', 'Dashboard')

@section('content')

{{-- Stats cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">

    @php
        $cards = [
            ['label' => 'Total Sites',         'value' => $totalSites,      'color' => 'blue'],
            ['label' => 'On Latest Version',   'value' => $onLatestVersion, 'color' => 'green'],
            ['label' => 'Failed Last Update',  'value' => $failedSites,     'color' => 'red'],
            ['label' => 'Never Reported',      'value' => $neverReported,   'color' => 'amber'],
        ];
        $colorMap = [
            'blue'  => 'bg-blue-50 text-blue-700 border-blue-100',
            'green' => 'bg-green-50 text-green-700 border-green-100',
            'red'   => 'bg-red-50 text-red-700 border-red-100',
            'amber' => 'bg-amber-50 text-amber-700 border-amber-100',
        ];
    @endphp

    @foreach($cards as $card)
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="text-sm font-medium text-slate-500 mb-2">{{ $card['label'] }}</div>
        <div class="text-3xl font-bold {{ explode(' ', $colorMap[$card['color']])[1] }}">
            {{ $card['value'] }}
        </div>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

    {{-- Active release card --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 xl:col-span-1">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Active Release</h2>
        @if($latestRelease)
            <div class="text-2xl font-bold text-blue-700 mb-1">v{{ $latestRelease->version }}</div>
            <div class="text-xs text-slate-400 mb-4">
                Published {{ $latestRelease->published_at ? $latestRelease->published_at->format('d M Y') : '—' }}
            </div>
            @if($latestRelease->notes)
                <p class="text-sm text-slate-600 mb-4">{{ Str::limit($latestRelease->notes, 120) }}</p>
            @endif
            <div class="flex items-center gap-4 text-sm text-slate-500">
                <span>{{ $latestRelease->formattedSize() }}</span>
                @if($latestRelease->sha256)
                    <span class="font-mono text-xs truncate max-w-24" title="{{ $latestRelease->sha256 }}">
                        {{ substr($latestRelease->sha256, 0, 8) }}…
                    </span>
                @endif
            </div>
            <div class="mt-4">
                <a href="{{ route('releases.show', $latestRelease) }}"
                   class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    View adoption →
                </a>
            </div>
        @else
            <p class="text-slate-400 text-sm">No active release yet.</p>
            <a href="{{ route('releases.create') }}"
               class="mt-4 inline-block text-blue-600 hover:text-blue-800 text-sm font-medium">
                Upload first release →
            </a>
        @endif
    </div>

    {{-- Version adoption --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 xl:col-span-2">
        <h2 class="text-sm font-semibold text-slate-500 uppercase tracking-wide mb-4">Version Adoption</h2>
        @if($adoption->isEmpty())
            <p class="text-slate-400 text-sm">No version data yet.</p>
        @else
            @php $maxCount = $adoption->max('site_count'); @endphp
            <div class="space-y-3">
                @foreach($adoption as $row)
                @php $pct = $maxCount > 0 ? round(($row->site_count / $totalSites) * 100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="font-medium text-slate-700">v{{ $row->current_version }}</span>
                        <span class="text-slate-500">{{ $row->site_count }} sites ({{ $pct }}%)</span>
                    </div>
                    <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Recent events --}}
<div class="mt-6 bg-white rounded-xl border border-slate-200">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-semibold text-slate-800">Recent Events</h2>
        <a href="{{ route('sites.index') }}" class="text-sm text-blue-600 hover:text-blue-800">All sites →</a>
    </div>

    @if($recentEvents->isEmpty())
        <div class="px-6 py-10 text-center text-slate-400 text-sm">No events recorded yet.</div>
    @else
        <div class="divide-y divide-slate-50">
            @foreach($recentEvents as $event)
            @php
                $colors = ['installed' => 'bg-green-100 text-green-800', 'failed' => 'bg-red-100 text-red-800',
                           'checked' => 'bg-blue-100 text-blue-800', 'unknown' => 'bg-gray-100 text-gray-600'];
                $color = $colors[$event->status] ?? 'bg-gray-100 text-gray-600';
            @endphp
            <div class="px-6 py-3 flex items-center gap-4 text-sm hover:bg-slate-50">
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $color }}">
                    {{ $event->status }}
                </span>
                <span class="font-medium text-slate-700 min-w-0">
                    <a href="{{ route('sites.show', $event->siteid) }}" class="hover:text-blue-600">
                        {{ $event->site?->site_name ?? $event->siteid }}
                    </a>
                </span>
                @if($event->target_version)
                    <span class="text-slate-400">→ v{{ $event->target_version }}</span>
                @endif
                @if($event->message)
                    <span class="text-slate-400 truncate flex-1">{{ Str::limit($event->message, 60) }}</span>
                @endif
                <span class="text-slate-400 ml-auto text-xs whitespace-nowrap">
                    {{ $event->created_at->diffForHumans() }}
                </span>
            </div>
            @endforeach
        </div>
    @endif
</div>

@endsection
