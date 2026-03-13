@extends('layouts.app')

@section('title', 'Sites – SIDAInfo Update Server')
@section('heading', 'Sites')

@section('content')

{{-- Filters --}}
<form method="GET" action="{{ route('sites.index') }}"
      class="bg-white rounded-xl border border-slate-200 p-4 mb-5 flex flex-wrap gap-3 items-end">

    <div class="flex-1 min-w-40">
        <label class="block text-xs font-medium text-slate-500 mb-1">Search</label>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="ID or name…"
               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="min-w-36">
        <label class="block text-xs font-medium text-slate-500 mb-1">Province</label>
        <select name="province"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="">All provinces</option>
            @foreach($provinces as $province)
                <option value="{{ $province }}" {{ request('province') == $province ? 'selected' : '' }}>
                    {{ $province }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="min-w-36">
        <label class="block text-xs font-medium text-slate-500 mb-1">Status</label>
        <select name="status"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="">All statuses</option>
            <option value="installed" {{ request('status') == 'installed' ? 'selected' : '' }}>Installed</option>
            <option value="failed"    {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
            <option value="checked"   {{ request('status') == 'checked' ? 'selected' : '' }}>Checked</option>
            <option value="never"     {{ request('status') == 'never' ? 'selected' : '' }}>Never reported</option>
        </select>
    </div>

    <div class="min-w-36">
        <label class="block text-xs font-medium text-slate-500 mb-1">Version</label>
        <select name="version"
                class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
            <option value="">All versions</option>
            @foreach($versions as $version)
                <option value="{{ $version }}" {{ request('version') == $version ? 'selected' : '' }}>
                    v{{ $version }}
                </option>
            @endforeach
        </select>
    </div>

    <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        Filter
    </button>

    @if(request()->hasAny(['search', 'province', 'district', 'status', 'version']))
        <a href="{{ route('sites.index') }}"
           class="text-sm text-slate-500 hover:text-slate-700 py-2">
            Clear
        </a>
    @endif
</form>

{{-- Results --}}
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-6 py-3 bg-slate-50 border-b border-slate-100 text-sm text-slate-500">
        {{ $sites->total() }} sites
    </div>

    @if($sites->isEmpty())
        <div class="px-6 py-12 text-center text-slate-400 text-sm">No sites match your filters.</div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 text-left">
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Site ID</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Name</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Province</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">District</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Version</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Status</th>
                        <th class="px-6 py-3 font-semibold text-slate-600 text-xs uppercase">Last Contact</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($sites as $site)
                    @php
                        $statusColors = [
                            'installed' => 'bg-green-100 text-green-800',
                            'failed'    => 'bg-red-100 text-red-800',
                            'checked'   => 'bg-blue-100 text-blue-800',
                            'unknown'   => 'bg-gray-100 text-gray-600',
                        ];
                        $sc = $statusColors[$site->last_status] ?? 'bg-gray-100 text-gray-500';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-3">
                            <a href="{{ route('sites.show', $site) }}"
                               class="font-mono text-xs text-blue-600 hover:text-blue-800">
                                {{ $site->siteid }}
                            </a>
                        </td>
                        <td class="px-6 py-3 text-slate-700 font-medium">
                            <a href="{{ route('sites.show', $site) }}" class="hover:text-blue-600">
                                {{ $site->site_name }}
                            </a>
                        </td>
                        <td class="px-6 py-3 text-slate-500">{{ $site->province ?? '—' }}</td>
                        <td class="px-6 py-3 text-slate-500">{{ $site->district ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @if($site->current_version)
                                <span class="text-slate-700 font-medium">v{{ $site->current_version }}</span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            @if($site->last_status)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $sc }}">
                                    {{ $site->last_status }}
                                </span>
                            @else
                                <span class="text-slate-300 text-xs">never</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-slate-400 text-xs">
                            {{ $site->last_checked_at?->diffForHumans() ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-slate-100">
            {{ $sites->links() }}
        </div>
    @endif
</div>

@endsection
