<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class DataImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $processed = 0;
    public int $inserted  = 0;
    public int $skipped   = 0;
    public int $failed    = 0;

    protected int $batchSize = 1000; // inserciones por lote

    // requeridos mínimos para poder guardar
    protected array $required = ['codigo', 'dni'];

    public function collection(Collection $rows)
    {
        // ---- Validación de headers en el primer bloque ----
        if ($this->processed === 0 && $rows->count() > 0) {
            $first = $rows->first()->toArray();
            $normHeaders = [];
            foreach (array_keys($first) as $raw) {
                $k = $this->normKey($raw);
                if ($k === '' && trim((string)$raw) === '%') $k = 'porcentaje';
                $normHeaders[] = $k;
            }
            foreach ($this->required as $h) {
                if (!in_array($h, $normHeaders, true)) {
                    throw new \RuntimeException(
                        "Falta columna requerida: {$h}. Encabezados normalizados: ".implode(', ', $normHeaders)
                    );
                }
            }
        }

        $batch = [];
        $now = now()->toDateTimeString();

        foreach ($rows as $row) {
            $this->processed++;
            try {
                // Normaliza claves del row
                $raw = $row->toArray();
                $x = [];
                foreach ($raw as $k => $v) {
                    $nk = $this->normKey($k);
                    if ($nk === '' && trim((string)$k) === '%') $nk = 'porcentaje';
                    if ($nk !== '') $x[$nk] = $v;
                }

                $codigo = trim((string)($x['codigo'] ?? ''));
                $dni    = trim((string)($x['dni'] ?? ''));

                if ($codigo === '' || $dni === '') { $this->skipped++; continue; }

                $batch[] = [
                    'codigo'        => $codigo,
                    'dni'           => $dni,
                    'titular'       => $this->cut($x['titular']       ?? null, 150),
                    'cartera'       => $this->cut($x['cartera']       ?? null, 100),
                    'entidad'       => $this->cut($x['entidad']       ?? null, 100),
                    'cosecha'       => $this->cut($x['cosecha']       ?? null, 100),
                    'sub_cartera'   => $this->cut($x['sub_cartera']   ?? ($x['subcartera'] ?? null), 100),
                    'producto'      => $this->cut($x['producto']      ?? null, 100),
                    'sub_producto'  => $this->cut($x['sub_producto']  ?? ($x['subproducto'] ?? null), 100),
                    'historico'     => $this->cut($x['historico']     ?? null, 120),
                    'departamento'  => $this->cut($x['departamento']  ?? null, 100),

                    'deuda_total'   => $this->toDecimal($x['deuda_total']   ?? null, 2),
                    'deuda_capital' => $this->toDecimal($x['deuda_capital'] ?? null, 2),
                    'campania'      => $this->toDecimal($x['campania']      ?? ($x['campana'] ?? null), 2),
                    'porcentaje'    => $this->toDecimal($x['porcentaje']    ?? null, 9),

                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];

                if (count($batch) >= $this->batchSize) {
                    $this->flush($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $this->failed++;
            }
        }

        if (!empty($batch)) $this->flush($batch);
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    protected function flush(array &$batch): void
    {
        // upsert por 'codigo' (PRIMARY KEY) para evitar errores por duplicados
        DB::table('data')->upsert(
            $batch,
            ['codigo'],
            [
                'dni','titular','cartera','entidad','cosecha','sub_cartera','producto',
                'sub_producto','historico','departamento','deuda_total','deuda_capital',
                'campania','porcentaje','updated_at'
            ]
        );
        $this->inserted += count($batch);
        $batch = [];
    }

    // ===== Helpers =====

    private function normKey(string $k): string
    {
        $k = mb_strtolower($k, 'UTF-8');
        $k = strtr($k, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        $k = preg_replace('/[^a-z0-9]+/','_', $k);
        return trim($k, '_');
    }

    private function cut($v, int $n): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : mb_substr($s, 0, $n);
    }

    /**
     * Convierte números estilo "20,853.32" o "20.853,32" a string decimal con punto.
     * $scale define cuántos decimales forzar (2 para montos, 9 para porcentaje).
     */
    private function toDecimal($v, int $scale): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '?' || $s === '-') return null;

        // quita espacios/no-break
        $s = str_replace(["\xC2\xA0", ' '], '', $s);
        // si tiene puntos de miles y coma decimal (formato europeo)
        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            // quita comas de miles y deja punto como decimal
            $s = str_replace(',', '', $s);
        }

        if (!is_numeric($s)) return null;

        // devolver con escala fija
        return number_format((float)$s, $scale, '.', '');
    }
}
