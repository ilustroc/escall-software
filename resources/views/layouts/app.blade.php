<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>@yield('title','ESCALL • Software')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-800 flex flex-col">

    {{-- HEADER --}}
    <header class="sticky top-0 z-30 border-b bg-white/95 backdrop-blur">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex items-center justify-between h-14">
                {{-- Marca --}}
                <div class="flex items-center gap-2">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-600 text-white font-semibold shadow-sm">
                        E
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-sm font-semibold tracking-tight">
                            ESCALL PERÚ
                        </span>
                        <span class="text-[11px] text-slate-500">
                            Panel Software
                        </span>
                    </div>
                </div>

                @auth
                {{-- Navegación desktop --}}
                <nav id="navMain" class="hidden md:flex items-center gap-2 text-sm">
                    <a href="{{ route('dashboard') }}"
                       class="px-3 py-2 rounded-lg font-medium transition
                              {{ request()->routeIs('dashboard') 
                                    ? 'bg-red-50 text-red-700' 
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Inicio
                    </a>

                    <a href="{{ route('cargas.index') }}"
                       class="px-3 py-2 rounded-lg font-medium transition
                              {{ request()->routeIs('cargas.*') 
                                    ? 'bg-red-50 text-red-700' 
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Cargas
                    </a>

                    <a href="{{ route('tablas.index') }}"
                       class="px-3 py-2 rounded-lg font-medium transition
                              {{ request()->routeIs('tablas.*') 
                                    ? 'bg-red-50 text-red-700' 
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Tablas
                    </a>

                    <a href="{{ route('reportes.index') }}"
                       class="px-3 py-2 rounded-lg font-medium transition
                              {{ request()->routeIs('reportes.*') 
                                    ? 'bg-red-50 text-red-700' 
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        Reportes
                    </a>

                    <a href="{{ route('sms.index') }}"
                       class="px-3 py-2 rounded-lg font-medium transition
                              {{ request()->routeIs('sms.*') 
                                    ? 'bg-red-50 text-red-700' 
                                    : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                        SMS
                    </a>
                </nav>

                {{-- Acciones / Logout --}}
                <div class="flex items-center gap-2">
                    {{-- Botón menú móvil --}}
                    <button id="btnMobile"
                            class="inline-flex items-center justify-center md:hidden h-9 w-9 rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                            aria-label="Abrir menú">
                        ☰
                    </button>

                    <form method="POST" action="{{ route('logout') }}" class="hidden md:block">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                            Salir
                        </button>
                    </form>
                </div>
                @endauth
            </div>

            {{-- Navegación móvil --}}
            @auth
            <nav id="navMobile" class="md:hidden hidden flex-col gap-1 pb-3 text-sm">
                <a href="{{ route('dashboard') }}"
                   class="block rounded-lg px-3 py-2 font-medium
                          {{ request()->routeIs('dashboard') 
                                ? 'bg-red-50 text-red-700' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Inicio
                </a>
                <a href="{{ route('cargas.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium
                          {{ request()->routeIs('cargas.*') 
                                ? 'bg-red-50 text-red-700' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Cargas
                </a>
                <a href="{{ route('tablas.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium
                          {{ request()->routeIs('tablas.*') 
                                ? 'bg-red-50 text-red-700' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Tablas
                </a>
                <a href="{{ route('reportes.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium
                          {{ request()->routeIs('reportes.*') 
                                ? 'bg-red-50 text-red-700' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    Reportes
                </a>
                <a href="{{ route('sms.index') }}"
                   class="block rounded-lg px-3 py-2 font-medium
                          {{ request()->routeIs('sms.*') 
                                ? 'bg-red-50 text-red-700' 
                                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}">
                    SMS
                </a>

                <form method="POST" action="{{ route('logout') }}" class="mt-1">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                        Salir
                    </button>
                </form>
            </nav>
            @endauth
        </div>
    </header>

    {{-- CONTENIDO --}}
    <main class="flex-1">
        <div class="max-w-none mx-auto px-4 py-5 space-y-4">

            {{-- Breadcrumb --}}
            @hasSection('crumb')
                <nav class="text-xs text-slate-500" aria-label="Breadcrumb">
                    <ol class="flex items-center gap-1">
                        <li>
                            <a href="{{ route('dashboard') }}" class="hover:text-slate-700">
                                Inicio
                            </a>
                        </li>
                        <li class="text-slate-400">/</li>
                        <li class="truncate">
                            @yield('crumb')
                        </li>
                    </ol>
                </nav>
            @endif

            {{-- Flash messages --}}
            @if(session('ok') || session('warn') || session('error') || $errors->any())
                <div class="space-y-2">
                    @if(session('ok'))
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs text-emerald-800">
                            {{ session('ok') }}
                        </div>
                    @endif

                    @if(session('warn'))
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            {{ session('warn') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-800">
                            <div class="font-semibold mb-1">Errores:</div>
                            <ul class="list-disc pl-4 space-y-0.5">
                                @foreach($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Card principal --}}
            <section class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4 md:p-6">
                @yield('content')
            </section>
        </div>
    </main>

    {{-- FOOTER --}}
    <footer class="border-t bg-white">
        <div class="max-w-6xl mx-auto px-4 py-3 text-[11px] text-slate-500 flex justify-between items-center">
            <span>© {{ date('Y') }} Escall Perú</span>
            <span class="hidden sm:inline">Panel de Software</span>
        </div>
    </footer>

    {{-- Script simple para menú móvil --}}
    <script>
        const btn = document.getElementById('btnMobile');
        const navMobile = document.getElementById('navMobile');

        if (btn && navMobile) {
            btn.addEventListener('click', () => {
                navMobile.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
