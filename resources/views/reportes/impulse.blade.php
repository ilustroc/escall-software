@extends('layouts.app')
@section('title','Reporte Impulse')

@section('content')
<h2>Reporte Impulse</h2>

<form method="GET" action="{{ route('reportes.impulse.index') }}" style="margin:12px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
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
    <a class="btn" href="{{ route('reportes.impulse.export', ['fi'=>$fi,'ff'=>$ff]) }}">
      Exportar Excel
    </a>
    <span style="font-size:12px; color:#666;">Total: {{ number_format($count,0,',','.') }} filas</span>
  @endif
</form>

<div style="overflow:auto; max-width:100%; border:1px solid #ddd; border-radius:6px;">
  <table style="border-collapse:collapse; width:100%; font-size:13px;">
    <thead>
      <tr style="background:#f2f2f2;">
        <th>DOCUMENTO</th>
        <th>CLIENTE</th>
        <th>ACCIÓN</th>
        <th>CONTACTO</th>
        <th>AGENTE</th>
        <th>OPERACION</th>
        <th>ENTIDAD</th>
        <th>EQUIPO</th>
        <th>FECHA GESTION</th>
        <th>FECHA CITA</th>
        <th>TELEFONO</th>
        <th>OBSERVACION</th>
        <th>MONTO PROMESA</th>
        <th>NRO CUOTAS</th>
        <th>FECHA PROMESA</th>
        <th>PROCEDENCIA LLAMADA</th>
        <th>CARTERA</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ $r->documento }}</td>
          <td>{{ $r->cliente }}</td>
          <td>{{ $r->accion }}</td>
          <td>{{ $r->contacto }}</td>
          <td>{{ $r->agente }}</td>
          <td>{{ $r->operacion }}</td>
          <td>{{ $r->entidad }}</td>
          <td>PROPIA 2 - ESCALL</td>
          <td>{{ $r->fecha_gestion }}</td>
          <td></td>
          <td>{{ $r->telefono }}</td>
          <td>{{ $r->observacion }}</td>
          <td class="num">{{ is_null($r->monto_promesa) ? '' : number_format($r->monto_promesa, 0, ',', '.') }}</td>
          <td class="num">{{ $r->nro_cuotas }}</td>
          <td>{{ $r->fecha_promesa }}</td>
          <td>Predictivo</td>
          <td>{{ $r->cartera }}</td>
        </tr>
      @empty
        <tr><td colspan="17">Sin resultados para el rango seleccionado.</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

@if(method_exists($rows,'links'))
  <div style="margin-top:10px;">{{ $rows->links() }}</div>
@endif

<style>
  .num{ text-align:right; font-variant-numeric: tabular-nums; }
  table th, table td{ padding:6px 8px; border-bottom:1px solid #e0e0e0; border-right:1px solid #e0e0e0; white-space:nowrap; }
</style>
@endsection
