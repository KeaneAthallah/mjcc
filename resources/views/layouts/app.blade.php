<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · MOROWALI JUARA COMMAND CENTER</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.csrfToken = @json(csrf_token());
        window.flashMessages = @json($flashMessages);
    </script>
</head>
<body class="bg-gray-100 text-gray-800 h-screen overflow-hidden">

<div class="flex h-full">

    <!-- ===== SIDEBAR ===== -->
    <aside x-data="sidebarNav()"
           :class="open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed lg:static inset-y-0 left-0 z-40 w-64 flex flex-col bg-gradient-to-b from-gray-900 via-gray-900 to-emerald-700 text-white shadow-2xl transition-transform duration-300 lg:translate-x-0">

        <div class="px-6 py-5 text-center border-b border-white/10">
            <div class="w-[72px] h-[72px] mx-auto rounded-full bg-white flex items-center justify-center overflow-hidden shadow-lg mb-2">
                <img src="{{ asset('logo.png') }}" alt="Logo Morowali Juara" class="w-full h-full object-contain p-1.5">
            </div>
            <h2 class="text-[13px] tracking-widest uppercase font-extrabold leading-tight">Morowali Juara<br>Command Center</h2>
            <small class="text-[10px] opacity-60 tracking-widest">Dashboard Monitoring</small>
        </div>

        <nav class="flex-1 overflow-y-auto py-3 sidebar-scroll">
            @can('viewAny', \App\Models\SosAlert::class)
                <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Darurat</div>
                <a href="{{ route('sos.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('sos.*') ? 'bg-red-600/20 text-white border-red-400' : 'text-white/75 border-transparent hover:border-red-400' }}">
                    <span class="w-[22px] text-center text-base">🆘</span>SOS Darurat
                    <span class="ml-auto bg-red-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $sosStats['open'] ?? 0 }}</span>
                </a>
                <div class="pt-1"></div>
            @endcan

            <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Menu Utama</div>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('dashboard') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">▧</span>Beranda
            </a>

            <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Pemantauan</div>
            <a href="{{ route('education.dashboard') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('education.dashboard') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">🎓</span>Pendidikan
                <span class="ml-auto bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $sidebarStats['pendidikan'] }}</span>
            </a>
            <a href="{{ route('security.dashboard') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('security.dashboard') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">🛡️</span>Ketertiban
                <span class="ml-auto bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $sidebarStats['ketertiban'] }}</span>
            </a>
            <a href="{{ route('health.dashboard') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('health.dashboard') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">🏥</span>Kesehatan
                <span class="ml-auto bg-emerald-600 text-white text-[10px] px-2 py-0.5 rounded-full font-bold">{{ $sidebarStats['kesehatan'] }}</span>
            </a>

            <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Informasi</div>
            <a href="{{ route('maps.index') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('maps.index') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">🗺️</span>Peta Gabungan
            </a>

            @can('viewAny', \App\Models\CrawlRecord::class)
                <div class="px-5 py-2 mt-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Data Terintegrasi</div>

                <a href="{{ route('crawler.dashboard') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.dashboard') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">▧</span>Overview Data
                </a>

                {{-- ATS --}}
                <div x-data="sidebarGroup({{ request()->routeIs('crawler.ats*') ? 'true' : 'false' }})">
                    <button @click="toggle()"
                            class="w-full flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.ats*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                        <span class="w-[22px] text-center text-base">👥</span>ATS Kemendikdasmen
                        <svg class="ml-auto w-4 h-4 opacity-60 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="bg-white/5">
                        <x:crawler-subnav :links="[
                            ['route' => 'crawler.ats', 'label' => 'Ringkasan'],
                            ['route' => 'crawler.ats', 'label' => 'Data ATS'],
                            ['route' => 'crawler.ats', 'label' => 'Per Kecamatan'],
                            ['route' => 'maps.index', 'label' => 'Peta'],
                        ]"/>
                    </div>
                </div>

                {{-- DAPO --}}
                <div x-data="sidebarGroup({{ request()->routeIs('crawler.dapo*') ? 'true' : 'false' }})">
                    <button @click="toggle()"
                            class="w-full flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.dapo*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                        <span class="w-[22px] text-center text-base">🏫</span>DAPO Kemendikdasmen
                        <svg class="ml-auto w-4 h-4 opacity-60 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="bg-white/5">
                        <x:crawler-subnav :links="[
                            ['route' => 'crawler.dapo', 'label' => 'Sekolah'],
                            ['route' => 'maps.index', 'label' => 'Peta'],
                        ]"/>
                    </div>
                </div>

                {{-- SP2KP --}}
                <div x-data="sidebarGroup({{ request()->routeIs('crawler.sp2kp*') ? 'true' : 'false' }})">
                    <button @click="toggle()"
                            class="w-full flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.sp2kp*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                        <span class="w-[22px] text-center text-base">🏪</span>PIHPS BI
                        <svg class="ml-auto w-4 h-4 opacity-60 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="bg-white/5">
                        <x:crawler-subnav :links="[
                            ['route' => 'crawler.sp2kp', 'label' => 'Harga'],
                            ['route' => 'crawler.sp2kp', 'label' => 'Komoditas'],
                            ['route' => 'maps.index', 'label' => 'Peta'],
                        ]"/>
                    </div>
                </div>

                {{-- BPS --}}
                <div x-data="sidebarGroup({{ request()->routeIs('crawler.bps*') ? 'true' : 'false' }})">
                    <button @click="toggle()"
                            class="w-full flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.bps*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                        <span class="w-[22px] text-center text-base">📊</span>BPS
                        <svg class="ml-auto w-4 h-4 opacity-60 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="bg-white/5">
                        <x:crawler-subnav :links="[
                            ['route' => 'crawler.bps', 'label' => 'Indikator'],
                            ['route' => 'crawler.bps', 'label' => 'Statistik Wilayah'],
                        ]"/>
                    </div>
                </div>

                {{-- Kesehatan --}}
                <div x-data="sidebarGroup({{ request()->routeIs('crawler.kesehatan*') ? 'true' : 'false' }})">
                    <button @click="toggle()"
                            class="w-full flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.kesehatan*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                        <span class="w-[22px] text-center text-base">🏥</span>Fasyankes Kemenkes
                        <svg class="ml-auto w-4 h-4 opacity-60 transition-transform" :class="open ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </button>
                    <div x-show="open" x-cloak class="bg-white/5">
                        <x:crawler-subnav :links="[
                            ['route' => 'crawler.kesehatan', 'label' => 'Ringkasan'],
                            ['route' => 'crawler.kesehatan', 'label' => 'Data Fasyankes'],
                        ]"/>
                    </div>
                </div>

                <a href="{{ route('crawler.runs') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('crawler.runs*') ? 'bg-white/10 text-white border-violet-400' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🕘</span>Riwayat Sinkronisasi
                </a>
            @endcan

            {{-- Data Master (admin + operator) --}}
            @can('viewAny', \App\Models\School::class)
                <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Data Master</div>
                <a href="{{ route('master.kecamatans.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('master.kecamatans.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🗂️</span>Kecamatan
                </a>
                <a href="{{ route('master.kelurahans.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('master.kelurahans.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🏘️</span>Kelurahan/Desa
                </a>
                <a href="{{ route('education.schools.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('education.schools.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🏫</span>Sekolah
                </a>
                <a href="{{ route('master.subjects.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('master.subjects.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">📚</span>Mata Pelajaran
                </a>
                <a href="{{ route('security.polseks.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('security.polseks.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🚓</span>Polsek
                </a>
                <a href="{{ route('security.tipkamtikmas.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('security.tipkamtikmas.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🪖</span>Tipkamtikmas
                </a>
                <a href="{{ route('security.poskamlings.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('security.poskamlings.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🛡️</span>Poskamling
                </a>
                <a href="{{ route('security.markets.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('security.markets.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🏪</span>Pasar
                </a>
                <a href="{{ route('health.facilities.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('health.facilities.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">🏥</span>Fasilitas Kesehatan
                </a>
            @endcan

            {{-- System (admin only) --}}
            @can('viewAny', \App\Models\User::class)
                <div class="px-5 py-2 text-[10px] uppercase tracking-widest opacity-40 font-bold">Sistem</div>
                <a href="{{ route('users.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('users.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">👤</span>Users
                </a>
                <a href="{{ route('audit.index') }}"
                   class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('audit.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                    <span class="w-[22px] text-center text-base">📜</span>Log Aktivitas
                </a>
            @endcan

            <a href="{{ route('profile.edit') }}"
               class="flex items-center gap-3 px-5 py-3 text-[13px] font-semibold border-l-[3px] transition hover:bg-white/10 hover:text-white {{ request()->routeIs('profile.*') ? 'bg-white/10 text-white border-emerald-500' : 'text-white/65 border-transparent' }}">
                <span class="w-[22px] text-center text-base">⚙️</span>Profile
            </a>
        </nav>

        <div class="px-5 py-4 border-t border-white/10 text-[11px] opacity-40 text-center tracking-wider">
            Morowali Juara &copy; {{ date('Y') }}
        </div>
    </aside>

    {{-- Mobile sidebar overlay --}}
    <div x-data="sidebarNav()" x-show="open" @click="close()"
         class="fixed inset-0 z-30 bg-black/50 lg:hidden"></div>

    <!-- ===== MAIN ===== -->
    <div class="flex flex-col flex-1 min-w-0 relative">

        <!-- ===== HEADER ===== -->
        <header class="sticky top-0 z-20 bg-white border-b-2 border-emerald-100 shadow-sm flex items-center justify-between px-4 sm:px-7 h-[60px]">
            <div class="flex items-center gap-3">
                <button x-data="sidebarNav()" @click="toggle()" class="lg:hidden text-gray-700 p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-[15px] sm:text-base font-extrabold tracking-wide text-gray-900">
                    ◆ <span class="text-emerald-600">MOROWALI</span> JUARA COMMAND CENTER
                </h1>
            </div>

            <div class="flex items-center gap-4 sm:gap-5">
                <span class="hidden sm:inline-flex items-center text-[12px] font-bold text-gray-700">
                    <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse mr-1.5"></span> LIVE
                </span>

                @can('viewAny', \App\Models\SosAlert::class)
                    <a href="{{ route('sos.index') }}"
                       x-data="sosMonitor()"
                       data-open="{{ $sosStats['open'] ?? 0 }}"
                       data-active="{{ $sosStats['active'] ?? 0 }}"
                       class="relative flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border border-red-200 text-red-700 hover:bg-red-100"
                       :class="{ 'animate-pulse': active > 0 }"
                       title="SOS Darurat"
                    >
                        <span class="text-base">🆘</span>
                        <span class="hidden md:inline text-[12px] font-bold">SOS</span>
                        <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-red-600 text-white text-[11px] font-extrabold flex items-center justify-center" x-text="open">0</span>
                    </a>
                @endcan

                <div x-data="liveClock()" class="text-right">
                    <div class="text-lg sm:text-xl font-extrabold text-emerald-700 tabular-nums" x-text="time">--:--:--</div>
                    <div class="text-[10px] sm:text-[11px] text-gray-600 capitalize" x-text="date">--</div>
                </div>

                {{-- User menu --}}
                <div x-data="dropdown()" @click.outside="close()" class="relative">
                    <button @click="toggle()" class="flex items-center gap-2 focus:outline-none">
                        <span class="hidden sm:block text-[13px] font-bold text-gray-800">{{ $currentUser->name }}</span>
                        <span class="w-9 h-9 rounded-full bg-gradient-to-br from-emerald-500 to-blue-600 text-white flex items-center justify-center text-sm font-bold">
                            {{ strtoupper(substr($currentUser->name, 0, 1)) }}
                        </span>
                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open" x-cloak @click="close()"
                         class="absolute right-0 mt-2 w-52 bg-white rounded-xl shadow-xl border border-gray-200 py-1 z-50">
                        <div class="px-4 py-2 border-b border-gray-100">
                            <div class="text-[13px] font-bold text-gray-800 truncate">{{ $currentUser->name }}</div>
                            <div class="text-[11px] text-emerald-600 font-semibold capitalize">{{ $currentUser->role }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-[13px] text-gray-700 hover:bg-gray-50">Profil</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-[13px] text-red-600 hover:bg-red-50">Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- ===== CONTENT ===== -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

{{-- Toast notifications --}}
<div x-data="notifications()" class="fixed top-4 right-4 z-[100] space-y-2 w-80 max-w-[calc(100vw-2rem)]">
    <template x-for="item in items" :key="item.id">
        <div x-show="true" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="transform translate-x-full opacity-0"
             x-transition:enter-end="transform translate-x-0 opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             :class="'text-white rounded-xl shadow-lg px-4 py-3 flex items-start gap-3 ' + item.color">
            <span class="font-bold mt-0.5" x-text="icons[item.type] ?? '•'"></span>
            <div class="flex-1 text-[13px] font-medium" x-text="item.message"></div>
            <button @click="remove(item.id)" class="opacity-80 hover:opacity-100 text-sm">✕</button>
        </div>
    </template>
</div>

{{-- Confirm dialog --}}
<div x-data="confirmDialog()" x-show="open" x-cloak class="fixed inset-0 z-[110] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" @click="cancel()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="w-12 h-12 mx-auto rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl mb-3">⚠️</div>
        <h3 class="text-center text-lg font-extrabold text-gray-900" x-text="title">Konfirmasi Hapus</h3>
        <p class="text-center text-[13px] text-gray-600 mt-2" x-text="message"></p>
        <div class="flex gap-3 mt-5">
            <button @click="cancel()" class="flex-1 px-4 py-2.5 rounded-xl bg-gray-100 text-gray-700 font-bold text-[13px] hover:bg-gray-200" x-text="cancelLabel">Batal</button>
            <button @click="confirm()" class="flex-1 px-4 py-2.5 rounded-xl bg-red-600 text-white font-bold text-[13px] hover:bg-red-700" x-text="confirmLabel">Ya, Hapus</button>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
    .sidebar-scroll::-webkit-scrollbar { width: 4px; }
    .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 2px; }
</style>

@stack('scripts')
</body>
</html>
