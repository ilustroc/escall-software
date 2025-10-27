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

class ReporteDataTecCenterMesExport extends DefaultValueBinder
    implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use Exportable;

    /** Mes objetivo (YYYY-MM) requerido */
    protected string $month;

    /** Columnas Excel que deben forzarse a TEXTO (DNI, NUM CUENTA, TELEFONO) */
    private array $textCols = ['C','D','T'];

    public function forMonth(string $month): self
    {
        if (!preg_match('/^\d{4}\-\d{2}$/', $month)) {
            throw new \InvalidArgumentException('Mes inválido. Use formato YYYY-MM.');
        }
        $this->month = $month;
        return $this;
    }

    /** Binder: fuerza a texto en columnas específicas */
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
        DB::connection()->disableQueryLog();
        try { DB::statement('SET SESSION sql_big_selects=1'); } catch (\Throwable $e) {}

        // Rango de mes (cerrado-abierto)
        $start = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString();
        $end   = Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonth()->toDateString();

        // DNIs activos de TEC CENTER (DATA)
        $dset = DB::table('data')
            ->where('cartera', 'TEC CENTER')
            ->distinct()
            ->select('dni');

        // Normalizadores para empatar tipificación con tabla tipificaciones
        $normG = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normT = "REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')";

        // --------- GESTIONES del MES (sólo DNIs activos de TEC CENTER) ----------
        $gMes = DB::table('gestiones as g')
            ->joinSub($dset, 'dset', fn($j) => $j->on('dset.dni','=','g.dni'))
            ->whereBetween('g.fecha_gestion', [$start, $end]);

        // --------- ÚLTIMA del mes ----------
        $ultimaInner = DB::query()->fromSub($gMes, 'g')
            ->selectRaw("
                g.dni,
                g.telefono                               AS u_tel,
                g.status                                 AS u_status,
                g.tipificacion                           AS u_tip,
                g.observacion                            AS u_obs,
                g.fecha_gestion                          AS u_fecha,
                COALESCE(NULLIF(TRIM(g.nombre),''), g.usuario, 'SIN NOMBRE') AS u_nombre,
                ROW_NUMBER() OVER (PARTITION BY g.dni ORDER BY g.fecha_gestion DESC) AS rn
            ");
        $ultima = DB::query()->fromSub($ultimaInner, 'u')->where('u.rn',1);

        // --------- MEJOR del mes (por puntos ASC, luego más reciente) ----------
        $mejorInner = DB::query()->fromSub($gMes, 'g')
            ->leftJoin('tipificaciones as t', DB::raw($normG), '=', DB::raw($normT))
            ->selectRaw("
                g.dni,
                g.telefono                               AS m_tel,
                g.status                                 AS m_status,
                g.tipificacion                           AS m_tip,
                g.observacion                            AS m_obs,
                g.fecha_gestion                          AS m_fecha,
                g.fecha_pago                             AS m_fecha_pago,
                g.monto_pago                             AS m_monto_pago,
                COALESCE(NULLIF(TRIM(g.nombre),''), g.usuario, 'SIN NOMBRE') AS m_nombre,
                COALESCE(t.puntos, 999)                  AS m_puntos,
                ROW_NUMBER() OVER (
                  PARTITION BY g.dni
                  ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
                ) AS rn
            ");
        $mejor = DB::query()->fromSub($mejorInner, 'm')->where('m.rn',1);

        // --------- INTENSIDAD (conteo del mes) ----------
        $intensidad = DB::query()->fromSub($gMes, 'g')
            ->selectRaw('g.dni, COUNT(*) AS intensidad')
            ->groupBy('g.dni');

        // --------- SELECT final (sólo DATA TEC CENTER) ----------
        return DB::table('data as d')
            ->where('d.cartera', 'TEC CENTER')
            ->leftJoinSub($mejor, 'm', fn($j) => $j->on('m.dni','=','d.dni'))
            ->leftJoinSub($ultima, 'u', fn($j) => $j->on('u.dni','=','d.dni'))
            ->leftJoinSub($intensidad, 'cnt', fn($j) => $j->on('cnt.dni','=','d.dni'))
            ->select([
                // A..F
                DB::raw("'EXTERNO' AS canal"),
                DB::raw("'ESCALL' AS agencia"),
                'd.dni',
                'd.codigo',
                'd.cosecha   as cartera',
                'd.producto  as tipo_cartera',

                // G..J (vacíos)
                DB::raw("'' AS separador1"),
                DB::raw("NULL AS pago_soles"),
                DB::raw("NULL AS num_pagos"),
                DB::raw("NULL AS gt_asignado"),

                // K..M PDP condicional (si resultado = 'Promesa de Pago', case-insensitive)
                DB::raw("CASE WHEN UPPER(m.m_tip) = 'PROMESA DE PAGO' THEN m.m_fecha_pago ELSE NULL END AS fecha_pdp"),
                DB::raw("CASE WHEN UPPER(m.m_tip) = 'PROMESA DE PAGO' THEN m.m_monto_pago ELSE NULL END AS monto_pdp"),
                DB::raw("CASE WHEN UPPER(m.m_tip) = 'PROMESA DE PAGO' THEN m.m_nombre ELSE NULL END AS id_gestor"),

                // N..R
                DB::raw('m.m_status AS status'),
                DB::raw('m.m_fecha  AS fec_mejor'),
                DB::raw("'' AS gestion"),
                DB::raw("'' AS accion"),
                DB::raw('m.m_tip    AS resultado'),

                // S..U
                DB::raw('m.m_obs      AS comentario'),
                DB::raw('m.m_tel      AS telefono'),
                DB::raw('m.m_nombre   AS gestor'),

                // V..W
                DB::raw('u.u_nombre   AS ult_gestion'),
                DB::raw('COALESCE(cnt.intensidad,0) AS nro_gestiones'),

                // X..AC CANALES
                DB::raw("''  AS separador2"),
                DB::raw("'SI' AS sms"),
                // CORREOS: si resultado vacío -> '' ; si hay contenido -> 'SI'
                DB::raw("CASE WHEN COALESCE(NULLIF(TRIM(m.m_tip),''), '') = '' THEN '' ELSE 'SI' END AS correos"),
                // WHATSAPP: puntos < 17 -> 'SI' else 'NO'
                DB::raw("CASE WHEN COALESCE(m.m_puntos,999) < 17 THEN 'SI' ELSE 'NO' END AS whatsapp"),
                DB::raw("'SI' AS ivr"),
                DB::raw("''  AS otro"),
            ])
            ->orderBy('d.dni');
    }

    public function headings(): array
    {
        return [
            'CANAL','AGENCIA','DNI','NUM CUENTA','CARTERA','TIPO_CARTERA',
            '>>>>>','PAGO S/.','# PAGOS','GT ASIGNADO',
            'FECHA PDP','MONTO PDP','ID GESTOR',
            'STATUS','FEC. MEJOR GESTION','GESTION','ACCION','RESULTADO',
            'COMENTARIO','TELEFONO','GESTOR','ULT GESTION','NRO GESTIONES',
            '>>>>> CANALES >>>>>','SMS','CORREOS','WHATSAPP','IVR','OTRO',
        ];
    }

    public function map($r): array
    {
        $fmtDate = function ($dt) {
            if (empty($dt)) return null;
            try { return Carbon::parse($dt)->format('d/m/Y'); } catch (\Throwable $e) { return null; }
        };

        return [
            // A..F
            'EXTERNO',
            'ESCALL',
            (string)$r->dni,          // C texto
            (string)$r->codigo,       // D texto
            $r->cartera,
            $r->tipo_cartera,

            // G..J
            '',
            null,
            null,
            null,

            // K..M (PDP)
            $fmtDate($r->fecha_pdp),
            is_null($r->monto_pdp) ? null : (float)round($r->monto_pdp,2),
            $r->id_gestor,

            // N..R
            $r->status,
            $fmtDate($r->fec_mejor),
            '',
            '',
            $r->resultado,

            // S..U
            $r->comentario,
            (string)($r->telefono ?? ''),   // T texto
            $r->gestor,

            // V..W
            $r->ult_gestion,
            (int)($r->nro_gestiones ?? 0),

            // X..AC
            '',
            'SI',
            $r->correos,
            $r->whatsapp,
            'SI',
            '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Texto (además del binder)
            'C' => NumberFormat::FORMAT_TEXT, // DNI
            'D' => NumberFormat::FORMAT_TEXT, // NUM CUENTA
            'T' => NumberFormat::FORMAT_TEXT, // TELEFONO

            // Fechas como texto
            'K' => NumberFormat::FORMAT_TEXT, // FECHA PDP
            'O' => NumberFormat::FORMAT_TEXT, // FEC. MEJOR GESTION

            // Monto PDP con separador de miles y 2 decimales
            'L' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }
}
