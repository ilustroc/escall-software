<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\GestionesUploadRequest;
use App\Imports\GestionesImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class GestionesController extends Controller
{
    public function form(): View
    {
        return view('cargas.gestiones');
    }

    public function upload(GestionesUploadRequest $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $file = $request->file('archivo');
        $import = new GestionesImport();

        // Import síncrono (para 2k filas va sobrado)
        Excel::import($import, $file);

        return back()->with(
            'ok',
            "Carga XLSX completada. Procesadas: {$import->processed}, insertadas: {$import->inserted}, omitidas: {$import->skipped}, con error: {$import->failed}."
        );
    }
}
