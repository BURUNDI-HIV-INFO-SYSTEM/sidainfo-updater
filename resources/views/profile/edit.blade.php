@extends('layouts.app')

@section('title', 'Profile – SIDAInfo Update Server')
@section('heading', 'Profile Settings')

@section('content')

<div class="max-w-4xl space-y-6">
    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <h2 class="text-lg font-semibold text-slate-800 mb-6">Profile</h2>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="name">Name</label>
                <input id="name" name="name" type="text" required value="{{ old('name', $user->name) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('name')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email', $user->email) }}"
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('email')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Save Profile
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-8">
        <h2 class="text-lg font-semibold text-slate-800 mb-6">Change Password</h2>

        <form method="POST" action="{{ route('profile.password.update') }}" class="space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5" for="current_password">Current Password</label>
                <input id="current_password" name="current_password" type="password" required
                       class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('current_password')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password">New Password</label>
                    <input id="password" name="password" type="password" required
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5" for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                           class="w-full px-3.5 py-2.5 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="bg-slate-900 hover:bg-slate-800 text-white font-medium px-5 py-2.5 rounded-lg text-sm transition-colors">
                    Change Password
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
