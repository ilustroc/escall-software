<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GestionesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    public int $processed = 0;
    public int $inserted  = 0;
    public int $skipped   = 0;
    public int $failed    = 0;

    protected int $batchSize = 1000; // inserciones por lote

    protected array $required = [
        'fecha_gestion','dni','telefono','status','tipificacion','observacion','fecha_pago','monto_pago','nombre'
    ];

    public function collection(Collection $rows)
    {
        // Verifica headers la primera vez
        if ($this->processed === 0 && $rows->count() > 0) {
            $headers = array_keys($rows->first()->toArray());
            $headers = array_map(fn($k)=>Str::of($k)->lower()->value(), $headers);
            foreach ($this->required as $h) {
                if (!in_array($h, $headers, true)) {
                    $this->failed += $rows->count();
                    throw new \RuntimeException("Falta columna requerida: {$h}. Encabezados: ".implode(', ', $headers));
                }
            }
        }

        $batch = [];
        $now = now()->toDateTimeString();

        foreach ($rows as $row) {
            $this->processed++;
            try {
                $data = $row->toArray();

                $fg = $this->parseDate($data['fecha_gestion'] ?? null, false);
                $dni = trim((string)($data['dni'] ?? ''));

                if (!$fg || $dni==='') { $this->skipped++; continue; }

                $batch[] = [
                    'fecha_gestion' => $fg,
                    'dni'           => $dni,
                    'telefono'      => $this->cut($data['telefono'] ?? null, 25),
                    'status'        => $this->cut($data['status'] ?? null, 100),
                    'tipificacion'  => $this->cut($data['tipificacion'] ?? null, 120),
                    'observacion'   => $this->cut($data['observacion'] ?? null, 500),
                    'fecha_pago'    => $this->parseDate($data['fecha_pago'] ?? null, false),
                    'monto_pago'    => $this->toIntNullable($data['monto_pago'] ?? null),
                    'nombre'        => $this->cut($data['nombre'] ?? null, 150),
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
        return 1000; // lectura por bloques (suficiente para 2k; deja margen por si crece)
    }

    protected function flush(array &$batch): void
    {
        DB::table('gestiones')->insert($batch);
        $this->inserted += count($batch);
        $batch = [];
    }

    // ===== Helpers =====
    private function cut($v, int $n): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : mb_substr($s, 0, $n);
    }

    private function toIntNullable($v): ?int
    {
        if ($v === null || $v === '') return null;
        $s = preg_replace('/[^\d\-]/','', (string)$v);
        return is_numeric($s) ? (int)$s : null;
    }

    /**
     * Acepta serial Excel o textos dd/mm/yyyy | yyyy-mm-dd
     * Devuelve 'YYYY-MM-DD' o null
     */
    private function parseDate($v, bool $withTime=false): ?string
    {
        if ($v === null || $v === '') return null;

        // serial excel
        if (is_numeric($v)) {
            try {
                $dt = Carbon::instance(ExcelDate::excelToDateTimeObject((float)$v));
                return $dt->toDateString();
            } catch (\Throwable $e) { /* sigue */ }
        }

        $s = trim((string)$v);
        foreach (['d/m/Y','d-m-Y','Y-m-d','Y/m/d'] as $fmt) {
            try {
                $dt = Carbon::createFromFormat($fmt, $s);
                return $dt->toDateString();
            } catch (\Throwable $e) { /* intenta siguiente */ }
        }
        return null;
    }
}
