<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Exports\ReporteTecCenterExport;
use Maatwebsite\Excel\Facades\Excel;

class ReporteTecCenterController extends Controller
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
            ->where('d.cartera', '=', 'TEC CENTER')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx);

        $count = (clone $base)->count();

        $rows = (clone $base)
            ->selectRaw("
                g.dni                                                            AS dni,
                d.codigo                                                         AS ncuenta,
                d.cosecha                                                        AS cartera,
                g.fecha_gestion                                                  AS fecha_gestion,
                g.status                                                         AS contacto,
                g.tipificacion                                                   AS resultado,
                COALESCE(NULLIF(TRIM(g.nombre),''), 'SIN NOMBRE')                AS gestor,
                g.observacion                                                    AS comentario,
                g.telefono                                                       AS telefono
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni')
            ->paginate(100)
            ->appends(['fi'=>$fi,'ff'=>$ff]);

        return view('reportes.tec-center', compact('fi','ff','rows','count'));
    }

    public function export(Request $request)
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');
        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        // Sufijo (DIA.MES) o rango (dd.mm-dd.mm)
        $suf = ($fi === $ff)
            ? Carbon::parse($fi)->format('d.m')
            : Carbon::parse($fi)->format('d.m') . '-' . Carbon::parse($ff)->format('d.m');

        $filename = "FRMT_GESTIONES CP - ({$suf}).xlsx";
        return Excel::download(new ReporteTecCenterExport($fi, $ff), $filename);
    }

    private function isDate(?string $s): bool
    {
        return (bool) $s && preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
    }
}
