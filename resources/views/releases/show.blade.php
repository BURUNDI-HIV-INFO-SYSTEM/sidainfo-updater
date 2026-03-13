@extends('layouts.app')

@section('title', 'Release v'.$release->version.' – SIDAInfo Update Server')
@section('heading', 'Release v'.$release->version)

@section('header-actions')
    @if(!$release->is_active)
        <div class="flex items-center gap-3">
            <form method="POST" action="{{ route('releases.activate', $release) }}">
                @csrf
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    Set as Active
                </button>
            </form>
            <form method="POST" action="{{ route('releases.destroy', $release) }}"
                  onsubmit="return confirm('Permanently delete release v{{ $release->version }}?')">
                @csrf @method('DELETE')
                <button type="submit"
                        class="bg-red-50 hover:bg-red-100 text-red-600 text-sm font-medium px-4 py-2 rounded-lg transition-colors border border-red-200">
                    Delete
                </button>
            </form>
        </div>
    @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium bg-green-100 text-green-800">
            <span class="w-2 h-2 rounded-full bg-green-500"></span>
            Active Release
        </span>
    @endif
@endsection

@section('content')

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6">

    {{-- Release metadata --}}
    <div class="xl:col-span-1 bg-white rounded-xl border border-slate-200 p-6 space-y-4">
        <h2 class="font-semibold text-slate-700">Release Info</h2>

        <div class="space-y-3 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Version</span>
                <span class="font-semibold text-slate-800">{{ $release->version }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Archive</span>
                <span class="font-mono text-xs text-slate-600">{{ $release->archive_name }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Published</span>
                <span class="text-slate-700">{{ $release->published_at?->format('d M Y H:i') ?? '—' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">File size</span>
                <span class="text-slate-700">{{ $release->formattedSize() }}</span>
            </div>
            @if($release->minimum_required_version)
            <div class="flex justify-between">
                <span class="text-slate-500">Min. version</span>
                <span class="text-slate-700">v{{ $release->minimum_required_version }}</span>
            </div>
            @endif
            @if($release->sha256)
            <div>
                <span class="text-slate-500">SHA-256</span>
                <div class="font-mono text-xs text-slate-600 break-all mt-1">{{ $release->sha256 }}</div>
            </div>
            @endif
        </div>

        @if($release->notes)
        <div class="pt-3 border-t border-slate-100">
            <div class="text-sm font-medium text-slate-600 mb-2">Release Notes</div>
            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $release->notes }}</p>
        </div>
        @endif

        <div class="pt-3 border-t border-slate-100">
            <div class="text-sm font-medium text-slate-600 mb-1">Download URL</div>
            <code class="text-xs text-blue-700 break-all">{{ route('update.download', $release->version) }}</code>
        </div>
    </div>

    {{-- Adoption stats --}}
    <div class="xl:col-span-2 bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-slate-700">Adoption</h2>
            <span class="text-slate-500 text-sm">
                <strong class="text-slate-800">{{ $sites->count() }}</strong> / {{ $totalSites }} sites
                ({{ $totalSites > 0 ? round(($sites->count() / $totalSites) * 100) : 0 }}%)
            </span>
        </div>

        {{-- Progress bar --}}
        <div class="h-3 bg-slate-100 rounded-full overflow-hidden mb-6">
            @php $pct = $totalSites > 0 ? round(($sites->count() / $totalSites) * 100) : 0; @endphp
            <div class="h-full bg-blue-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>

        @if($sites->isEmpty())
            <p class="text-slate-400 text-sm text-center py-8">No sites are on this version yet.</p>
        @else
            <div class="overflow-auto max-h-72">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left border-b border-slate-100">
                            <th class="pb-2 text-xs font-semibold text-slate-500 uppercase">Site ID</th>
                            <th class="pb-2 text-xs font-semibold text-slate-500 uppercase">Name</th>
                            <th class="pb-2 text-xs font-semibold text-slate-500 uppercase">Province</th>
                            <th class="pb-2 text-xs font-semibold text-slate-500 uppercase">Last Update</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($sites as $site)
                        <tr class="hover:bg-slate-50">
                            <td class="py-2 font-mono text-xs text-slate-500">
                                <a href="{{ route('sites.show', $site) }}" class="hover:text-blue-600">{{ $site->siteid }}</a>
                            </td>
                            <td class="py-2 text-slate-700">{{ $site->site_name }}</td>
                            <td class="py-2 text-slate-500">{{ $site->province }}</td>
                            <td class="py-2 text-slate-400 text-xs">
                                {{ $site->last_installed_at?->diffForHumans() ?? '—' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

@endsection
