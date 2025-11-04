@extends('layouts.app')
@section('title','Reportes')
@section('content')
<h2>Generar Reportes</h2>
<p>Aquí irá el módulo para generar y descargar reportes.</p>

<p>
  <a class="btn" href="{{ route('reportes.impulse.index') }}">Reporte Impulse</a>
  <a class="btn" href="{{ route('reportes.kp.index') }}">Reporte Kp Invest</a>
  <a class="btn" href="{{ route('reportes.tec.index') }}">Reporte Tec Center</a>
  <a class="btn" href="{{ route('reportes.carteras.index') }}">Reporte Carteras</a>
</p>
@endsection
