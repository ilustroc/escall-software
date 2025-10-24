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
        // Fechas (defaults: hoy)
        $fi = $request->query('fi');
        $ff = $request->query('ff');

        if (!$this->isDate($fi)) $fi = Carbon::today()->toDateString();
        if (!$this->isDate($ff)) $ff = $fi;
        if ($ff < $fi) $ff = $fi;

        $start = $fi . ' 00:00:00';
        $endEx = Carbon::parse($ff)->addDay()->startOfDay()->toDateTimeString(); // fin exclusivo

        // Query base (se reutiliza)
        $base = $this->baseQuery($start, $endEx);

        // Conteo total y vista previa paginada
        $count = (clone $base)->count();
        $rows  = (clone $base)
            ->selectRaw("
                g.dni                          AS documento,
                d.titular                      AS cliente,
                g.tipificacion                 AS accion,
                g.status                       AS contacto,
                COALESCE(NULLIF(TRIM(g.nombre),''),'SIN NOMBRE') AS agente,
                d.codigo                       AS operacion,
                d.entidad                      AS entidad,
                g.fecha_gestion                AS fecha_gestion,
                g.telefono                     AS telefono,
                g.observacion                  AS observacion,
                g.monto_pago                   AS monto_promesa,
                CASE WHEN g.monto_pago IS NULL OR g.monto_pago=0 THEN 0 ELSE 1 END AS nro_cuotas,
                g.fecha_pago                   AS fecha_promesa,
                d.cartera                      AS cartera
            ")
            ->orderBy('g.fecha_gestion')
            ->paginate(100)
            ->appends(['fi'=>$fi,'ff'=>$ff]); // mantener filtros

        return view('reportes.impulse', compact('fi','ff','rows','count'));
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

        $filename = "Gestiones Cartera Propia Escall {$sufijo}.xlsx";
        return Excel::download(new ReporteImpulseExport($fi, $ff), $filename);
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
