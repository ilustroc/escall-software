@extends('layouts.app')

@section('title','Cargas')
@section('crumb','Cargas')

@section('content')
    <div class="space-y-6">
        <header class="space-y-1">
            <h1 class="text-lg font-semibold text-slate-800">
                Cargas de información
            </h1>
            <p class="text-xs text-slate-500">
                Desde aquí puedes cargar data, gestiones y ejecutar el SP en una sola vista.
            </p>
        </header>

        {{-- GESTIONES --}}
        @include('cargas.partials.gestiones-block')

        {{-- SP --}}
        @include('cargas.partials.sp-block')

        {{-- DATA --}}
        @include('cargas.partials.data-block')
    </div>
@endsection
