<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Cargas\GestionesController;
use App\Http\Controllers\Cargas\GestionesSpController;
use App\Http\Controllers\Cargas\DataController;
use App\Http\Controllers\Tablas\GestionesMesController;
use App\Http\Controllers\Tablas\GestionesSemanalController;
use App\Http\Controllers\Reportes\ReporteImpulseController;
use App\Http\Controllers\Reportes\ReporteKpInvestController;
use App\Http\Controllers\Reportes\ReporteTecCenterController;
use App\Http\Controllers\Reportes\ReporteCarterasController;

/*
 |--------------------------------------------------------------------------
 | Rutas públicas (Autenticación)
 |--------------------------------------------------------------------------
 | Rutas accesibles sin iniciar sesión: mostrar formulario de login y procesarlo.
 */
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');         // Mostrar formulario de login
Route::post('/login', [AuthController::class, 'doLogin'])->name('login.post');    // Procesar login

// Logout (requiere token CSRF, se mantiene fuera del GET)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
 |--------------------------------------------------------------------------
 | Rutas protegidas por sesión
 |--------------------------------------------------------------------------
 */
Route::middleware('auth')->group(function () {

    // Dashboard principal
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    /*
     |--------------------------------------------------------------------------
     | Rutas de "cargas"
     |--------------------------------------------------------------------------
     */
    Route::prefix('cargas')->name('cargas.')->group(function () {
        Route::get('/', fn() => view('cargas.index'))->name('index');

        // DATA (vista + uploads)
        Route::get('data', [DataController::class, 'form'])->name('data.form');
        Route::post('data/upload', [DataController::class, 'upload'])->name('data.upload');
        Route::post('data/import-csv', [DataController::class, 'importarCsv'])->name('data.import.csv');

        // Gestiones (si las usas)
        Route::get('gestiones', [GestionesController::class, 'form'])->name('gestiones.form');
        Route::post('gestiones', [GestionesController::class, 'upload'])->name('gestiones.upload');

        // SP (si las usas)
        Route::get('sp', [GestionesSpController::class, 'form'])->name('sp.form');
        Route::get('sp/preview', [GestionesSpController::class, 'preview'])->name('sp.preview');
        Route::post('sp/import', [GestionesSpController::class, 'import'])->name('sp.import');
    });

    /*
     |--------------------------------------------------------------------------
     | Tablas / reportes de gestiones
     |--------------------------------------------------------------------------
     */
    Route::get('/tablas', [GestionesMesController::class, 'index'])->name('tablas.index');
    Route::get('/tablas/gestiones-mes', [GestionesMesController::class, 'index'])->name('tablas.gestiones.mes');
    Route::get('/tablas/semanales', [GestionesSemanalController::class, 'index'])->name('tablas.semanales');

    /*
     |--------------------------------------------------------------------------
     | Tablas / reportes de gestiones
     |--------------------------------------------------------------------------
     */
    Route::get('/reportes', fn() => view('reportes.index'))->name('reportes.index');
    Route::get('/reportes/impulse',        [ReporteImpulseController::class, 'index'])->name('reportes.impulse.index');
    Route::get('/reportes/impulse/export', [ReporteImpulseController::class, 'export'])->name('reportes.impulse.export');
    Route::get('/reportes/kp-invest',        [ReporteKpInvestController::class, 'index'])->name('reportes.kp.index');
    Route::get('/reportes/kp-invest/export', [ReporteKpInvestController::class, 'export'])->name('reportes.kp.export');
    Route::get('/reportes/tec-center',        [ReporteTecCenterController::class, 'index'])->name('reportes.tec.index');
    Route::get('/reportes/tec-center/export', [ReporteTecCenterController::class, 'export'])->name('reportes.tec.export');

    Route::get('/reportes/carteras', [ReporteCarterasController::class, 'index'])->name('reportes.carteras.index');

    // XLSX
    Route::get('/reportes/carteras/export-data', [ReporteCarterasController::class, 'exportData'])
        ->name('reportes.carteras.exportData');

    // CSV rápido (streaming)
    Route::get('/reportes/carteras/export-data-csv', [ReporteCarterasController::class, 'exportDataCsv'])
        ->name('reportes.carteras.exportDataCsv');

    // Asignación TEC Center (placeholder)
    Route::get('/reportes/carteras/export-tec', [ReporteCarterasController::class, 'exportAsignacionTec'])
        ->name('reportes.carteras.exportTec');
        
    Route::get('/reportes/data-tec-center', [ReporteCarterasController::class, 'exportDataTecCenter'])
        ->name('reportes.carteras.exportDataTecCenter');

    Route::middleware('auth')->group(function () {
        Route::get('/reportes/tec-center-data', [ReporteCarterasController::class, 'exportTecCenterData'])
            ->name('reportes.tec.data');
        });
        
    /*
     |--------------------------------------------------------------------------
     | Vistas estáticas / utilitarias
     |--------------------------------------------------------------------------
     | Rutas que sirven vistas simples (reportes, sms).
     */
    Route::get('/sms', fn() => view('sms.index'))->name('sms.index');

});

