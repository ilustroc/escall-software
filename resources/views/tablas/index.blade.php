@extends('layouts.app')
@section('title','Gestiones por asesor (mes)')

@section('content')
<div class="page-metrics">

    <h2 class="page-title">Gestiones por asesor – Mes</h2>

    {{-- FILTROS DEL MES --}}
    <form method="GET" action="{{ route('tablas.index') }}" class="filter-bar">
        <div class="filter-group">
            <label>Mes:</label>
            <input type="month" name="mes" value="{{ $mes }}">
        </div>

        <button class="btn" type="submit">Filtrar</button>

        <div class="filter-group">
            <input type="text" id="filtroAgente" placeholder="Filtrar por agente...">
        </div>

        @if(($omitidos ?? 0) > 0)
            <span class="hint">(Ocultadas {{ $omitidos }} columnas sin datos)</span>
        @endif
    </form>

    {{-- RESUMEN RÁPIDO DEL MES --}}
    <div class="kpi-row">
        <div class="kpi-card">
            <span class="kpi-label">Total gestiones del mes</span>
            <span class="kpi-value">{{ number_format($totalGeneral, 0, ',', '.') }}</span>
        </div>
        <div class="kpi-card">
            <span class="kpi-label">Agentes</span>
            <span class="kpi-value">{{ count($table) }}</span>
        </div>
    </div>

    {{-- TABLA ÚNICA: TODO EL MES COMPLETO --}}
    <div class="table-block">
        <div class="table-block-header">
            <h3>Detalle mensual por agente</h3>
            <span class="table-block-sub">Gestiones diarias de todo el mes seleccionado</span>
        </div>

        <div class="table-container wide-x">
            <table id="tabla" class="data-table">
                <thead>
                    <tr>
                        <th class="sticky-left">AGENTE</th>
                        @foreach($labels as $lbl)
                            <th class="num">{{ $lbl }}</th>
                        @endforeach
                        <th class="num total-col">TOTAL MES</th>
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
                        <tr>
                            <td colspan="{{ count($days)+2 }}">Sin datos para el mes seleccionado.</td>
                        </tr>
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
    </div>

    {{-- ======= SECCIÓN SEMANAL (LUNES A VIERNES) ======= --}}
    <h3 class="section-title">Gestiones semanales (lunes a viernes)</h3>

    <form method="GET" action="{{ route('tablas.index') }}" class="filter-bar">
        <input type="hidden" name="mes" value="{{ $mes }}">
        <div class="filter-group">
            <label>Semana:</label>
            <input type="week" name="week" value="{{ $week }}">
        </div>
        <button class="btn" type="submit">Filtrar</button>
        <span class="hint">Mostrando: {{ $wLabels[0] ?? '' }} → {{ $wLabels[4] ?? '' }}</span>
    </form>

    @php
        $sumRow = function($row,$days){ $t=0; foreach($days as $d){ $t += $row[$d] ?? 0; } return $t; };
        $sumCol = function($tbl,$days){
            $out = array_fill_keys($days,0);
            foreach($tbl as $r){
                foreach($days as $d){
                    $out[$d]+= $r[$d]??0;
                }
            }
            return $out;
        };
    @endphp

    {{-- BLOQUES POR CARTERA: cada cartera tiene COSECHAS y RANGOS --}}
    @foreach($wCarteras as $cartera)
        @php
            $tag    = $wCarteraLabel[$cartera] ?? $cartera;
            $left   = $wPorCosecha[$cartera] ?? [];
            $right  = $wPorRango[$cartera]   ?? [];
            $totL   = $sumCol($left,  $wDays);
            $totR   = $sumCol($right, $wDays);
        @endphp

        <div class="cartera-block">
            <div class="cartera-header">
                <h4>{{ strtoupper($tag) }}</h4>
                <span>Cartera semanal ({{ $wLabels[0] ?? '' }} – {{ $wLabels[4] ?? '' }})</span>
            </div>

            <div class="cartera-grid">
                {{-- COSECHAS --}}
                <div class="table-container no-scroll-x">
                    <div class="table-title">Gestiones diarias por cosechas</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="sticky-left">COSECHAS</th>
                                @foreach($wLabels as $lbl)
                                    <th class="num">{{ $lbl }}</th>
                                @endforeach
                                <th class="num total-col">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($left as $cosecha => $row)
                                <tr>
                                    <td class="sticky-left strong">{{ $cosecha }}</td>
                                    @foreach($wDays as $d)
                                        <td class="num">{{ $row[$d] ?? 0 }}</td>
                                    @endforeach
                                    <td class="num total-col strong">{{ $sumRow($row,$wDays) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($wDays)+2 }}">Sin datos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="sticky-left">TOTAL</th>
                                @foreach($wDays as $d)
                                    <th class="num">{{ $totL[$d] }}</th>
                                @endforeach
                                <th class="num grand-total">{{ array_sum($totL) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- RANGOS --}}
                <div class="table-container no-scroll-x">
                    <div class="table-title">Gestiones diarias por rango</div>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th class="sticky-left">RANGO</th>
                                @foreach($wLabels as $lbl)
                                    <th class="num">{{ $lbl }}</th>
                                @endforeach
                                <th class="num total-col">TOTAL</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($right as $rg => $row)
                                <tr>
                                    <td class="sticky-left strong">{{ $rg }}</td>
                                    @foreach($wDays as $d)
                                        <td class="num">{{ $row[$d] ?? 0 }}</td>
                                    @endforeach
                                    <td class="num total-col strong">{{ $sumRow($row,$wDays) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($wDays)+2 }}">Sin datos.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="sticky-left">TOTAL</th>
                                @foreach($wDays as $d)
                                    <th class="num">{{ $totR[$d] }}</th>
                                @endforeach
                                <th class="num grand-total">{{ array_sum($totR) }}</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div> {{-- .cartera-grid --}}
        </div> {{-- .cartera-block --}}
    @endforeach

</div> {{-- .page-metrics --}}

{{-- estilos y script se quedan igual --}}
<style>
    :root {
        --tbl-border: #e5e7eb;
        --tbl-head-bg: #f9fafb;
        --tbl-head-text: #4b5563;
        --tbl-stripe-bg: #fdfdfd;
        --tbl-sticky-bg: #ffffff;
        --tbl-total-bg: #eef2ff;
        --tbl-grand-total-bg: #e0ecff;
        --tbl-hover-bg: #f0f5ff;
    }

    /* usa todo el ancho del contenido */
    .page-metrics{
        width:100%;
        max-width:none;
        margin:0;
        padding:8px 8px 24px;
        background:#ffffff;
    }

    .page-title{
        margin:0 0 10px;
        font-size:20px;
        font-weight:700;
    }

    .section-title{
        margin:24px 0 8px;
        font-size:18px;
        font-weight:700;
    }

    .filter-bar{
        margin: 12px 0 10px;
        display:flex;
        flex-wrap:wrap;
        gap:10px 16px;
        align-items:center;
        background:#f3f4f6;
        padding:8px 10px;
        border-radius:8px;
        border:1px solid #e5e7eb;
        font-size:13px;
    }
    .filter-group{
        display:flex;
        align-items:center;
        gap:6px;
    }
    .filter-bar label{
        font-weight:600;
    }
    .filter-bar input[type="month"],
    .filter-bar input[type="week"],
    .filter-bar input[type="text"]{
        padding:6px 8px;
        border-radius:6px;
        border:1px solid #d1d5db;
        font-size:13px;
    }
    .filter-bar .btn{
        padding:6px 12px;
        border-radius:6px;
        border:0;
        background:#2563eb;
        color:#fff;
        font-size:13px;
        font-weight:600;
        cursor:pointer;
    }
    .filter-bar .btn:hover{
        background:#1d4ed8;
    }
    .hint{
        font-size:12px;
        color:#6b7280;
    }

    .kpi-row{
        display:flex;
        flex-wrap:wrap;
        gap:12px;
        margin-bottom:8px;
    }
    .kpi-card{
        flex:0 0 160px;
        background:#f9fafb;
        border-radius:10px;
        padding:8px 10px;
        border:1px solid #e5e7eb;
    }
    .kpi-label{
        display:block;
        font-size:11px;
        color:#6b7280;
        margin-bottom:2px;
    }
    .kpi-value{
        font-size:18px;
        font-weight:700;
        color:#111827;
    }

    .table-block{
        margin-top:10px;
    }
    .table-block-header h3{
        margin:0;
        font-size:15px;
        font-weight:700;
    }
    .table-block-sub{
        font-size:12px;
        color:#6b7280;
    }

    .table-container{
        border:1px solid var(--tbl-border);
        border-radius:8px;
        box-shadow:0 1px 3px rgba(0,0,0,0.04);
        margin-top:6px;
        overflow:auto;
    }
    .table-container.wide-x{
        max-width:100%;
    }
    .table-container.no-scroll-x{
        overflow-x:hidden;
    }

    .table-title{
        background:var(--tbl-head-bg);
        padding:8px 10px;
        font-weight:700;
        border-bottom:1px solid var(--tbl-border);
        font-size:13px;
    }

    .data-table{
        border-collapse:collapse;
        width:100%;
        font-size:12px;
    }
    .data-table th,
    .data-table td{
        padding:5px 6px;
        border-bottom:1px solid var(--tbl-border);
        white-space:nowrap;
    }
    .data-table thead th{
        background:var(--tbl-head-bg);
        color:var(--tbl-head-text);
        text-transform:uppercase;
        font-size:10px;
        letter-spacing:0.4px;
        font-weight:700;
        border-bottom-width:2px;
        position:sticky;
        top:0;
        z-index:2;
    }
    .data-table tbody tr:nth-child(even) td{
        background:var(--tbl-stripe-bg);
    }
    .data-table tbody tr:hover td{
        background:var(--tbl-hover-bg);
    }
    .data-table tfoot th{
        background:var(--tbl-total-bg);
        font-weight:700;
        position:sticky;
        bottom:0;
        z-index:2;
    }

    .sticky-left{
        position:sticky;
        left:0;
        background:var(--tbl-sticky-bg);
        z-index:1;
        border-right:1px solid var(--tbl-border);
    }
    .data-table tbody tr:nth-child(even) .sticky-left{
        background:var(--tbl-stripe-bg);
    }
    .data-table tbody tr:hover .sticky-left{
        background:var(--tbl-hover-bg);
    }
    .data-table thead .sticky-left{
        z-index:3;
        background:var(--tbl-head-bg);
    }
    .data-table tfoot .sticky-left{
        z-index:3;
        background:var(--tbl-total-bg);
    }

    .num{
        text-align:right;
        font-variant-numeric:tabular-nums;
    }
    .strong{ font-weight:600; }
    .total-col{
        background:var(--tbl-total-bg);
        font-weight:700;
        border-left:1px solid var(--tbl-border);
    }
    .grand-total{
        background:var(--tbl-grand-total-bg)!important;
        color:#111827;
        font-weight:800;
    }

    .cartera-block{
        margin-top:18px;
        padding:10px 12px 14px;
        border-radius:10px;
        border:1px solid #e5e7eb;
        background:#fafafa;
    }
    .cartera-header{
        display:flex;
        justify-content:space-between;
        align-items:baseline;
        margin-bottom:6px;
    }
    .cartera-header h4{
        margin:0;
        font-size:15px;
        font-weight:700;
    }
    .cartera-header span{
        font-size:12px;
        color:#6b7280;
    }

    .cartera-grid{
        display:grid;
        grid-template-columns:1fr;
        gap:12px;
    }
    @media (min-width: 992px){
        .cartera-grid{
            grid-template-columns: minmax(0,1.2fr) minmax(0,1fr);
        }
    }

    @media (max-width:768px){
        .page-metrics{
            padding:8px 4px 24px;
        }
        .filter-bar{
            flex-direction:column;
            align-items:flex-start;
        }
        .kpi-card{
            flex:1 1 100%;
        }
    }
</style>

<script>
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
