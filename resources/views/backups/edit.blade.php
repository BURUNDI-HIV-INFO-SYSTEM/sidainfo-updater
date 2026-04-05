@extends('layouts.app')

@section('title', 'Edit Backup Destination – SIDAInfo Update Server')
@section('heading', 'Edit Backup Destination')

@section('content')

<div class="max-w-4xl">
    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <form method="POST" action="{{ route('backups.update', $destination) }}" class="space-y-6">
            @csrf
            @method('PUT')

            @include('backups._form', ['destination' => $destination])

            <div class="flex items-center justify-between">
                <a href="{{ route('backups.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Back to backups</a>
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Update Destination
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
