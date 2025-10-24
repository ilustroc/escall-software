<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipificacionesSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['CANCELACION',                              1,  'CD+'],
            ['CONVENIO DE PAGO',                         2,  'CD+'],
            ['PAGO PARCIAL',                             3,  'CD+'],
            ['RECORDATORIO DE PAGO',                     4,  'CD+'],
            ['PROMESA DE PAGO',                          5,  'CD+'],
            ['TITULAR REALIZA PROPUESTA DE PAGO',        6,  'CD+'],
            ['SEGUIMIENTO',                              7,  'CD+'],
            ['NEGOCIACION',                              8,  'CD+'],
            ['WHATSAPP - EN NEGOCIACION',                9,  'CD+'],
            ['TITULAR SOLICITA VOLVER A LLAMAR',         10, 'CD+'],
            ['TITULAR NO QUIERE PAGAR',                  12, 'CD-'],
            ['RECLAMO',                                  13, 'CD-'],
            ['WHATSAPP - CONTESTA',                      14, 'CD+'],
            ['ENCARGO EN DOMICILIO(PAPA, MAMA, HERMANOS, CONYUGUE)', 15, 'CI'],
            ['ENCARGO EN TERCEROS(OTROS FAMILIARES Y AMISTADOES)',    16, 'CI'],
            ['FALLECIDO',                                17, 'NC-'],
            ['ILOCALIZADO',                              18, 'NC-'],
            ['NUMERO EQUIVOCADO',                        19, 'NC-'],
            ['WHATSAPP - EQUVOCADO',                    20, 'NC-'],
            ['BUZON DE VOZ',                             21, 'NC-'],
            ['NO CONTESTA',                              22, 'NC+'],
        ];

        foreach ($rows as [$tip, $pts, $mc]) {
            DB::table('tipificaciones')->updateOrInsert(
                ['tipificacion' => $tip],
                ['puntos' => $pts, 'mc' => $mc, 'updated_at'=>now(),'created_at'=>now()]
            );
        }
    }
}
