@extends('layouts.app')
@section('title','Cargas')
@section('content')
<h2>Cargas</h2>

<p>Aquí subiremos gestiones, pagos y propuestas.</p>

<p>
  <a class="btn" href="{{ route('cargas.gestiones.form') }}">Cargar Gestiones</a>
  <a class="btn" href="{{ route('cargas.sp.form') }}">Cargar SP</a>
  <a class="btn" href="{{ route('cargas.data.form') }}">Cargar Data</a>
</p>
@endsection
