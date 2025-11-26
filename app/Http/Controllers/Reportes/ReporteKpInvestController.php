<?php

namespace App\Http\Controllers\Reportes;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ReporteKpInvestExport;
use App\Http\Controllers\Controller;

class ReporteKpInvestController extends Controller
{
    public function index(Request $request): View
    {
        // Fechas (defaults: hoy)
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $start = $fi . ' 00:00:00';
        $endEx = Carbon::parse($ff)->addDay()->startOfDay()->toDateTimeString(); // fin exclusivo

        // Query base reutilizable
        $base = $this->baseQuery($start, $endEx);

        // SOLO conteo
        $kpCount = $base->count();

        return view('reportes.index', [
            'kpFi'    => $fi,
            'kpFf'    => $ff,
            'kpCount' => $kpCount,
        ]);
    }

    public function export(Request $request)
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $sufijo = ($fi === $ff)
            ? Carbon::parse($fi)->format('Ymd')
            : Carbon::parse($fi)->format('Ymd') . '_' . Carbon::parse($ff)->format('Ymd');

        $filename = "Reporte KP INVEST {$sufijo}.xlsx";

        return Excel::download(new ReporteKpInvestExport($fi, $ff), $filename);
    }

    private function isDate(?string $s): bool
    {
        if (!$s) return false;
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
    }

    private function baseQuery(string $start, string $endEx)
    {
        return DB::table('gestiones as g')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx);
    }
}
