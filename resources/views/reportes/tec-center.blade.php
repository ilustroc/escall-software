@extends('layouts.app')
@section('title','Reporte TEC CENTER')

@section('content')
<h2>Reporte TEC CENTER</h2>

<form method="GET" action="{{ route('reportes.tec.index') }}" style="margin:12px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
  <div>
    <label>Fecha inicio</label><br>
    <input type="date" name="fi" value="{{ $fi }}" required>
  </div>
  <div>
    <label>Fecha fin</label><br>
    <input type="date" name="ff" value="{{ $ff }}" required>
  </div>
  <button class="btn" type="submit">Buscar</button>

  @if(($count ?? 0) > 0)
    <a class="btn" href="{{ route('reportes.tec.export', ['fi'=>$fi,'ff'=>$ff]) }}">
      Exportar Excel (FRMT_GESTIONES CP)
    </a>
    <span style="font-size:12px; color:#666;">Total: {{ number_format($count,0,',','.') }} filas</span>
  @endif
</form>

<div style="overflow:auto; max-width:100%; border:1px solid #ddd; border-radius:6px;">
  <table style="border-collapse:collapse; width:100%; font-size:13px;">
    <thead>
      <tr style="background:#f2f2f2;">
        <th>DNI</th>
        <th>N° CUENTA</th>
        <th>CARTERA</th>
        <th>FECHA GESTION</th>
        <th>CONTACTO</th>
        <th>RESULTADO</th>
        <th>GESTOR</th>
        <th>COMENTARIO</th>
        <th>TELEFONO</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r->dni }}</td>
          <td>{{ $r->ncuenta }}</td>
          <td>{{ $r->cartera }}</td>
          <td>{{ \Carbon\Carbon::parse($r->fecha_gestion)->format('d/m/Y') }}</td>
          <td>{{ $r->contacto }}</td>
          <td>{{ $r->resultado }}</td>
          <td>{{ $r->gestor }}</td>
          <td>{{ $r->comentario }}</td>
          <td>{{ $r->telefono }}</td>
        </tr>
      @empty
        <tr><td colspan="9">Sin resultados para el rango seleccionado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if(method_exists($rows,'links'))
  <div style="margin-top:10px;">{{ $rows->links() }}</div>
@endif

<style>
  table th, table td{ padding:6px 8px; border-bottom:1px solid #e0e0e0; border-right:1px solid #e0e0e0; white-space:nowrap; }
</style>
@endsection
