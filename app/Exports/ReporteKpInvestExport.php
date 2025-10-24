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

class ReporteKpInvestExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    public function __construct(private string $fi, private string $ff) {}

    public function query()
    {
        $start = $this->fi.' 00:00:00';
        $endEx = date('Y-m-d H:i:s', strtotime($this->ff . ' +1 day'));

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', '=', 'KP INVEST')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
            ->selectRaw("
                g.fecha_gestion                                              AS fecha_gest,
                CASE WHEN CHAR_LENGTH(g.dni)=8 THEN 'DNI'
                     WHEN CHAR_LENGTH(g.dni)=11 THEN 'RUC'
                     ELSE 'DNI' END                                          AS tip_doc,
                g.dni                                                        AS num_doc,
                d.titular                                                    AS cliente,
                g.telefono                                                   AS telefono,
                'LLAMADA ENTRATE'                                            AS accion,
                g.tipificacion                                               AS tipificacion,
                g.status                                                     AS estado,
                g.observacion                                                AS gestion,
                g.fecha_pago                                                 AS fecha_promesa,
                g.monto_pago                                                 AS monto_promesa
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');
    }

    public function headings(): array
    {
        return [
            'Fecha Gest','Tip.Doc','Num.Doc','Cliente','Teléfono','Acción',
            'Tipificación','ESTADO','Gestion','Fecha Promesa','Monto Promesa'
        ];
    }

    public function map($r): array
    {
        $fg = $r->fecha_gest ? Carbon::parse($r->fecha_gest) : null;
        $fp = $r->fecha_promesa ? Carbon::parse($r->fecha_promesa) : null;

        return [
            $fg,                     // Fecha Gest -> dd/mm/yyyy (col A)
            $r->tip_doc,             // Tip.Doc    -> DNI/RUC
            (string)$r->num_doc,     // Num.Doc    -> texto
            $r->cliente,
            (string)$r->telefono,    // Teléfono   -> texto
            $r->accion,              // "LLAMADA ENTRATE"
            $r->tipificacion,
            $r->estado,
            $r->gestion,
            $fp,                     // Fecha Promesa -> dd/mm/yyyy (col J)
            $r->monto_promesa,
        ];
    }

    // Formatos de columna (según el orden de headings)
    public function columnFormats(): array
    {
        return [
            'A' => 'dd/mm/yyyy',              // Fecha Gest
            'C' => NumberFormat::FORMAT_TEXT, // Num.Doc
            'E' => NumberFormat::FORMAT_TEXT, // Teléfono
            'J' => 'dd/mm/yyyy',              // Fecha Promesa
        ];
    }
}
