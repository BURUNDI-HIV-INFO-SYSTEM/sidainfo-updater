@extends('layouts.app')

@section('title', 'Releases – SIDAInfo Update Server')
@section('heading', 'Releases')

@section('header-actions')
    <a href="{{ route('releases.create') }}"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Upload Release
    </a>
@endsection

@section('content')

@if($releases->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-16 text-center">
        <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
        </svg>
        <p class="text-slate-500">No releases uploaded yet.</p>
        <a href="{{ route('releases.create') }}"
           class="mt-4 inline-block bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
            Upload first release
        </a>
    </div>
@else
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-100 bg-slate-50 text-left">
                    <th class="px-6 py-3 font-semibold text-slate-600">Version</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Archive</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Published</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Size</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Adoption</th>
                    <th class="px-6 py-3 font-semibold text-slate-600">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($releases as $release)
                @php $adoption = $release->adoptionCount(); @endphp
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4">
                        <a href="{{ route('releases.show', $release) }}"
                           class="font-semibold text-blue-700 hover:text-blue-900">
                            v{{ $release->version }}
                        </a>
                        @if($release->minimum_required_version)
                            <div class="text-xs text-slate-400 mt-0.5">min: v{{ $release->minimum_required_version }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-slate-600">{{ $release->archive_name }}</td>
                    <td class="px-6 py-4 text-slate-600">
                        {{ $release->published_at ? $release->published_at->format('d M Y') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-slate-600">{{ $release->formattedSize() }}</td>
                    <td class="px-6 py-4">
                        <span class="text-slate-700 font-medium">{{ $adoption }}</span>
                        <span class="text-slate-400"> / {{ $totalSites }}</span>
                        @if($totalSites > 0)
                        <div class="h-1.5 bg-slate-100 rounded-full mt-1 w-24 overflow-hidden">
                            <div class="h-full bg-blue-500 rounded-full"
                                 style="width: {{ round(($adoption / $totalSites) * 100) }}%"></div>
                        </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($release->is_active)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-500">
                                Inactive
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center gap-3 justify-end">
                            @if(!$release->is_active)
                                <form method="POST" action="{{ route('releases.activate', $release) }}">
                                    @csrf
                                    <button type="submit"
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Set Active
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('releases.destroy', $release) }}"
                                      onsubmit="return confirm('Delete release v{{ $release->version }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="text-xs text-red-500 hover:text-red-700 font-medium">
                                        Delete
                                    </button>
                                </form>
                            @endif
                            <a href="{{ route('releases.show', $release) }}"
                               class="text-xs text-slate-500 hover:text-slate-700 font-medium">
                                Details
                            </a>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

@endsection
