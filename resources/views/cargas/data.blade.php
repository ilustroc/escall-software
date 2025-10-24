@extends('layouts.app')
@section('title','Cargar Data')
@section('content')
<h2>Cargar Data</h2>

@if(session('ok'))
  <div class="box" style="border-color:#2a7;">{{ session('ok') }}</div>
@endif
@if($errors->any())
  <div class="box" style="border-color:#c00; color:#c00;">
    <ul style="margin:0; padding-left:16px;">
      @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
    </ul>
  </div>
@endif

<p><strong>Encabezados requeridos:</strong></p>
<code>CODIGO | DNI | TITULAR | CARTERA | ENTIDAD | COSECHA | SUB_CARTERA | PRODUCTO | SUB_PRODUCTO | HISTORICO | DEPARTAMENTO | DEUDA_TOTAL | DEUDA_CAPITAL | CAMPAÑA | PORCENTAJE_CAMPAÑA  </code>
<br><br>

<form method="POST" action="{{ route('cargas.data.upload') }}" enctype="multipart/form-data">
  @csrf
  <input type="file" name="archivo" accept=".xlsx" required>
  <button type="submit" class="btn">Subir e importar</button>
</form>
@endsection
