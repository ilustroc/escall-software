<?php

namespace App\Http\Controllers\Tablas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class GestionesSemanalController extends Controller
{
    // Cartera -> etiqueta para el título
    private array $carteraLabel = [
        'KP INVEST'                          => 'KPI',
        'TEC CENTER'                         => 'ALTAMIRA',
        'IMPULSE'                            => 'IMPULSE',
    ];

    // Orden de rangos (texto exacto que imprimimos)
    private array $rangos = [
        '1.[50K - MAS]',
        '2.[20K - 50K]',
        '3.[10K - 20K]',
        '4.[5K - 10K]',
        '5.[1K - 5K]',
        '6.[501 - 1K]',
        '7.[0 - 500]',
    ];

    public function index(Request $request): View
    {
        // ===== Selector de semana (input type="week": 2025-W42). Default: semana actual
        $week = $request->string('week')->toString();
        if (!preg_match('/^\d{4}-W\d{2}$/', $week ?? '')) {
            $week = Carbon::now()->format('o-\WW'); // ISO year-week
        }
        [$isoYear, $isoWeek] = sscanf($week, '%d-W%d');

        $monday = Carbon::now()->setISODate($isoYear, $isoWeek)->startOfDay(); // lunes ISO
        $days   = [];
        $labels = [];
        for ($i = 0; $i < 5; $i++) {                // solo lunes-viernes
            $d = $monday->copy()->addDays($i);
            $days[]   = $d->toDateString();        // 'YYYY-MM-DD'
            $labels[] = $this->labelDia($d);
        }
        $from = $days[0] . ' 00:00:00';
        $to   = end($days) . ' 23:59:59';

        // ===== Traer conteos en UNA query: por cartera/cosecha/día y por RANGO capital
        $caseRango = "
            CASE
                WHEN d.deuda_capital >= 50000 THEN '1.[50K - MAS]'
                WHEN d.deuda_capital >= 20000 THEN '2.[20K - 50K]'
                WHEN d.deuda_capital >= 10000 THEN '3.[10K - 20K]'
                WHEN d.deuda_capital >= 5000  THEN '4.[5K - 10K]'
                WHEN d.deuda_capital >= 1000  THEN '5.[1K - 5K]'
                WHEN d.deuda_capital >= 501   THEN '6.[501 - 1K]'
                ELSE '7.[0 - 500]'
            END
        ";

        $rows = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->selectRaw("
                d.cartera,
                d.cosecha,
                DATE(g.fecha_gestion) as dia,
                COUNT(*) as total,
                {$caseRango} as rango
            ")
            ->whereBetween('g.fecha_gestion', [$from, $to])
            ->groupBy('d.cartera', 'd.cosecha', DB::raw('DATE(g.fecha_gestion)'), DB::raw($caseRango))
            ->get();

        // ===== Armar pivots por carteras
        $carteras = array_keys($this->carteraLabel);
        $porCosecha = [];   // [cartera][cosecha][dia] = total
        $porRango   = [];   // [cartera][rango][dia]   = total

        foreach ($carteras as $c) { $porCosecha[$c] = []; $porRango[$c] = []; }

        foreach ($rows as $r) {
            $c = trim((string)$r->cartera);
            if (!isset($porCosecha[$c])) continue; // ignorar otras carteras

            $co = $r->cosecha ?: 'SIN COSECHA';
            $d  = $r->dia;
            $n  = (int)$r->total;
            $rg = $r->rango;

            // cosechas
            $porCosecha[$c][$co][$d] = ($porCosecha[$c][$co][$d] ?? 0) + $n;

            // rangos
            $porRango[$c][$rg][$d] = ($porRango[$c][$rg][$d] ?? 0) + $n;
        }

        // ordenar filas: cosechas por total desc, rangos por orden fijo
        foreach ($porCosecha as $c => &$mat) {
            uasort($mat, function($a, $b) use ($days) {
                $sa = 0; $sb = 0;
                foreach ($days as $d) { $sa += $a[$d] ?? 0; $sb += $b[$d] ?? 0; }
                return $sb <=> $sa;
            });
        } unset($mat);

        // garantizar filas de rangos en orden y presentes aunque sean 0
        foreach ($porRango as $c => &$mat) {
            $ordered = [];
            foreach ($this->rangos as $rg) $ordered[$rg] = $mat[$rg] ?? [];
            $mat = $ordered;
        } unset($mat);

        return view('tablas.semanales', [
            'week'        => $week,
            'days'        => $days,
            'labels'      => $labels,
            'carteras'    => $carteras,
            'carteraLabel'=> $this->carteraLabel,
            'porCosecha'  => $porCosecha,
            'porRango'    => $porRango,
        ]);
    }

    private function labelDia(Carbon $d): string
    {
        // lunes 20, martes 21...
        $map = ['Monday'=>'lunes', 'Tuesday'=>'martes', 'Wednesday'=>'miércoles', 'Thursday'=>'jueves', 'Friday'=>'viernes'];
        $name = $map[$d->englishDayOfWeek] ?? $d->format('D');
        return $name . ' ' . $d->format('j');
    }
}
