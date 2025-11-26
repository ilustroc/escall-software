<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;
use PDO;

class GestionesSpController extends Controller
{
    public function form(): View
    {
        $fi = now()->startOfMonth()->toDateString();
        $ff = now()->toDateString();

        return view('cargas.index', [
            'spFi'      => $fi,
            'spFf'      => $ff,
            'spPreview' => null,
            'spCount'   => null,
        ]);
    }

    public function preview(Request $r): View
    {
        [$fi, $ff] = $this->validateDates($r);

        // Trae primeras 100 filas SIN “cartera”
        [$rows, $total] = $this->fetchFromSpPreview($fi, $ff, 100);

        return view('cargas.index', [
            'spFi'      => $fi,
            'spFf'      => $ff,
            'spPreview' => $rows,
            'spCount'   => $total,
        ]);
    }

    public function import(Request $r)
    {
        [$fi, $ff] = $this->validateDates($r);

        @set_time_limit(0);
        @ini_set('memory_limit','1024M');
        DB::connection()->disableQueryLog();

        // 1) Borrar rango en local
        DB::transaction(function() use ($fi, $ff) {
            DB::table('gestiones')
              ->whereBetween('fecha_gestion', [$fi, $ff])
              ->delete();
        });

        // 2) Insertar desde SP en streaming (por lotes)
        $inserted = $this->streamFromSpAndInsert($fi, $ff);

        return back()->with('ok', "Importación finalizada. Insertadas: {$inserted} filas para {$fi} → {$ff}.");
    }

    // ===== Helpers =====

    private function validateDates(Request $r): array
    {
        $data = $r->validate([
            'fi' => ['required','date'],
            'ff' => ['required','date','after_or_equal:fi'],
        ]);
        return [$data['fi'], $data['ff']];
    }

    /**
     * Vista previa: lee de a poco y devuelve hasta $limit filas mapeadas + conteo total
     */
    private function fetchFromSpPreview(string $fi, string $ff, int $limit = 100): array
    {
        $pdo = DB::connection('sp')->getPdo();
        // Unbuffered para no cargar todo en memoria
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $stmt = $pdo->prepare('CALL sp_gestiones(?, ?)');
        $stmt->execute([$fi, $ff]);

        $rows = [];
        $count = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $count++;
            if (count($rows) < $limit) {
                $mapped = $this->mapRow($row); // sin cartera
                if ($mapped) {
                    $rows[] = $mapped;
                }
            }
        }

        // Limpia posibles result sets extra del SP
        while ($stmt->nextRowset()) { /* consume */ }
        $stmt->closeCursor();

        return [$rows, $count];
    }

    /**
     * Importación real: stream + insert en lotes
     */
    private function streamFromSpAndInsert(string $fi, string $ff): int
    {
        $pdo = DB::connection('sp')->getPdo();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

        $stmt = $pdo->prepare('CALL sp_gestiones(?, ?)');
        $stmt->execute([$fi, $ff]);

        $batch = [];
        $BATCH = 2000;
        $inserted = 0;
        $now = now()->toDateTimeString();

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $mapped = $this->mapRow($row);
            if (!$mapped) continue;

            $mapped['created_at'] = $now;
            $mapped['updated_at'] = $now;

            $batch[] = $mapped;
            if (count($batch) >= $BATCH) {
                DB::table('gestiones')->insert($batch);
                $inserted += count($batch);
                $batch = [];
            }
        }

        // Flush final ANTES de limpiar rowsets
        if (!empty($batch)) {
            DB::table('gestiones')->insert($batch);
            $inserted += count($batch);
        }

        // Limpia posibles result sets extra del SP
        while ($stmt->nextRowset()) { /* consume */ }
        $stmt->closeCursor();

        return $inserted;
    }

    /**
     * Mapea columnas del SP a tu tabla local "gestiones"
     * Ignora la columna 'cartera' si viene.
     * Acepta nombres con espacios/acentos.
     */
    private function mapRow(array $r): ?array
    {
        // Normaliza claves (minus, sin acentos/espacios/símbolos)
        $norm = function(string $k): string {
            $k = mb_strtolower($k, 'UTF-8');
            $k = strtr($k, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
            $k = preg_replace('/[^a-z0-9]+/','_', $k);
            return trim($k,'_');
        };
        $x = [];
        foreach ($r as $k=>$v) $x[$norm($k)] = $v;

        // Posibles nombres del SP -> los de tu tabla
        $fechaGestion = $x['fecha_gestion'] ?? $x['fecha_de_gestion'] ?? $x['fecha_gestion_'] ?? null;
        $dni          = $x['dni']           ?? $x['documento'] ?? null;
        $telefono     = $x['telefono']      ?? $x['celular'] ?? null;
        $status       = $x['status']        ?? $x['tipo_de_contacto'] ?? $x['contacto'] ?? null;
        $tipificacion = $x['tipificacion']  ?? $x['tipo_de_resultado'] ?? $x['resultado'] ?? null;
        $observacion  = $x['observacion']   ?? $x['obs'] ?? null;
        $fechaPago    = $x['fecha_pago']    ?? null;
        $montoPago    = $x['monto_pago']    ?? $x['montopago'] ?? null;
        $nombre       = $x['nombre']        ?? $x['agente'] ?? $x['usuario'] ?? null;
        // Ignoramos: $x['cartera'] si existe

        // ⇢ Defaults cuando vienen vacíos
        $status       = $this->defaultStatus($status);           // <- NO CONTACTO si viene vacío
        $tipificacion = $this->defaultTipificacion($tipificacion); // <- NO CONTESTA si viene vacío

        // Convierte fechas; si fecha_gestion inválida, descarta fila
        $fg = $this->toDate($fechaGestion);
        if (!$fg || !$dni) return null;
        $fp = $this->toDate($fechaPago);

        return [
            'fecha_gestion' => $fg,
            'dni'           => trim((string)$dni),
            'telefono'      => $this->cut($telefono, 25),
            'status'        => $this->cut($status, 100),
            'tipificacion'  => $this->cut($tipificacion, 120),
            'observacion'   => $this->cut($observacion, 500),
            'fecha_pago'    => $fp,
            'monto_pago'    => $this->toIntNullable($montoPago),
            'nombre'        => $this->cut($nombre, 150),
        ];
    }

    private function defaultStatus($v): string
    {
        $s = trim((string)$v);
        return $s === '' ? 'NO CONTACTO' : $s;
    }

    private function defaultTipificacion($v): string
    {
        $s = trim((string)$v);
        return $s === '' ? 'NO CONTESTA' : $s;
    }

    private function toDate($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '0000-00-00' || $s === '00/00/0000' || $s === '00000000') return null;

        // yyyy-mm-dd o yyyy-mm-dd hh:mm:ss
        if (preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}:\d{2})?$/', $s)) {
            $datePart = substr($s, 0, 10);
            if ($datePart === '0000-00-00') return null;
            try {
                $dt = new \DateTime($datePart);
                $y = (int)$dt->format('Y');
                if ($y < 1900 || $y > 2100) return null;
                return $dt->format('Y-m-d');
            } catch (\Throwable $e) { return null; }
        }

        // dd/mm/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m)) {
            $y = (int)$m[3]; $mo = (int)$m[2]; $d = (int)$m[1];
            if ($y < 1900 || $y > 2100) return null;
            if (!checkdate($mo, $d, $y)) return null;
            return sprintf('%04d-%02d-%02d', $y, $mo, $d);
        }

        // cualquier otro formato -> null
        return null;
    }

    private function toIntNullable($v): ?int
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '' || $s === '?' || $s === '-') return null;
        $s = preg_replace('/[^\d\-]/', '', $s);
        return ($s === '' || !is_numeric($s)) ? null : (int)$s;
    }

    private function cut($v, int $n): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : mb_substr($s, 0, $n);
    }
}
