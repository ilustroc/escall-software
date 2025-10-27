@extends('layouts.app')
@section('title','Reportes de Carteras')

@section('content')
<h2>Reportes de Carteras</h2>

<div class="box" style="display:flex; gap:24px; flex-wrap:wrap;">
  {{-- XLSX (con formateo) --}}
  <form method="GET" action="{{ route('reportes.carteras.exportData') }}">
    <div style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
      <div>
        <label>Etiqueta del archivo (p.ej. OCTUBRE25)</label><br>
        <input type="text" name="tag" value="{{ $tag }}" style="padding:8px;">
      </div>

      <div>
        <label>Mes de trabajo (YYYY-MM)</label><br>
        <input type="month" name="mes" value="{{ $mes }}" style="padding:8px;">
      </div>

      <div>
        <button class="btn" type="submit">Generar <strong>REPORTE DATA</strong></button>
        <div style="font-size:12px; color:#666; margin-top:6px;">
          Nombre: <code>REPORTE {{ $tag }} ESCALL.xlsx</code>
        </div>
      </div>
    </div>
  </form>

  {{-- CSV rápido (streaming) --}}
  <form method="GET" action="{{ route('reportes.carteras.exportDataCsv') }}">
    <div style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
      <div>
        <label>Etiqueta del archivo (p.ej. OCTUBRE25)</label><br>
        <input type="text" name="tag" value="{{ $tag }}" style="padding:8px;">
      </div>

      <div>
        <label>Mes de trabajo (YYYY-MM)</label><br>
        <input type="month" name="mes" value="{{ $mes }}" style="padding:8px;">
      </div>

      <div>
        <button class="btn" type="submit">Generar <strong>REPORTE DATA (CSV)</strong></button>
        <div style="font-size:12px; color:#666; margin-top:6px;">
          Nombre: <code>REPORTE {{ $tag }} ESCALL.csv</code> — más veloz para volúmenes grandes
        </div>
      </div>
    </div>
  </form>

  {{-- resources/views/reportes/carteras.blade.php --}}
  <form method="GET" action="{{ route('reportes.tec.data') }}" style="margin-top:12px;">
    <div style="display:flex; gap:12px; align-items:end; flex-wrap:wrap;">
      <div>
        <label>Mes de trabajo (YYYY-MM)</label><br>
        <input type="month" name="mes" value="{{ $mes ?? now()->format('Y-m') }}" style="padding:8px;">
      </div>
      <div>
        <button class="btn" type="submit">
          Generar <strong>REPORTE DATA TEC CENTER</strong>
        </button>
      </div>
    </div>
  </form>


</div>

<p style="margin-top:10px; font-size:12px; color:#666;">
  El <em>REPORTE DATA</em> usa el mes seleccionado (YYYY-MM). MC/SITUACIÓN/TELEFONOS se calcula con la mejor gestión
  <strong>fuera</strong> de ese mes; “Última”, “Mejor” e “Intensidad” se calculan <strong>dentro</strong> de ese mes.
</p>

@endsection
