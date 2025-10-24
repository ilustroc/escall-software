<?php

namespace App\Http\Controllers\Reportes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\ReporteDataCarterasExport;
use App\Exports\AsignacionTecCenterPlaceholderExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReporteCarterasController extends Controller
{
    public function index(Request $r)
    {
        $tag  = $r->query('tag',  'OCTUBRE25');
        $lote = $r->query('lote', '1809');
        $mes  = $r->query('mes', Carbon::now()->format('Y-m'));
        return view('reportes.carteras', compact('tag','lote','mes'));
    }

    /** XLSX (usa el mes fijo YYYY-MM) */
    public function exportData(Request $r)
    {
        $tag = $r->query('tag', 'OCTUBRE25');
        $mes = $r->query('mes');

        if (!preg_match('/^\d{4}\-\d{2}$/', (string)$mes)) {
            return back()->with('error', 'Mes inválido. Use formato YYYY-MM.');
        }

        $file = "REPORTE {$tag} ESCALL.xlsx";

        return Excel::download(
            (new ReporteDataCarterasExport)->forMonth($mes),
            $file
        );
    }

    /** CSV rápido (streaming) con el mismo mes fijo YYYY-MM */
    public function exportDataCsv(Request $r)
    {
        $tag = $r->query('tag', 'OCTUBRE25');
        $mes = $r->query('mes');

        if (!preg_match('/^\d{4}\-\d{2}$/', (string)$mes)) {
            return back()->with('error', 'Mes inválido. Use formato YYYY-MM.');
        }

        $start = Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $mes)->startOfMonth()->addMonth()->toDateString();

        [$sql, $bindings] = $this->sqlReporteData($start, $end);

        // desactiva buffering para stream real
        try {
            $pdo = DB::connection()->getPdo();
            @$pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        } catch (\Throwable $e) {}

        return response()->streamDownload(function () use ($sql, $bindings) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8

            fputcsv($out, [
                'CARTERA','DNI','TITULAR','CODIGO','ENTIDAD','COSECHA','PRODUCTO','SUB_PRODUCTO',
                'HISTORICO','DEPARTAMENTO','RANGO','DEUDA TOTAL','DEUDA CAPITAL','CAMPAÑA','%',
                'MC','SITUACION','TELEFONOS',
                'TELEFONO - ULTIMA GESTION','STATUS - ULTIMA GESTION','TIPIFICACION - ULTIMA GESTION',
                'OBSERVACION - ULTIMA GESTION','FECHA - ULTIMA GESTION',
                'TELEFONO - MEJOR GESTION','STATUS - MEJOR GESTION','TIPIFICACION - MEJOR GESTION',
                'OBSERVACION - MEJOR GESTION','FECHA - MEJOR GESTION',
                'INTENSIDAD',
            ]);

            foreach (DB::connection()->cursor($sql, $bindings) as $row) {
                $r = (array)$row;
                fputcsv($out, [
                    $r['CARTERA'] ?? null,
                    $r['DNI'] ?? null,
                    $r['TITULAR'] ?? null,
                    $r['CODIGO'] ?? null,
                    $r['ENTIDAD'] ?? null,
                    $r['COSECHA'] ?? null,
                    $r['PRODUCTO'] ?? null,
                    $r['SUB_PRODUCTO'] ?? null,
                    $r['HISTORICO'] ?? null,
                    $r['DEPARTAMENTO'] ?? null,
                    $r['RANGO'] ?? null,
                    $r['DEUDA TOTAL'] ?? null,
                    $r['DEUDA CAPITAL'] ?? null,
                    $r['CAMPAÑA'] ?? null,
                    $r['%'] ?? null,
                    $r['MC'] ?? null,
                    $r['SITUACION'] ?? null,
                    $r['TELEFONOS'] ?? null,
                    $r['TELEFONO - ULTIMA GESTION'] ?? null,
                    $r['STATUS - ULTIMA GESTION'] ?? null,
                    $r['TIPIFICACION - ULTIMA GESTION'] ?? null,
                    $r['OBSERVACION - ULTIMA GESTION'] ?? null,
                    $r['FECHA - ULTIMA GESTION'] ?? null,
                    $r['TELEFONO - MEJOR GESTION'] ?? null,
                    $r['STATUS - MEJOR GESTION'] ?? null,
                    $r['TIPIFICACION - MEJOR GESTION'] ?? null,
                    $r['OBSERVACION - MEJOR GESTION'] ?? null,
                    $r['FECHA - MEJOR GESTION'] ?? null,
                    $r['INTENSIDAD'] ?? 0,
                ]);
            }

            fclose($out);
        }, "REPORTE {$tag} ESCALL.csv", [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function exportAsignacionTec(Request $r)
    {
        $lote = $r->query('lote', '1809');
        return Excel::download(
            new AsignacionTecCenterPlaceholderExport,
            "FRMT_RG AGENCIAS EXTERNAS {$lote}.xlsx"
        );
    }

    /**
     * SQL (CTEs) para el reporte, basado en un MES FIJO [start, end).
     * MC/SITUACION/TELEFONOS = mejor gestión FUERA del mes objetivo.
     * Última/Mejor/Intensidad = SOLO dentro del mes objetivo.
     * DNI/CODIGO/TELs salen como TEXTO agregando un apóstrofe al final (…’).
     */
    private function sqlReporteData(string $start, string $end): array
    {
        $sql = <<<SQL
            WITH
            dset AS (
            SELECT DISTINCT dni FROM data
            ),
            best_all AS (
            SELECT
                g.dni,
                g.tipificacion,
                g.telefono,
                COALESCE(t.puntos, 999) AS puntos,
                t.mc,
                g.fecha_gestion,
                ROW_NUMBER() OVER (
                    PARTITION BY g.dni
                    ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
                ) AS rn
            FROM gestiones g
            JOIN dset ds ON ds.dni = g.dni
            LEFT JOIN tipificaciones t
                ON REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')
                =
                REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')
            -- EXCLUIR registros del mes objetivo
            WHERE (g.fecha_gestion < ? OR g.fecha_gestion >= ?)
            ),
            best AS (SELECT * FROM best_all WHERE rn = 1),

            ultima_all AS (
            SELECT
                g.dni,
                g.telefono       AS u_tel,
                g.status         AS u_status,
                g.tipificacion   AS u_tip,
                g.observacion    AS u_obs,
                g.fecha_gestion  AS u_fecha,
                ROW_NUMBER() OVER (
                PARTITION BY g.dni
                ORDER BY g.fecha_gestion DESC
                ) AS rn
            FROM gestiones g
            JOIN dset ds ON ds.dni = g.dni
            WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
            ),
            u AS (SELECT * FROM ultima_all WHERE rn = 1),

            mejor_all AS (
            SELECT
                g.dni,
                g.telefono       AS m_tel,
                g.status         AS m_status,
                g.tipificacion   AS m_tip,
                g.observacion    AS m_obs,
                g.fecha_gestion  AS m_fecha,
                COALESCE(t.puntos,999) AS m_puntos,
                ROW_NUMBER() OVER (
                PARTITION BY g.dni
                ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
                ) AS rn
            FROM gestiones g
            JOIN dset ds ON ds.dni = g.dni
            LEFT JOIN tipificaciones t
                ON REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')
                =
                REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')
            WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
            ),
            m AS (SELECT * FROM mejor_all WHERE rn = 1),

            cnt AS (
            SELECT g.dni, COUNT(*) AS intensidad
            FROM gestiones g
            JOIN dset ds ON ds.dni = g.dni
            WHERE g.fecha_gestion >= ? AND g.fecha_gestion < ?
            GROUP BY g.dni
            )

            SELECT
            d.cartera                                                  AS `CARTERA`,
            CONCAT(COALESCE(CAST(d.dni    AS CHAR), ''), CHAR(39))     AS `DNI`,
            d.titular                                                  AS `TITULAR`,
            CONCAT(COALESCE(CAST(d.codigo AS CHAR), ''), CHAR(39))     AS `CODIGO`,
            d.entidad                                                  AS `ENTIDAD`,
            d.cosecha                                                  AS `COSECHA`,
            d.producto                                                 AS `PRODUCTO`,
            d.sub_producto                                             AS `SUB_PRODUCTO`,
            d.historico                                                AS `HISTORICO`,
            d.departamento                                             AS `DEPARTAMENTO`,
            CASE
                WHEN COALESCE(d.deuda_capital,0) >= 50000 THEN '1.[50K - MAS]'
                WHEN COALESCE(d.deuda_capital,0) >= 20000 THEN '2.[20K - 50K]'
                WHEN COALESCE(d.deuda_capital,0) >= 10000 THEN '3.[10K - 20K]'
                WHEN COALESCE(d.deuda_capital,0) >=  5000 THEN '4.[5K - 10K]'
                WHEN COALESCE(d.deuda_capital,0) >=  1000 THEN '5.[1K - 5K]'
                WHEN COALESCE(d.deuda_capital,0) >=   501 THEN '6.[501 - 1K]'
                ELSE '7.[0 - 500]'
            END                                                        AS `RANGO`,
            d.deuda_total                                              AS `DEUDA TOTAL`,
            d.deuda_capital                                            AS `DEUDA CAPITAL`,
            d.campania                                                 AS `CAMPAÑA`,
            d.porcentaje                                               AS `%`,
            COALESCE(best.mc, 'SG')                                    AS `MC`,
            COALESCE(best.tipificacion, 'SIN GESTION')                 AS `SITUACION`,
            CONCAT(COALESCE(CAST(best.telefono AS CHAR), ''), CHAR(39))    AS `TELEFONOS`,
            CONCAT(COALESCE(CAST(u.u_tel  AS CHAR), ''), CHAR(39))         AS `TELEFONO - ULTIMA GESTION`,
            u.u_status                                                     AS `STATUS - ULTIMA GESTION`,
            u.u_tip                                                        AS `TIPIFICACION - ULTIMA GESTION`,
            u.u_obs                                                        AS `OBSERVACION - ULTIMA GESTION`,
            u.u_fecha                                                      AS `FECHA - ULTIMA GESTION`,
            CONCAT(COALESCE(CAST(m.m_tel AS CHAR), ''), CHAR(39))          AS `TELEFONO - MEJOR GESTION`,
            m.m_status                                                     AS `STATUS - MEJOR GESTION`,
            m.m_tip                                                        AS `TIPIFICACION - MEJOR GESTION`,
            m.m_obs                                                        AS `OBSERVACION - MEJOR GESTION`,
            m.m_fecha                                                      AS `FECHA - MEJOR GESTION`,
            COALESCE(cnt.intensidad,0)                                 AS `INTENSIDAD`
            FROM data d
            LEFT JOIN best  ON best.dni = d.dni
            LEFT JOIN u     ON u.dni    = d.dni
            LEFT JOIN m     ON m.dni    = d.dni
            LEFT JOIN cnt   ON cnt.dni  = d.dni
            ORDER BY d.cartera, d.dni
            SQL;

        // Orden de bindings:
        // (best_all start,end) (u start,end) (m start,end) (cnt start,end)
        $bindings = [$start, $end, $start, $end, $start, $end, $start, $end];

        return [$sql, $bindings];
    }
}
