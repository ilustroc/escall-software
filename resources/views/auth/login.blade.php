@extends('layouts.app')

@section('title','Login')
@section('crumb','Iniciar sesión')

@section('content')
    <div class="max-w-sm mx-auto space-y-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                Iniciar sesión
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Ingresa tus credenciales para acceder al panel.
            </p>
        </div>

        <form method="POST"
              action="{{ route('login.post') }}"
              class="space-y-4 text-sm">
            @csrf

            {{-- Email --}}
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">
                    Email
                </label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       required
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm
                              focus:border-red-500 focus:ring-red-500">
                @error('email')
                    <p class="mt-1 text-[11px] text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">
                    Contraseña
                </label>
                <input type="password"
                       name="password"
                       required
                       class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm
                              focus:border-red-500 focus:ring-red-500">
                @error('password')
                    <p class="mt-1 text-[11px] text-red-600">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Remember --}}
            <div class="flex items-center justify-between">
                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox"
                           name="remember"
                           class="h-3.5 w-3.5 rounded border-slate-300 text-red-600 focus:ring-red-500">
                    <span>Recordarme</span>
                </label>
            </div>

            <div>
                <button type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-red-600 px-4 py-2
                               text-sm font-medium text-white shadow-sm hover:bg-red-700 focus:outline-none
                               focus:ring-2 focus:ring-red-500 focus:ring-offset-1">
                    Entrar
                </button>
            </div>
        </form>
    </div>
@endsection
