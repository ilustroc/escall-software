@extends('layouts.app')
@section('title','Reportes')
@section('crumb','Reportes')

@section('content')
    <div class="space-y-6">
        <header class="space-y-1">
            <h1 class="text-lg font-semibold text-slate-800">
                Generar Reportes
            </h1>
            <p class="text-xs text-slate-500">
                Aquí puedes consultar y exportar todos los reportes del software en una sola vista.
            </p>
        </header>

        {{-- Impulse --}}
        @include('reportes.partials.impulse-block')

        {{-- KP INVEST --}}
        @include('reportes.partials.kp-block')

        {{-- TEC CENTER --}}
        @include('reportes.partials.tec-block')

        {{-- CARTERAS --}}
        @include('reportes.partials.carteras-block')
    </div>
@endsection
