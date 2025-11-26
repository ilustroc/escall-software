@extends('layouts.app')

@section('title','Panel')

@section('crumb','Panel principal')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-lg font-semibold text-slate-800">
                Panel principal
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Accesos rápidos a los módulos del software.
            </p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Reportes --}}
        <a href="{{ route('reportes.index') }}"
           class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm hover:border-red-500 hover:shadow-md transition">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-slate-800 group-hover:text-red-700">
                    Reportes
                </span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-red-50 text-red-700">
                    Ver
                </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                Generar y descargar reportes operativos.
            </p>
        </a>

        {{-- Cargas --}}
        <a href="{{ route('cargas.index') }}"
           class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm hover:border-red-500 hover:shadow-md transition">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-slate-800 group-hover:text-red-700">
                    Cargas
                </span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-700">
                    Gestionar
                </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                Importar data, gestiones y otros archivos.
            </p>
        </a>

        {{-- Tablas --}}
        <a href="{{ route('tablas.index') }}"
           class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm hover:border-red-500 hover:shadow-md transition">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-slate-800 group-hover:text-red-700">
                    Tablas de resultados
                </span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-700">
                    Configurar
                </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                Mantener las tablas y parámetros del sistema.
            </p>
        </a>

        {{-- SMS --}}
        <a href="{{ route('sms.index') }}"
           class="group flex flex-col justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm hover:border-red-500 hover:shadow-md transition">
            <div class="flex items-center justify-between gap-2">
                <span class="font-medium text-slate-800 group-hover:text-red-700">
                    SMS
                </span>
                <span class="text-[11px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-700">
                    Enviar
                </span>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                Envío y seguimiento de mensajes SMS.
            </p>
        </a>
    </div>
@endsection
