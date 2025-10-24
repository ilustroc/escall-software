@extends('layouts.app')
@section('title','Carga SP')
@section('content')
<h2>Carga por SP – Rango de fechas</h2>

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

<form method="GET" action="{{ route('cargas.sp.preview') }}" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
  <div>
    <label>Fecha inicio</label><br>
    <input type="date" name="fi" value="{{ old('fi',$fi) }}" required>
  </div>
  <div>
    <label>Fecha fin</label><br>
    <input type="date" name="ff" value="{{ old('ff',$ff) }}" required>
  </div>
  <button class="btn" type="submit">Vista previa</button>
</form>

@if($preview !== null)
  <div class="box" style="margin-top:12px;">
    <strong>Resultado del SP:</strong>
    <div style="font-size:12px; color:#555;">Se muestran hasta 100 filas. Total devuelto por el SP: <b>{{ number_format($count,0,',','.') }}</b></div>
  </div>

  <div style="overflow:auto; max-height:420px; border:1px solid #ddd;">
    <table border="1" cellpadding="4" cellspacing="0" style="border-collapse:collapse; width:100%; font-size:13px;">
      <thead style="background:#f2f2f2;">
        <tr>
          <th>fecha_gestion</th>
          <th>dni</th>
          <th>telefono</th>
          <th>status</th>
          <th>tipificacion</th>
          <th>observacion</th>
          <th>fecha_pago</th>
          <th>monto_pago</th>
          <th>nombre</th>
        </tr>
      </thead>
      <tbody>
        @forelse($preview as $r)
          <tr>
            <td>{{ $r['fecha_gestion'] }}</td>
            <td>{{ $r['dni'] }}</td>
            <td>{{ $r['telefono'] }}</td>
            <td>{{ $r['status'] }}</td>
            <td>{{ $r['tipificacion'] }}</td>
            <td>{{ Str::limit($r['observacion'] ?? '', 80) }}</td>
            <td>{{ $r['fecha_pago'] }}</td>
            <td style="text-align:right;">{{ $r['monto_pago'] }}</td>
            <td>{{ $r['nombre'] }}</td>
          </tr>
        @empty
          <tr><td colspan="9">Sin filas en el rango.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <form method="POST" action="{{ route('cargas.sp.import') }}" style="margin-top:10px;">
    @csrf
    <input type="hidden" name="fi" value="{{ $fi }}">
    <input type="hidden" name="ff" value="{{ $ff }}">
    <button class="btn" type="submit" onclick="return confirm('Se borrarán las gestiones locales entre {{ $fi }} y {{ $ff }} y se importarán desde el SP. ¿Continuar?')">
      Borrar rango e Importar desde SP
    </button>
  </form>
@endif
@endsection
