@extends('layouts.app')
@section('title','Cargar Gestiones')
@section('content')
<h2>Cargar Gestiones</h2>

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
<code>fecha_gestion | dni | telefono | status | tipificacion | observacion | fecha_pago | monto_pago | nombre</code>
<br><br>

<form method="POST" action="{{ route('cargas.gestiones.upload') }}" enctype="multipart/form-data">
  @csrf
  <input type="file" name="archivo" accept=".xlsx" required>
  <button type="submit" class="btn">Subir e importar</button>
</form>
@endsection
