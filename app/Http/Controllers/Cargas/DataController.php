<?php

namespace App\Http\Controllers\Cargas;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataUploadRequest;
use App\Imports\DataImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DataController extends Controller
{
    public function form(): View
    {
        return view('cargas.data');
    }

    public function upload(DataUploadRequest $request): RedirectResponse
    {
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $file = $request->file('archivo');
        $import = new DataImport();

        // Import síncrono (para 2k filas va sobrado)
        Excel::import($import, $file);

        return back()->with(
            'ok',
            "Carga XLSX completada. Procesadas: {$import->processed}, insertadas: {$import->inserted}, omitidas: {$import->skipped}, con error: {$import->failed}."
        );
    }
}
