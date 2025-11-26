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

        // backend igual: tabla gestiones, fecha_gestion
        $base = DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', '=', 'TEC CENTER')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx);

        // solo conteo, sin selectRaw ni paginate
        $tecCount = (clone $base)->count();

        // mandamos a la vista general de reportes
        return view('reportes.index', [
            'tecFi'    => $fi,
            'tecFf'    => $ff,
            'tecCount' => $tecCount,
        ]);
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
