<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;
use App\Exports\ReporteImpulseExport;
use Maatwebsite\Excel\Facades\Excel;

class ReporteImpulseController extends Controller
{
    public function index(Request $request): View
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $start = $fi . ' 00:00:00';
        $endEx = Carbon::parse($ff)->addDay()->startOfDay()->toDateTimeString();

        $base  = $this->baseQuery($start, $endEx);

        $count = $base->count();

        return view('reportes.index', [
            'impFi'    => $fi,
            'impFf'    => $ff,
            'impCount' => $count,
        ]);
    }

    public function export(Request $request)
    {
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        // Equipo: 2 o 3 (default 2 si no viene nada)
        $equipo = (int) $request->query('equipo', 2);
        if (!in_array($equipo, [2,3])) {
            $equipo = 2;
        }

        // Usamos la fecha de inicio para el nombre (como en tu ejemplo)
        $dia = Carbon::parse($fi)->format('Ymd');

        $filename = "Gestiones Cartera Propia - {$equipo} Escall {$dia}.xlsx";

        return Excel::download(
            new ReporteImpulseExport($fi, $ff, $equipo),
            $filename
        );
    }

    // ----------------- helpers -----------------
    private function isDate(?string $s): bool
    {
        if (!$s) return false;
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $s);
    }

    private function baseQuery(string $start, string $endEx)
    {
        // Join gestiones + data, cartera contiene IMPULSE, fecha_gestion en [start, endEx)
        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', 'like', '%IMPULSE%')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx);
    }
}
