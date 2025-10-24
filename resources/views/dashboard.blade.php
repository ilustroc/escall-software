@extends('layouts.app')

@section('title','Panel')

@section('content')
  <h2>Panel principal</h2>
  <div class="menu" style="margin-top:12px;">
    <a class="btn" href="{{ route('reportes.index') }}">Generar Reportes</a>
    <a class="btn" href="{{ route('cargas.index') }}">Cargas</a>
    <a class="btn" href="{{ route('tablas.index') }}">Tablas de resultados</a>
    <a class="btn" href="{{ route('sms.index') }}">SMS</a>
  </div>
@endsection
