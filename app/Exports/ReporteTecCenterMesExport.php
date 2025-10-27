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

class ReporteTecCenterMesExport extends DefaultValueBinder
    implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithCustomValueBinder
{
    use Exportable;

    protected string $month; // YYYY-MM obligatorio

    /** Columnas que deben ser TEXTO en Excel (DNI/CUENTA/TEL) */
    private array $textCols = ['C','D','S'];

    public function forMonth(string $month): self
    {
        // Espera YYYY-MM (p.ej. 2025-10)
        if (!preg_match('/^\d{4}\-\d{2}$/', $month)) {
            throw new \InvalidArgumentException('Mes inválido. Formato esperado: YYYY-MM');
        }
        $this->month = $month;
        return $this;
    }

    // Fuerza texto en columnas C (DNI), D (NUM CUENTA), S (TELEFONO)
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

        [$start, $end] = [
            Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->toDateString(),
            Carbon::createFromFormat('Y-m', $this->month)->startOfMonth()->addMonth()->toDateString(),
        ];

        // DNIs activos sólo de TEC CENTER (acota muchísimo)
        $dniTec = DB::table('data')->distinct()->where('cartera','TEC CENTER')->select('dni');

        // Gestiones del mes (solo DNIs activos)
        $gMes = DB::table('gestiones as g')
            ->joinSub($dniTec, 'dset', fn($j) => $j->on('dset.dni','=','g.dni'))
            ->whereBetween('g.fecha_gestion', [$start, $end])
            ->select(['g.dni','g.telefono','g.status','g.tipificacion','g.observacion',
                      'g.fecha_gestion','g.fecha_pago','g.monto_pago','g.nombre']);

        // Última del mes por fecha DESC
        $ultimaAll = DB::query()->fromSub($gMes, 'g')
            ->selectRaw("
                g.dni,
                g.telefono       AS u_tel,
                g.status         AS u_status,
                g.tipificacion   AS u_tip,
                g.observacion    AS u_obs,
                g.fecha_gestion  AS u_fecha,
                COALESCE(NULLIF(TRIM(g.nombre),''), 'SIN NOMBRE') AS u_nombre,
                ROW_NUMBER() OVER (PARTITION BY g.dni ORDER BY g.fecha_gestion DESC) AS rn
            ");
        $ultima = DB::query()->fromSub($ultimaAll, 'u')->where('u.rn',1);

        // Mejor del mes (tipificaciones por puntaje asc, desempate fecha desc)
        $normG = "REPLACE(REPLACE(UPPER(TRIM(g.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $normT = "REPLACE(REPLACE(UPPER(TRIM(t.tipificacion)), CHAR(13), ''), CHAR(10), '')";
        $mejorAll = DB::query()->fromSub($gMes, 'g')
            ->leftJoin('tipificaciones as t', DB::raw($normG), '=', DB::raw($normT))
            ->selectRaw("
                g.dni,
                g.telefono       AS m_tel,
                g.status         AS m_status,
                g.tipificacion   AS m_tip,
                g.observacion    AS m_obs,
                g.fecha_gestion  AS m_fecha,
                g.fecha_pago     AS m_fecha_pago,
                g.monto_pago     AS m_monto_pago,
                COALESCE(NULLIF(TRIM(g.nombre),''), 'SIN NOMBRE') AS m_nombre,
                COALESCE(t.puntos, 999) AS m_puntos,
                ROW_NUMBER() OVER (
                  PARTITION BY g.dni
                  ORDER BY COALESCE(t.puntos,999) ASC, g.fecha_gestion DESC
                ) AS rn
            ");
        $mejor = DB::query()->fromSub($mejorAll, 'm')->where('m.rn',1);

        // Intensidad (conteo en el mes)
        $intensidad = DB::query()->fromSub($gMes, 'g')
            ->selectRaw('g.dni, COUNT(*) AS intensidad')
            ->groupBy('g.dni');

        // SELECT final (sólo columnas necesarias; evita duplicados)
        return DB::table('data as d')
            ->joinSub($dniTec, 'dset', fn($j)=>$j->on('dset.dni','=','d.dni'))
            ->leftJoinSub($mejor,   'm',   fn($j)=>$j->on('m.dni','=','d.dni'))
            ->leftJoinSub($ultima,  'u',   fn($j)=>$j->on('u.dni','=','d.dni'))
            ->leftJoinSub($intensidad, 'cnt', fn($j)=>$j->on('cnt.dni','=','d.dni'))
            ->where('d.cartera','TEC CENTER')
            ->select([
                DB::raw("'EXTERNO' AS canal"),                // A
                DB::raw("'ESCALL'  AS agencia"),              // B
                'd.dni',                                      // C (texto)
                'd.codigo',                                   // D (texto)
                'd.cosecha as cartera',                       // E
                'd.producto as tipo_cartera',                 // F
                DB::raw("'' AS separador1"),                  // G
                DB::raw("NULL AS pago_soles"),                // H
                DB::raw("NULL AS num_pagos"),                 // I
                DB::raw("NULL AS gt_asignado"),               // J
                DB::raw("CASE WHEN UPPER(m.m_tip)='PROMESA DE PAGO' THEN m.m_fecha_pago ELSE NULL END AS fecha_pdp"), // K
                DB::raw("CASE WHEN UPPER(m.m_tip)='PROMESA DE PAGO' THEN m.m_monto_pago ELSE NULL END AS monto_pdp"), // L
                DB::raw("CASE WHEN UPPER(m.m_tip)='PROMESA DE PAGO' THEN m.m_nombre     ELSE NULL END AS id_gestor"), // M
                DB::raw('m.m_status AS status'),              // N
                DB::raw('m.m_fecha  AS fec_mejor_gestion'),   // O
                DB::raw("'' AS accion"),                      // P
                DB::raw('m.m_tip   AS resultado'),            // Q
                DB::raw('m.m_obs   AS comentario'),           // R
                DB::raw('m.m_tel   AS telefono'),             // S (texto)
                DB::raw('m.m_nombre AS gestor'),              // T
                DB::raw('u.u_fecha  AS ult_gestion'),         // U (fecha última)
                DB::raw('COALESCE(cnt.intensidad,0) AS nro_gestiones'), // V
                DB::raw("'' AS separador2"),                  // W
                DB::raw("'SI' AS sms"),                       // X
                DB::raw("CASE WHEN COALESCE(NULLIF(TRIM(m.m_tip),''),'')='' THEN '' ELSE 'SI' END AS correos"), // Y
                DB::raw("CASE WHEN COALESCE(m.m_puntos,999) < 17 THEN 'SI' ELSE '' END AS whatsapp"), // Z
                DB::raw("'SI' AS ivr"),                       // AA
                DB::raw("''  AS otro"),                       // AB
            ])
            ->orderBy('d.dni');
    }

    public function headings(): array
    {
        return [
            'CANAL','AGENCIA','DNI','NUM CUENTA','CARTERA','TIPO_CARTERA',
            '>>>>>','PAGO S/.','# PAGOS','GT ASIGNADO',
            'FECHA PDP','MONTO PDP','ID GESTOR',
            'STATUS','FEC. MEJOR GESTION','ACCION','RESULTADO','COMENTARIO',
            'TELEFONO','GESTOR','ULT GESTION','NRO GESTIONES',
            '>>>>> CANALES >>>>>','SMS','CORREOS','WHATSAPP','IVR','OTRO',
        ];
    }

    public function map($r): array
    {
        $d = fn($v) => $v ? Carbon::parse($v)->format('d/m/Y') : null;

        return [
            'EXTERNO',
            'ESCALL',
            (string)$r->dni,               // texto
            (string)$r->codigo,            // texto
            $r->cartera,
            $r->tipo_cartera,
            '', null, null, null,
            $d($r->fecha_pdp),
            $r->monto_pdp !== null ? (float)$r->monto_pdp : null,
            $r->id_gestor,
            $r->status,
            $d($r->fec_mejor_gestion),
            '',
            $r->resultado,
            $r->comentario,
            (string)($r->telefono ?? ''),  // texto
            $r->gestor,
            $d($r->ult_gestion),
            (int)$r->nro_gestiones,
            '',
            'SI',
            $r->correos,
            $r->whatsapp, // '' o 'SI'
            'SI',
            '',
        ];
    }

    public function columnFormats(): array
    {
        return [
            // Texto forzado
            'C' => NumberFormat::FORMAT_TEXT, // DNI
            'D' => NumberFormat::FORMAT_TEXT, // NUM CUENTA
            'S' => NumberFormat::FORMAT_TEXT, // TELEFONO

            // Fechas como texto (ya las mapeo dd/mm/yyyy)
            'K' => NumberFormat::FORMAT_TEXT, // FECHA PDP
            'O' => NumberFormat::FORMAT_TEXT, // FEC. MEJOR GESTION
            'U' => NumberFormat::FORMAT_TEXT, // ULT GESTION
        ];
    }
}
