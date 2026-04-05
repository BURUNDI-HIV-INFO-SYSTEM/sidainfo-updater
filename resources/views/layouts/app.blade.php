<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SIDAInfo Update Server')</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen">

<div class="flex h-screen overflow-hidden">

    {{-- Sidebar --}}
    <aside class="w-60 flex-shrink-0 flex flex-col" style="background:#1e293b">

        {{-- Brand --}}
        <div class="px-5 py-5 flex items-center gap-3" style="border-bottom:1px solid rgba(255,255,255,0.07)">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                 style="background:#3b82f6">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10"/>
                </svg>
            </div>
            <div>
                <div class="text-white font-semibold text-sm leading-tight">SIDAInfo</div>
                <div class="text-xs leading-tight" style="color:#64748b">Update Server</div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">

            <p class="px-3 mb-2 text-xs font-semibold uppercase tracking-wider" style="color:#475569">Menu</p>

            @php
                $navLinks = [
                    ['route' => 'dashboard',      'match' => 'dashboard',    'label' => 'Dashboard',
                     'icon'  => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ['route' => 'releases.index', 'match' => 'releases.*',   'label' => 'Releases',
                     'icon'  => 'M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3V10'],
                    ['route' => 'sites.index',    'match' => 'sites.*',      'label' => 'Sites',
                     'icon'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                    ['route' => 'users.index',    'match' => 'users.*',      'label' => 'Users',
                     'icon'  => 'M17 20h5V18a4 4 0 00-5.874-3.543M17 20H7m10 0v-2c0-.653-.126-1.277-.355-1.848M7 20H2V18a4 4 0 015.874-3.543M7 20v-2c0-.653.126-1.277.355-1.848m0 0a5.002 5.002 0 019.29 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                    ['route' => 'backups.index',  'match' => 'backups.*',    'label' => 'Backups',
                     'icon'  => 'M4 7h16M4 12h16m-7 5h7M7 17H4m0-10l1.293-1.293A1 1 0 016 5h12a1 1 0 01.707.293L20 7m-9 5l-3 3m0 0l-3-3m3 3V7'],
                    ['route' => 'tarifs.index',        'match' => 'tarifs.*',         'label' => 'Tarifs',
                     'icon'  => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ['route' => 'examens-config.index','match' => 'examens-config.*', 'label' => 'Examens',
                     'icon'  => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                ];
            @endphp

            @foreach($navLinks as $link)
                @php $active = request()->routeIs($link['match']); @endphp
                <a href="{{ route($link['route']) }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors group"
                   style="{{ $active
                       ? 'background:#3b82f6; color:#ffffff;'
                       : 'color:#94a3b8;' }}"
                   @if(!$active)
                   onmouseover="this.style.background='rgba(255,255,255,0.07)'; this.style.color='#e2e8f0';"
                   onmouseout="this.style.background=''; this.style.color='#94a3b8';"
                   @endif
                >
                    {{-- Active indicator bar --}}
                    <span class="w-0.5 h-4 rounded-full flex-shrink-0 transition-colors"
                          style="{{ $active ? 'background:#93c5fd;' : 'background:transparent;' }}"></span>

                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                              d="{{ $link['icon'] }}"/>
                    </svg>

                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- User footer --}}
        <div class="px-3 py-3" style="border-top:1px solid rgba(255,255,255,0.07)">
            <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg"
                 style="background:rgba(255,255,255,0.05)">
                {{-- Avatar initials --}}
                <div class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-xs font-bold text-white"
                     style="background:#3b82f6">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</div>
                    <div class="text-xs truncate" style="color:#64748b">{{ auth()->user()->email }}</div>
                    <a href="{{ route('profile.edit') }}"
                       class="mt-1 inline-block text-xs text-blue-300 hover:text-blue-200">
                        Profile settings
                    </a>
                </div>
                {{-- Sign out button --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" title="Sign out"
                            style="color:#64748b"
                            onmouseover="this.style.color='#f87171'"
                            onmouseout="this.style.color='#64748b'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="bg-white border-b border-slate-200 px-8 py-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold text-slate-800">@yield('heading', 'Dashboard')</h1>
            <div>@yield('header-actions')</div>
        </header>

        <div class="flex-1 overflow-y-auto p-8">
            @if(session('success'))
                <div class="mb-6 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

</body>
</html>
