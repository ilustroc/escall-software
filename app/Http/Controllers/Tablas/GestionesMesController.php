<?php

namespace App\Http\Controllers\Tablas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class GestionesMesController extends Controller
{
    public function index(Request $request): View
    {
        // =========================
        // 1) BLOQUE MENSUAL (por asesor)
        // =========================
        $mes = $request->string('mes')->toString();
        if (!preg_match('/^\d{4}\-\d{2}$/', $mes ?? '')) {
            $mes = Carbon::now()->format('Y-m');
        }
        [$y, $m] = explode('-', $mes);
        $start = Carbon::create((int)$y, (int)$m, 1)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        // Días del mes
        $days = [];
        $labels = [];
        $c = $start->copy();
        while ($c->lte($end)) {
            $days[]   = $c->toDateString();     // 'YYYY-MM-DD'
            $labels[] = $c->format('j-M');      // '1-Oct'
            $c->addDay();
        }

        DB::connection()->disableQueryLog();
        $rows = DB::table('gestiones')
            ->selectRaw('COALESCE(NULLIF(TRIM(nombre), ""), "SIN NOMBRE") AS agente, DATE(fecha_gestion) AS dia, COUNT(*) AS total')
            ->whereBetween('fecha_gestion', [$start->toDateString(), $end->toDateString()])
            ->groupBy('agente', DB::raw('DATE(fecha_gestion)'))
            ->get();

        // Pivot [agente][dia]=total
        $table = [];
        foreach ($rows as $r) { $table[$r->agente][$r->dia] = (int)$r->total; }

        // Totales por día
        $totalDia = array_fill_keys($days, 0);
        foreach ($table as $byDay) {
            foreach ($days as $d) { $totalDia[$d] += $byDay[$d] ?? 0; }
        }

        // Oculta columnas con total 0
        $visibleDays = [];
        $visibleLabels = [];
        foreach ($days as $i => $d) {
            if (($totalDia[$d] ?? 0) > 0) { $visibleDays[] = $d; $visibleLabels[] = $labels[$i]; }
        }

        // Ordena agentes por total visible desc
        uasort($table, function ($a, $b) use ($visibleDays) {
            $sa=0; $sb=0;
            foreach ($visibleDays as $d) { $sa += $a[$d] ?? 0; $sb += $b[$d] ?? 0; }
            return $sb <=> $sa;
        });

        $totalGeneral = 0;
        foreach ($visibleDays as $d) { $totalGeneral += $totalDia[$d] ?? 0; }

        // =========================
        // 2) BLOQUE SEMANAL (lun-vie) COSECHAS & RANGOS
        // =========================
        $week = $request->string('week')->toString();
        if (!preg_match('/^\d{4}-W\d{2}$/', $week ?? '')) {
            $week = Carbon::now()->format('o-\WW'); // ISO year-week
        }
        [$isoYear, $isoWeek] = sscanf($week, '%d-W%d');
        $wMonday = Carbon::now()->setISODate($isoYear, $isoWeek)->startOfDay();

        $wDays = [];  // 'YYYY-MM-DD'
        $wLabels = []; // 'lunes 20'...
        for ($i=0; $i<5; $i++) {
            $d = $wMonday->copy()->addDays($i);
            $wDays[]   = $d->toDateString();
            $wLabels[] = ([
                'Monday'=>'lunes', 'Tuesday'=>'martes', 'Wednesday'=>'miércoles',
                'Thursday'=>'jueves', 'Friday'=>'viernes'
            ][$d->englishDayOfWeek] ?? $d->format('D')) . ' ' . $d->format('j');
        }
        $wFrom = $wDays[0] . ' 00:00:00';
        $wTo   = end($wDays) . ' 23:59:59';

        $caseRango = "
            CASE
              WHEN d.deuda_capital >= 50000 THEN '1.[50K - MAS]'
              WHEN d.deuda_capital >= 20000 THEN '2.[20K - 50K]'
              WHEN d.deuda_capital >= 10000 THEN '3.[10K - 20K]'
              WHEN d.deuda_capital >=  5000 THEN '4.[5K - 10K]'
              WHEN d.deuda_capital >=  1000 THEN '5.[1K - 5K]'
              WHEN d.deuda_capital >=   501 THEN '6.[501 - 1K]'
              ELSE '7.[0 - 500]'
            END
        ";

        $wRows = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->selectRaw("
                d.cartera,
                d.cosecha,
                DATE(g.fecha_gestion) AS dia,
                COUNT(*) AS total,
                {$caseRango} AS rango
            ")
            ->whereBetween('g.fecha_gestion', [$wFrom, $wTo])
            ->groupBy('d.cartera', 'd.cosecha', DB::raw('DATE(g.fecha_gestion)'), DB::raw($caseRango))
            ->get();

        $wCarteras = ['KP INVEST','TEC CENTER','IMPULSE','COOPERATIVA SAN FRANCISCO JAVIER'];
        $wCarteraLabel = [
            'KP INVEST' => 'KPI',
            'TEC CENTER' => 'ALTAMIRA',
            'IMPULSE' => 'IMPULSE',
            'COOPERATIVA SAN FRANCISCO JAVIER' => 'COOPERATIVA SAN FRANCISCO JAVIER',
        ];

        $wPorCosecha = []; $wPorRango = [];
        foreach ($wCarteras as $c) { $wPorCosecha[$c]=[]; $wPorRango[$c]=[]; }

        foreach ($wRows as $r) {
            $c = trim((string)$r->cartera);
            if (!isset($wPorCosecha[$c])) continue;
            $co = $r->cosecha ?: 'SIN COSECHA';
            $d  = $r->dia;
            $n  = (int)$r->total;
            $rg = $r->rango;

            $wPorCosecha[$c][$co][$d] = ($wPorCosecha[$c][$co][$d] ?? 0) + $n;
            $wPorRango[$c][$rg][$d]   = ($wPorRango[$c][$rg][$d]   ?? 0) + $n;
        }

        // --- ORDEN PERSONALIZADO DE COSECHAS POR CARTERA ---
        $customCosechaOrder = [
            // KPI -> cartera real: KP INVEST
            'KP INVEST' => [
                'BBVA1','BBVA2','BBVA3','BBVA4','BBVA5','BBVA6','BBVA7','BBVA8',
                'CAJAAQP1','CAJAAQP2','CAJAAQP3',
                'CONFIANZA','CONFIANZA_2','CONFIANZA_3','CONFIANZA_4','CONFIANZA_5',
                'CONFIANZA_6','CONFIANZA_7','CONFIANZA_8','CONFIANZA_9','CONFIANZA_10',
                'CONFIANZA_11','CONFIANZA_12',
                'COMPARTAMOS_1',
            ],

            // IMPULSE
            'TEC CENTER' => [
                'BBVA','IBK TEC 01','IBK TEC 02','IBK TEC 03','IBK TEC 04','TARJETA OH'
            ],
            
            // BBVA
            'IMPULSE' => [
                'CAJAAQP1_','CAJAAQP2_','CONFIANZA_','CONFIANZA_2_',
            ],
        ];

        // Aplica el orden personalizado si existe; si no, ordena por total semanal desc
        foreach ($wPorCosecha as $cartera => &$mat) {
            if (isset($customCosechaOrder[$cartera])) {
                // 1) Coloca primero las cosechas en el orden indicado
                $ordered = [];
                foreach ($customCosechaOrder[$cartera] as $key) {
                    if (array_key_exists($key, $mat)) {
                        $ordered[$key] = $mat[$key];
                        unset($mat[$key]);
                    }
                }
                // 2) El resto (si hubiera) va al final, ordenado por total semanal desc
                uasort($mat, function($a, $b) use ($wDays) {
                    $sa = 0; $sb = 0;
                    foreach ($wDays as $d) { $sa += $a[$d] ?? 0; $sb += $b[$d] ?? 0; }
                    return $sb <=> $sa;
                });
                // 3) Fusión final: personalizados primero + restantes
                $mat = $ordered + $mat;
            } else {
                // Sin orden personalizado: por total semanal desc
                uasort($mat, function($a, $b) use ($wDays) {
                    $sa = 0; $sb = 0;
                    foreach ($wDays as $d) { $sa += $a[$d] ?? 0; $sb += $b[$d] ?? 0; }
                    return $sb <=> $sa;
                });
            }
        }
        unset($mat);

        // Orden fijo de rangos
        $rangosOrden = [
            '1.[50K - MAS]','2.[20K - 50K]','3.[10K - 20K]',
            '4.[5K - 10K]','5.[1K - 5K]','6.[501 - 1K]','7.[0 - 500]'
        ];
        foreach ($wPorRango as $c=>&$mat){
            $ord=[]; foreach($rangosOrden as $r) $ord[$r] = $mat[$r] ?? [];
            $mat = $ord;
        } unset($mat);

        // Render
        return view('tablas.index', [
            // mensual
            'mes'          => $mes,
            'days'         => $visibleDays,
            'labels'       => $visibleLabels,
            'table'        => $table,
            'totalDia'     => $totalDia,
            'totalGeneral' => $totalGeneral,
            'omitidos'     => count($labels) - count($visibleLabels),
            // semanal
            'week'         => $week,
            'wDays'        => $wDays,
            'wLabels'      => $wLabels,
            'wCarteras'    => $wCarteras,
            'wCarteraLabel'=> $wCarteraLabel,
            'wPorCosecha'  => $wPorCosecha,
            'wPorRango'    => $wPorRango,
        ]);
    }
}
