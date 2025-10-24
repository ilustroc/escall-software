<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ReporteDataCarterasExport extends DefaultValueBinder
    implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use Exportable;

    /** Mes objetivo forzado (YYYY-MM). */
    protected ?string $month = null;

    /** Columnas Excel que deben salir como TEXTO sí o sí. */
    private array $textCols = ['B','D','R','S','X'];

    public function forMonth(string $month): self
    {
        if (!preg_match('/^\d{4}\-\d{2}$/', $month)) {
            throw new \InvalidArgumentException('Mes inválido. Use formato YYYY-MM.');
        }
        $this->month = $month;
        return $this;
    }

    /** Fuerza tipo TEXTO en columnas críticas. */
    public function bindValue(Cell $cell, $value)
    {
        if (in_array($cell->getColumn(), $this->textCols, true)) {
            $cell->setValueExplicit((string)$value, DataType::TYPE_STRING);
            return true;
        }
        return parent::bindValue($cell, $value);
    }

    public function query()
    {
        if (!$this->month) {
            throw new \RuntimeException('Debe especificar el mes (YYYY-MM) antes de exportar.');
        }

        DB::connection()->disableQueryLog();
        try { DB::statement('SET SESSION sql_big_selects=1'); } catch (\Throwable $e) {}

        // Rango fijo del mes elegido (>= start, < end)
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonth()->toDateString();

        // Solo DNIs activos (presentes en DATA)
        $dniData = DB::table('data')->distinct()->select('dni');

        // Normaliza tipificaciones para macheo robusto
        $normG = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normT = "REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        // Rango por deuda_capital
        $caseRango = "
            CASE
              WHEN COALESCE(d.deuda_capital,0) >= 50000 THEN '1.[50K - MAS]'
              WHEN COALESCE(d.deuda_capital,0) >= 20000 THEN '2.[20K - 50K]'
              WHEN COALESCE(d.deuda_capital,0) >= 10000 THEN '3.[10K - 20K]'
              WHEN COALESCE(d.deuda_capital,0) >=  5000 THEN '4.[5K - 10K]'
              WHEN COALESCE(d.deuda_capital,0) >=  1000 THEN '5.[1K - 5K]'
              WHEN COALESCE(d.deuda_capital,0) >=   501 THEN '6.[501 - 1K]'
              ELSE '7.[0 - 500]'
            END
        ";

        /* ===== Mejor GLOBAL (para MC/SITUACION/TELEFONOS), EXCLUYENDO el mes elegido ===== */
        $bestAll = DB::table('gestiones as g')
            ->joinSub($dniData, 'dset', fn($j) => $j->on('dset.dni','=','g.dni'))
            ->leftJoin('tipificaciones as t', DB::raw($normG), '=', DB::raw($normT))
            ->where(function($q) use ($start, $end) {
                $q->where('g.fecha_gestion','<',$start)
                  ->orWhere('g.fecha_gestion','>=',$end);
            })
            ->selectRaw("
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
            ");
        $best = DB::query()->fromSub($bestAll, 'best')->where('best.rn', 1);

        /* ===== Última dentro del mes elegido ===== */
        $ultimaInner = DB::table('gestiones as g')
            ->joinSub($dniData, 'dset', fn($j)=>$j->on('dset.dni','=','g.dni'))
            ->where('g.fecha_gestion','>=',$start)
            ->where('g.fecha_gestion','<',$end)
            ->selectRaw("
                g.dni,
                g.telefono AS u_tel,
                g.status   AS u_status,
                g.tipificacion AS u_tip,
                g.observacion  AS u_obs,
                g.fecha_gestion AS u_fecha,
                ROW_NUMBER() OVER (
                  PARTITION BY g.dni
                  ORDER BY g.fecha_gestion DESC
                ) AS rn
            ");
        $u1 = DB::query()->fromSub($ultimaInner, 'u')->where('u.rn',1);

        /* ===== Mejor dentro del mes elegido ===== */
        $mejorInner = DB::table('gestiones as g')
            ->leftJoin('tipificaciones as t', DB::raw($normG), '=', DB::raw($normT))
            ->joinSub($dniData, 'dset', fn($j)=>$j->on('dset.dni','=','g.dni'))
            ->where('g.fecha_gestion','>=',$start)
            ->where('g.fecha_gestion','<',$end)
            ->selectRaw("
                g.dni,
                g.telefono AS m_tel,
                g.status   AS m_status,
                g.tipificacion AS m_tip,
                g.observacion  AS m_obs,
                g.fecha_gestion AS m_fecha,
                COALESCE(t.puntos,999) AS m_puntos,
                ROW_NUMBER() OVER (
                  PARTITION BY g.dni
                  ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
                ) AS rn
            ");
        $m1 = DB::query()->fromSub($mejorInner, 'm')->where('m.rn',1);

        /* ===== Intensidad dentro del mes elegido ===== */
        $intensidad = DB::table('gestiones as g')
            ->joinSub($dniData, 'dset', fn($j)=>$j->on('dset.dni','=','g.dni'))
            ->where('g.fecha_gestion','>=',$start)
            ->where('g.fecha_gestion','<',$end)
            ->selectRaw('g.dni, COUNT(*) AS intensidad')
            ->groupBy('g.dni');

        /* ===== SELECT final ===== */
        return DB::table('data as d')
            ->leftJoinSub($best, 'best', fn($j)=>$j->on('best.dni','=','d.dni'))
            ->leftJoinSub($u1,   'u',    fn($j)=>$j->on('u.dni','=','d.dni'))
            ->leftJoinSub($m1,   'm',    fn($j)=>$j->on('m.dni','=','d.dni'))
            ->leftJoinSub($intensidad, 'cnt', fn($j)=>$j->on('cnt.dni','=','d.dni'))
            ->select([
                'd.cartera','d.dni','d.titular','d.codigo','d.entidad',
                'd.cosecha','d.producto','d.sub_producto','d.historico','d.departamento',
                DB::raw("($caseRango) AS rango"),
                'd.deuda_total','d.deuda_capital','d.campania','d.porcentaje',
                DB::raw("COALESCE(best.mc, 'SG') AS mc"),
                DB::raw("COALESCE(best.tipificacion, 'SIN GESTION') AS situacion"),
                DB::raw('best.telefono AS telefonos'),
                DB::raw('u.u_tel AS u_telefono'), DB::raw('u.u_status AS u_status'),
                DB::raw('u.u_tip AS u_tipificacion'), DB::raw('u.u_obs AS u_observacion'),
                DB::raw('u.u_fecha AS u_fecha'),
                DB::raw('m.m_tel AS m_telefono'), DB::raw('m.m_status AS m_status'),
                DB::raw('m.m_tip AS m_tipificacion'), DB::raw('m.m_obs AS m_observacion'),
                DB::raw('m.m_fecha AS m_fecha'),
                DB::raw('COALESCE(cnt.intensidad,0) AS intensidad'),
            ])
            ->orderBy('d.cartera')
            ->orderBy('d.dni');
    }

    public function headings(): array
    {
        return [
            'CARTERA','DNI','TITULAR','CODIGO','ENTIDAD','COSECHA','PRODUCTO','SUB_PRODUCTO',
            'HISTORICO','DEPARTAMENTO','RANGO','DEUDA TOTAL','DEUDA CAPITAL','CAMPAÑA','%',
            'MC','SITUACION','TELEFONOS',
            'TELEFONO - ULTIMA GESTION','STATUS - ULTIMA GESTION','TIPIFICACION - ULTIMA GESTION',
            'OBSERVACION - ULTIMA GESTION','FECHA - ULTIMA GESTION',
            'TELEFONO - MEJOR GESTION','STATUS - MEJOR GESTION','TIPIFICACION - MEJOR GESTION',
            'OBSERVACION - MEJOR GESTION','FECHA - MEJOR GESTION',
            'INTENSIDAD',
        ];
    }

    public function map($r): array
    {
        $fmt = fn($dt) => empty($dt) ? null : Carbon::parse($dt)->format('d/m/Y');
        return [
            $r->cartera,
            (string)$r->dni,
            $r->titular,
            (string)$r->codigo,
            $r->entidad,
            $r->cosecha,
            $r->producto,
            $r->sub_producto,
            $r->historico,
            $r->departamento,
            $r->rango,
            (float)round($r->deuda_total   ?? 0,2),
            (float)round($r->deuda_capital ?? 0,2),
            (float)round($r->campania      ?? 0,2),
            (float)($r->porcentaje ?? 0),
            $r->mc ?? 'SG',
            $r->situacion ?? 'SIN GESTION',
            (string)($r->telefonos ?? ''),
            (string)($r->u_telefono ?? ''),
            $r->u_status ?? null,
            $r->u_tipificacion ?? null,
            $r->u_observacion ?? null,
            $fmt($r->u_fecha),
            (string)($r->m_telefono ?? ''),
            $r->m_status ?? null,
            $r->m_tipificacion ?? null,
            $r->m_observacion ?? null,
            $fmt($r->m_fecha),
            (int)($r->intensidad ?? 0),
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Texto forzado
            'B'=>NumberFormat::FORMAT_TEXT, // DNI
            'D'=>NumberFormat::FORMAT_TEXT, // CODIGO
            'R'=>NumberFormat::FORMAT_TEXT, // TELEFONOS
            'S'=>NumberFormat::FORMAT_TEXT, // TEL ULTIMA
            'X'=>NumberFormat::FORMAT_TEXT, // TEL MEJOR
            // Números
            'L'=>NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'M'=>NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'N'=>NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
            'O'=>NumberFormat::FORMAT_PERCENTAGE_00,
            // Fechas como texto (en map ya van como dd/mm/yyyy)
            'W'=>NumberFormat::FORMAT_TEXT,
            'AB'=>NumberFormat::FORMAT_TEXT,
        ];
    }
}
