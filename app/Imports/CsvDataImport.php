<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;

class CsvDataImport
{
    private int $batchSize;
    public function __construct(int $batchSize = 1000){ $this->batchSize = max(200, $batchSize); }

    public function run(string $absolutePath): array
    {
        $fh = new \SplFileObject($absolutePath, 'r');
        $fh->setFlags(\SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY | \SplFileObject::DROP_NEW_LINE);
        $fh->setCsvControl(','); // si usas ; cámbialo aquí

        $processed = $inserted = $skipped = $failed = 0;

        $headers = null;
        $batch   = [];
        $now     = now()->toDateTimeString();

        foreach ($fh as $row) {
            if ($row === [null] || $row === false) continue;

            if ($headers === null) { // primera fila = cabeceras
                $headers = array_map(fn($h) => $this->normKey((string)$h), $row);
                foreach ($headers as $i => $h) if ($h === '' && trim((string)$row[$i]) === '%') $headers[$i] = 'porcentaje';
                if (!in_array('codigo', $headers, true) || !in_array('dni', $headers, true)) {
                    throw new \RuntimeException('Faltan columnas requeridas: codigo y/o dni');
                }
                continue;
            }

            $processed++;
            try {
                $assoc = [];
                foreach ($headers as $i => $key) {
                    if ($key === '') continue;
                    $assoc[$key] = $row[$i] ?? null;
                }

                $codigo = trim((string)($assoc['codigo'] ?? ''));
                $dni    = trim((string)($assoc['dni'] ?? ''));
                if ($codigo === '' || $dni === '') { $skipped++; continue; }

                $batch[] = [
                    'codigo'        => $codigo,
                    'dni'           => $dni,
                    'titular'       => $this->cut($assoc['titular'] ?? null, 150),
                    'cartera'       => $this->cut($assoc['cartera'] ?? null, 100),
                    'entidad'       => $this->cut($assoc['entidad'] ?? null, 100),
                    'cosecha'       => $this->cut($assoc['cosecha'] ?? null, 100),
                    'sub_cartera'   => $this->cut($assoc['sub_cartera'] ?? ($assoc['subcartera'] ?? null), 100),
                    'producto'      => $this->cut($assoc['producto'] ?? null, 100),
                    'sub_producto'  => $this->cut($assoc['sub_producto'] ?? ($assoc['subproducto'] ?? null), 100),
                    'historico'     => $this->cut($assoc['historico'] ?? null, 120),
                    'departamento'  => $this->cut($assoc['departamento'] ?? null, 100),

                    'deuda_total'   => $this->toDecimal($assoc['deuda_total']   ?? null, 2),
                    'deuda_capital' => $this->toDecimal($assoc['deuda_capital'] ?? null, 2),
                    'campania'      => $this->toDecimal($assoc['campania']      ?? ($assoc['campana'] ?? null), 2),
                    'porcentaje'    => $this->toDecimal($assoc['porcentaje']    ?? null, 9),

                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];

                if (count($batch) >= $this->batchSize) { $this->flush($batch); $inserted += $this->batchSize; $batch = []; }
            } catch (\Throwable $e) { $failed++; }
        }

        if (!empty($batch)) { $this->flush($batch); $inserted += count($batch); }

        return compact('processed','inserted','skipped','failed');
    }

    private function flush(array $batch): void
    {
        DB::table('data')->upsert(
            $batch,
            ['codigo'],
            [
                'dni','titular','cartera','entidad','cosecha','sub_cartera','producto',
                'sub_producto','historico','departamento','deuda_total','deuda_capital',
                'campania','porcentaje','updated_at'
            ]
        );
    }

    private function normKey(string $k): string {
        $k = mb_strtolower($k,'UTF-8'); $k = strtr($k,['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
        $k = preg_replace('/[^a-z0-9]+/','_',$k); return trim($k,'_');
    }
    private function cut($v,int $n): ?string { if($v===null) return null; $s=trim((string)$v); return $s===''?null:mb_substr($s,0,$n); }
    private function toDecimal($v,int $scale): ?string {
        if ($v===null) return null; $s=trim((string)$v); if ($s===''||$s==='?'||$s==='-') return null;
        $s=str_replace(["\xC2\xA0",' '],'',$s);
        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/',$s)) { $s=str_replace('.','',$s); $s=str_replace(',', '.',$s); }
        else { $s=str_replace(',','',$s); }
        if (!is_numeric($s)) return null; return number_format((float)$s,$scale,'.','');
    }
}
