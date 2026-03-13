@extends('layouts.app')

@section('title', 'Upload Release – SIDAInfo Update Server')
@section('heading', 'Upload New Release')

@section('content')

<div class="max-w-2xl">
    <div class="bg-white rounded-xl border border-slate-200 p-8">

        @if($errors->any())
            <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                <ul class="list-disc list-inside space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('releases.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid grid-cols-2 gap-5 mb-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="version">
                        Version <span class="text-red-500">*</span>
                    </label>
                    <input id="version" name="version" type="text" placeholder="e.g. 1.2.41"
                           value="{{ old('version') }}" required
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 @error('version') border-red-400 @enderror">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="minimum_required_version">
                        Minimum Required Version
                    </label>
                    <input id="minimum_required_version" name="minimum_required_version" type="text"
                           placeholder="e.g. 1.2.30" value="{{ old('minimum_required_version') }}"
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="published_at">
                    Publish Date
                </label>
                <input id="published_at" name="published_at" type="datetime-local"
                       value="{{ old('published_at', now()->format('Y-m-d\TH:i')) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="notes">
                    Release Notes
                </label>
                <textarea id="notes" name="notes" rows="4"
                          placeholder="Describe what changed in this release…"
                          class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none">{{ old('notes') }}</textarea>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="zip_file">
                    ZIP Archive <span class="text-red-500">*</span>
                </label>
                <div class="border-2 border-dashed border-slate-300 rounded-lg p-8 text-center hover:border-blue-400 transition-colors cursor-pointer"
                     onclick="document.getElementById('zip_file').click()">
                    <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="text-sm text-slate-500" id="file-label">Click to select a ZIP file</p>
                    <p class="text-xs text-slate-400 mt-1">Maximum 2 GB</p>
                    <input id="zip_file" name="zip_file" type="file" accept=".zip" required class="hidden"
                           onchange="document.getElementById('file-label').textContent = this.files[0]?.name ?? 'No file selected'">
                </div>
                @error('zip_file')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-2.5 rounded-lg text-sm transition-colors">
                    Upload Release
                </button>
                <a href="{{ route('releases.index') }}"
                   class="text-sm text-slate-500 hover:text-slate-700">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
