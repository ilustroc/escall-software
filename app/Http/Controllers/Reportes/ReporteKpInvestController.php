<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Exports\ReporteKpInvestExport;
use Maatwebsite\Excel\Facades\Excel;

class ReporteKpInvestController extends Controller
{
    public function index(Request $request): View
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $start = $fi.' 00:00:00';
        $endEx = Carbon::parse($ff)->addDay()->startOfDay()->toDateTimeString();

        $base = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', '=', 'KP INVEST')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx);

        $count = (clone $base)->count();

        $rows = (clone $base)
            ->selectRaw("
                g.fecha_gestion                                              AS fecha_gest,
                CASE WHEN CHAR_LENGTH(g.dni)=8 THEN 'DNI'
                     WHEN CHAR_LENGTH(g.dni)=11 THEN 'RUC'
                     ELSE 'DNI' END                                          AS tip_doc,
                g.dni                                                        AS num_doc,
                d.titular                                                    AS cliente,
                g.telefono                                                   AS telefono,
                'LLAMADA ENTRATE'                                            AS accion,
                g.tipificacion                                               AS tipificacion,
                g.status                                                     AS estado,
                g.observacion                                                AS gestion,
                g.fecha_pago                                                 AS fecha_promesa,
                g.monto_pago                                                 AS monto_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni')
            ->paginate(100)
            ->appends(['fi'=>$fi,'ff'=>$ff]);

        return view('reportes.kp', compact('fi','ff','rows','count'));
    }

    public function export(Request $request)
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');
        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $sufijo = ($fi === $ff) ? Carbon::parse($fi)->format('Ymd')
                                : Carbon::parse($fi)->format('Ymd') . '_' . Carbon::parse($ff)->format('Ymd');

        $filename = "Reporte KP INVEST {$sufijo}.xlsx";
        return Excel::download(new ReporteKpInvestExport($fi, $ff), $filename);
    }

    private function isDate(?string $s): bool
    {
        return (bool) $s && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
    }
}
