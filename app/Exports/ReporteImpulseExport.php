<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Carbon\Carbon;

class ReporteImpulseExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    private string $fi;
    private string $ff;

    public function __construct(string $fi, string $ff)
    {
        $this->fi = $fi;
        $this->ff = $ff;
    }

    public function query()
    {
        $start = $this->fi . ' 00:00:00';
        $endEx = date('Y-m-d H:i:s', strtotime($this->ff . ' +1 day'));

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', 'like', '%IMPULSE%')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
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
            ->orderBy('g.dni');
    }

    public function headings(): array
    {
        return [
            'DOCUMENTO','CLIENTE','ACCIÓN','CONTACTO','AGENTE','OPERACION','ENTIDAD','EQUIPO',
            'FECHA GESTION','FECHA CITA','TELEFONO','OBSERVACION',
            'MONTO PROMESA','NRO CUOTAS','FECHA PROMESA','PROCEDENCIA LLAMADA','CARTERA',
        ];
    }

    public function map($r): array
    {
        // Convertimos fechas a objetos Carbon para que Excel respete el formato de columna
        $fg = $r->fecha_gestion ? Carbon::parse($r->fecha_gestion) : null;
        $fp = $r->fecha_promesa ? Carbon::parse($r->fecha_promesa) : null;

        return [
            $r->documento,
            $r->cliente,
            $r->accion,
            $r->contacto,
            $r->agente,
            (string) $r->operacion,     // OPERACION como texto
            $r->entidad,
            'PROPIA 2 - ESCALL',
            $fg,                        // FECHA GESTION (se formatea abajo)
            null,                       // FECHA CITA
            (string) $r->telefono,      // TELEFONO como texto
            $r->observacion,
            $r->monto_promesa,
            $r->nro_cuotas,
            $fp,                        // FECHA PROMESA (se formatea abajo)
            'Predictivo',
            $r->cartera,
        ];
    }

    // Formatos por columna (según el orden de headings)
    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_TEXT,   // OPERACION
            'I' => 'dd/mm/yyyy',                // FECHA GESTION
            'K' => NumberFormat::FORMAT_TEXT,   // TELEFONO
            'O' => 'dd/mm/yyyy',                // FECHA PROMESA
        ];
    }
}
