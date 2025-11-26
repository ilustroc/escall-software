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
    private ?int $equipo;

    public function __construct(string $fi, string $ff, ?int $equipo = null)
    {
        $this->fi     = $fi;
        $this->ff     = $ff;
        $this->equipo = $equipo;
    }

    public function query()
    {
        $start = $this->fi . ' 00:00:00';
        $endEx = date('Y-m-d H:i:s', strtotime($this->ff . ' +1 day'));

        $q = DB::table('gestiones as g')
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

        if ($this->equipo === 3) {
            $q->where('d.entidad', 'BCP');
        } elseif ($this->equipo === 2) {
            $q->where(function ($qq) {
                $qq->whereNull('d.entidad')
                   ->orWhere('d.entidad', '!=', 'BCP');
            });
        }

        return $q;
    }

    public function headings(): array
    {
        return [
            'DOCUMENTO','CLIENTE','ACCIÓN','CONTACTO','AGENTE','OPERACION','ENTIDAD','EQUIPO',
            'FECHA GESTION','FECHA CITA','TELEFONO','OBSERVACION',
            'MONTO PROMESA','NRO CUOTAS','FECHA PROMESA','PROCEDENCIA LLAMADA',
        ];
    }

    public function map($r): array
    {
        // EQUIPO en función de ENTIDAD
        $equipo = ($r->entidad === 'BCP')
            ? 'PROPIA 3 - ESCALL'
            : 'PROPIA 2 - ESCALL';

        // Fechas como texto dd/mm/yy
        $fg = $r->fecha_gestion
            ? Carbon::parse($r->fecha_gestion)->format('d/m/y')
            : '';
        $fp = $r->fecha_promesa
            ? Carbon::parse($r->fecha_promesa)->format('d/m/y')
            : '';

        return [
            (string) $r->documento,     // DOCUMENTO siempre
            $r->cliente,
            $r->accion,
            $r->contacto,
            $r->agente,
            (string) $r->operacion,     // OPERACION
            $r->entidad,
            $equipo,
            $fg,                        // FECHA GESTION
            null,                       // FECHA CITA
            (string) $r->telefono,      // TELEFONO
            $r->observacion,
            $r->monto_promesa,
            $r->nro_cuotas,
            $fp,                        // FECHA PROMESA
            'Predictivo',
        ];
    }

    // Formatos por columna
    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,   // DOCUMENTO
            'F' => NumberFormat::FORMAT_TEXT,   // OPERACION
            'K' => NumberFormat::FORMAT_TEXT,   // TELEFONO
        ];
    }
}
