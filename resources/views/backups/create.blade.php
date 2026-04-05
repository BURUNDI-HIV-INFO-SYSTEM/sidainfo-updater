@extends('layouts.app')

@section('title', 'Add Backup Destination – SIDAInfo Update Server')
@section('heading', 'Add Backup Destination')

@section('content')

<div class="max-w-4xl">
    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <form method="POST" action="{{ route('backups.store') }}" class="space-y-6">
            @csrf

            @include('backups._form')

            <div class="flex items-center justify-between">
                <a href="{{ route('backups.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Back to backups</a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Save Destination
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
