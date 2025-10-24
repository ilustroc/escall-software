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

class ReporteTecCenterExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting
{
    use Exportable;

    public function __construct(private string $fi, private string $ff) {}

    public function query()
    {
        $start = $this->fi.' 00:00:00';
        $endEx = date('Y-m-d H:i:s', strtotime($this->ff . ' +1 day'));

        return DB::table('gestiones as g')
            ->join('data as d', 'd.dni', '=', 'g.dni')
            ->where('d.cartera', '=', 'TEC CENTER')
            ->where('g.fecha_gestion', '>=', $start)
            ->where('g.fecha_gestion', '<',  $endEx)
            ->selectRaw("
                g.dni                                               AS dni,
                d.codigo                                            AS ncuenta,
                d.cosecha                                           AS cartera,
                g.fecha_gestion                                     AS fecha_gestion,
                g.status                                            AS contacto,
                g.tipificacion                                      AS resultado,
                COALESCE(NULLIF(TRIM(g.nombre),''),'SIN NOMBRE')    AS gestor,
                g.observacion                                       AS comentario,
                g.telefono                                          AS telefono
            ")
            ->orderBy('g.fecha_gestion')
            ->orderBy('g.dni');
    }

    public function headings(): array
    {
        return [
            'DNI','N° CUENTA','CARTERA','FECHA GESTION','CONTACTO',
            'RESULTADO','GESTOR','COMENTARIO','TELEFONO'
        ];
    }

    public function map($r): array
    {
        $fg = $r->fecha_gestion ? Carbon::parse($r->fecha_gestion) : null;

        return [
            (string)$r->dni,          // DNI como texto (conserva ceros)
            (string)$r->ncuenta,      // N° CUENTA como texto
            $r->cartera,
            $fg,                      // FECHA GESTION (formato abajo)
            $r->contacto,
            $r->resultado,
            $r->gestor,
            $r->comentario,
            (string)$r->telefono,     // TELEFONO como texto
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,          // DNI
            'B' => NumberFormat::FORMAT_TEXT,          // N° CUENTA
            'D' => 'dd/mm/yyyy',              // FECHA GESTION
            'I' => NumberFormat::FORMAT_TEXT,          // TELEFONO
        ];
    }
}
