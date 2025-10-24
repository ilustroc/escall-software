@extends('layouts.app')
@section('title','Reporte KP INVEST')

@section('content')
<h2>Reporte KP INVEST</h2>

<form method="GET" action="{{ route('reportes.kp.index') }}" style="margin:12px 0; display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
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
    <a class="btn" href="{{ route('reportes.kp.export', ['fi'=>$fi,'ff'=>$ff]) }}">Exportar Excel</a>
    <span style="font-size:12px; color:#666;">Total: {{ number_format($count,0,',','.') }} filas</span>
  @endif
</form>

<div style="overflow:auto; max-width:100%; border:1px solid #ddd; border-radius:6px;">
  <table style="border-collapse:collapse; width:100%; font-size:13px;">
    <thead>
      <tr style="background:#f2f2f2;">
        <th>Fecha Gest</th>
        <th>Tip.Doc</th>
        <th>Num.Doc</th>
        <th>Cliente</th>
        <th>Teléfono</th>
        <th>Acción</th>
        <th>Tipificación</th>
        <th>ESTADO</th>
        <th>Gestion</th>
        <th>Fecha Promesa</th>
        <th>Monto Promesa</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td>{{ \Carbon\Carbon::parse($r->fecha_gest)->format('d/m/Y') }}</td>
          <td>{{ $r->tip_doc }}</td>
          <td>{{ $r->num_doc }}</td>
          <td>{{ $r->cliente }}</td>
          <td>{{ $r->telefono }}</td>
          <td>LLAMADA ENTRATE</td>
          <td>{{ $r->tipificacion }}</td>
          <td>{{ $r->estado }}</td>
          <td>{{ $r->gestion }}</td>
          <td>{{ $r->fecha_promesa ? \Carbon\Carbon::parse($r->fecha_promesa)->format('d/m/Y') : '' }}</td>
          <td class="num">{{ is_null($r->monto_promesa) ? '' : number_format($r->monto_promesa,0,',','.') }}</td>
        </tr>
      @empty
        <tr><td colspan="11">Sin resultados para el rango seleccionado.</td></tr>
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
