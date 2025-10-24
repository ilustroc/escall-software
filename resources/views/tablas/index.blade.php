@extends('layouts.app')
@section('title','Gestiones por asesor (mes)')

@section('content')
<h2>Gestiones por asesor – Mes</h2>

<form method="GET" action="{{ route('tablas.index') }}" class="filter-bar">
  <label>Mes:&nbsp;</label>
  <input type="month" name="mes" value="{{ $mes }}">
  <button class="btn" type="submit">Filtrar</button>

  <input type="text" id="filtroAgente" placeholder="Filtrar por agente..." style="padding:8px; min-width:220px;">
  @if(($omitidos ?? 0) > 0)
    <span style="font-size:12px; color:#666;">(Ocultadas {{ $omitidos }} columnas sin datos)</span>
  @endif
</form>

<div class="table-container">
  <table id="tabla" class="data-table">
    <thead>
      <tr>
        <th class="sticky-left">AGENTE</th>
        @foreach($labels as $lbl)
          <th class="num">{{ $lbl }}</th>
        @endforeach
        <th class="num total-col">TOTAL</th>
      </tr>
    </thead>
    <tbody>
      @forelse($table as $agente => $byDay)
        @php $rowTotal = 0; @endphp
        <tr class="tr">
          <td class="sticky-left strong">{{ $agente }}</td>
          @foreach($days as $d)
            @php $v = $byDay[$d] ?? 0; $rowTotal += $v; @endphp
            <td class="num">{{ number_format($v, 0, ',', '.') }}</td>
          @endforeach
          <td class="num total-col strong">{{ number_format($rowTotal, 0, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="{{ count($days)+2 }}">Sin datos para el mes seleccionado.</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <th class="sticky-left">TOTAL DÍA</th>
        @foreach($days as $d)
          <th class="num">{{ number_format($totalDia[$d] ?? 0, 0, ',', '.') }}</th>
        @endforeach
        <th class="num grand-total">{{ number_format($totalGeneral, 0, ',', '.') }}</th>
      </tr>
    </tfoot>
  </table>
</div>

<p style="margin-top:-16px; margin-bottom: 16px; font-size:12px; color:#666;">
  Consejo: la cabecera y la columna de “AGENTE” quedan fijas; desplázate horizontalmente para ver todos los días con datos.
</p>
<hr style="margin:18px 0;">

<h3 style="margin:0 0 8px 0; font-size:18px; font-weight:700;">Gestiones semanales (lunes a viernes)</h3>

<form method="GET" action="{{ route('tablas.index') }}" class="filter-bar">
  {{-- preserva el mes actual --}}
  <input type="hidden" name="mes" value="{{ $mes }}">
  <label>Semana:&nbsp;</label>
  <input type="week" name="week" value="{{ $week }}">
  <button class="btn" type="submit">Filtrar</button>
  <span style="font-size:12px; color:#666;">Mostrando: {{ $wLabels[0] ?? '' }} → {{ $wLabels[4] ?? '' }}</span>
</form>

@php
  // ayuditas (sin cambios)
  $sumRow = function($row,$days){ $t=0; foreach($days as $d){ $t += $row[$d] ?? 0; } return $t; };
  $sumCol = function($tbl,$days){ $out=array_fill_keys($days,0); foreach($tbl as $r){ foreach($days as $d){ $out[$d]+= $r[$d]??0; } } return $out; };
@endphp

<div class="table-grid">
  
  <div class="grid-column">
    @foreach($wCarteras as $cartera)
      @php
        $tag = $wCarteraLabel[$cartera] ?? $cartera;
        $left   = $wPorCosecha[$cartera] ?? [];
        $totL   = $sumCol($left,  $wDays);
      @endphp
      <div class="table-container"> <div class="table-title"> GESTIONES DIARIAS {{ $tag }} - COSECHAS</div>
        <table class="data-table">
          <thead>
            <tr>
              <th class="sticky-left">COSECHAS</th>
              @foreach($wLabels as $lbl) <th class="num">{{ $lbl }}</th> @endforeach
              <th class="num total-col">TOTAL</th>
            </tr>
          </thead>
          <tbody>
            @forelse($left as $cosecha => $row)
              <tr>
                <td class="sticky-left strong">{{ $cosecha }}</td>
                @foreach($wDays as $d) <td class="num">{{ $row[$d] ?? 0 }}</td> @endforeach
                <td class="num total-col strong">{{ $sumRow($row,$wDays) }}</td>
              </tr>
            @empty
              <tr><td colspan="{{ count($wDays)+2 }}">Sin datos.</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            <tr>
              <th class="sticky-left">TOTAL</th>
              @foreach($wDays as $d) <th class="num">{{ $totL[$d] }}</th> @endforeach
              <th class="num grand-total">{{ array_sum($totL) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    @endforeach
  </div>

  <div class="grid-column">
    @foreach($wCarteras as $cartera)
      @php
        $tag = $wCarteraLabel[$cartera] ?? $cartera;
        $right  = $wPorRango[$cartera]   ?? [];
        $totR   = $sumCol($right, $wDays);
      @endphp
      <div class="table-container"> <div class="table-title"> GESTIONES DIARIAS {{ $tag }} RANGO</div>
        <table class="data-table">
          <thead>
            <tr>
              <th class="sticky-left">RANGO</th>
              @foreach($wLabels as $lbl) <th class="num">{{ $lbl }}</th> @endforeach
              <th class="num total-col">TOTAL</th>
            </tr>
          </thead>
          <tbody>
            @forelse($right as $rg => $row)
              <tr>
                <td class="sticky-left strong">{{ $rg }}</td>
                @foreach($wDays as $d) <td class="num">{{ $row[$d] ?? 0 }}</td> @endforeach
                <td class="num total-col strong">{{ $sumRow($row,$wDays) }}</td>
              </tr>
            @empty
              <tr><td colspan="{{ count($wDays)+2 }}">Sin datos.</td></tr>
            @endforelse
          </tbody>
          <tfoot>
            @php $rTot = $sumCol($right, $wDays); @endphp
            <tr>
              <th class="sticky-left">TOTAL</th>
              @foreach($wDays as $d) <th class="num">{{ $rTot[$d] }}</th> @endforeach
              <th class="num grand-total">{{ array_sum($rTot) }}</th>
            </tr>
          </tfoot>
        </table>
      </div>
    @endforeach
  </div>

</div>
<style>
  :root {
    --tbl-border: #e0e0e0;
    --tbl-head-bg: #f8f9fa;
    --tbl-head-text: #555;
    --tbl-stripe-bg: #fdfdfd;
    --tbl-sticky-bg: #fff;
    --tbl-total-bg: #f4f6fa;
    --tbl-grand-total-bg: #e7f6ef;
    --tbl-hover-bg: #f0f5ff;
  }

  /* Contenedor del formulario de filtros */
  .filter-bar {
    margin: 16px 0;
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
    background: var(--tbl-head-bg);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--tbl-border);
  }
  .filter-bar label {
    font-weight: 600;
  }

  /* Contenedor de la tabla (para overflow y bordes) */
  .table-container {
    overflow: auto;
    max-width: 100%;
    border: 1px solid var(--tbl-border);
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    margin-bottom: 24px; /* <--- Se quita el margen para que lo controle el gap */
  }
  
  /* Título de la tabla (para Cosechas/Rango) */
  .table-title {
    background: var(--tbl-head-bg);
    padding: 10px 12px;
    font-weight: 700;
    border-bottom: 1px solid var(--tbl-border);
  }

  /* La tabla principal (para la de Agentes, la GRANDE de ARRIBA) */
  .data-table {
    border-collapse: collapse;
    width: 100%; 
    min-width: 800px;
    font-size: 13px;
  }

  /*
   * ESTA REGLA es para las tablas de Cosechas/Rango (las PEQUEÑAS de ABAJO)
   * Las hace de ancho automático (autoajuste de ancho).
  */
  .table-grid .data-table {
    width: auto; 
    min-width: 0;
  }

  .data-table th, 
  .data-table td {
    padding: 8px 10px;
    border-bottom: 1px solid var(--tbl-border);
    white-space: nowrap;
  }
  
  /* --- Estilos comunes de tablas (Cabecera, Cuerpo, Footer, Fija) --- */
  
  .data-table thead th {
    background: var(--tbl-head-bg);
    color: var(--tbl-head-text);
    position: sticky;
    top: 0;
    z-index: 2;
    text-transform: uppercase;
    font-size: 11px;
    letter-spacing: 0.5px;
    font-weight: 700;
    border-bottom-width: 2px;
  }
  .data-table tbody tr:nth-child(even) td {
    background: var(--tbl-stripe-bg);
  }
  .data-table tbody tr:hover td {
    background: var(--tbl-hover-bg);
  }
  .data-table tfoot th {
    background: var(--tbl-total-bg);
    font-weight: 700;
    position: sticky;
    bottom: 0;
    z-index: 2;
  }
  .sticky-left {
    position: sticky;
    left: 0;
    background: var(--tbl-sticky-bg);
    z-index: 1;
    border-right: 1px solid var(--tbl-border);
  }
  .data-table tbody tr:nth-child(even) .sticky-left {
    background: var(--tbl-stripe-bg);
  }
  .data-table tbody tr:hover .sticky-left {
    background: var(--tbl-hover-bg);
  }
  .data-table thead .sticky-left {
    z-index: 3;
    background: var(--tbl-head-bg);
  }
  .data-table tfoot .sticky-left {
    z-index: 3;
    background: var(--tbl-total-bg);
  }

  /* --- Clases de utilidad --- */

  .num {
    text-align: right;
    font-variant-numeric: tabular-nums;
  }
  .strong {
    font-weight: 600;
  }
  .total-col {
    background: var(--tbl-total-bg);
    font-weight: 700;
    border-left: 1px solid var(--tbl-border);
  }
  .grand-total {
    background: var(--tbl-grand-total-bg) !important;
    color: #000;
    font-weight: 800;
    font-size: 14px;
  }

  /*
   * ===============================================
   * ESTILOS DE LA GRILLA (2 COLUMNAS)
   * ===============================================
   */
  .table-grid {
    display: grid;
    grid-template-columns: auto auto; /* Dos columnas de ancho automático */
    justify-content: center;  /* Centra ambas columnas en la página */
    gap: 20px;
    margin-bottom: 20px;
    align-items: start; /* <-- Autoajuste de ALTURA */
  }

  /* Contenedor de cada columna */
  .grid-column {
    display: flex;
    flex-direction: column; /* Apila las tablas verticalmente */
    gap: 24px; /* Espacio entre las tablas de la misma columna */
  }
  
  /* Se quita el margen de la última tabla en cada columna */
  .grid-column .table-container:last-child {
      margin-bottom: 0;
  }

  /* En pantallas pequeñas, todo se vuelve una sola columna */
  @media (max-width: 1200px) {
    .table-grid {
      grid-template-columns: 1fr; /* Una sola columna */
      justify-items: stretch; /* Que ocupe todo el ancho */
    }
    .grid-column {
       width: 100%;
    }
    /* Hacemos que las tablas internas vuelvan a ocupar el 100% en móvil */
    .table-grid .data-table {
       width: 100%;
       min-width: 0;
    }
  }
</style>

<script>
  // Tu script de filtro (sin cambios)
  (function(){
    const input = document.getElementById('filtroAgente');
    if(!input) return;
    const rows = () => document.querySelectorAll('#tabla tbody tr');
    input.addEventListener('input', e=>{
      const q = e.target.value.trim().toLowerCase();
      rows().forEach(tr=>{
        const txt = (tr.querySelector('td.sticky-left')?.textContent || '').toLowerCase();
        tr.style.display = txt.includes(q) ? '' : 'none';
      });
    });
  })();
</script>
@endsection