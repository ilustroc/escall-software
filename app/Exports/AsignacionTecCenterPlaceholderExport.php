<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AsignacionTecCenterPlaceholderExport implements FromArray, WithHeadings
{
    public function headings(): array
    {
        return ['PENDIENTE','Define columnas para FRMT_RG AGENCIAS EXTERNAS'];
    }

    public function array(): array
    {
        return [];
    }
}
