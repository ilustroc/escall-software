<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Cargar Gestiones
        </h2>
        <p class="text-[11px] text-slate-500">
            Sube un archivo XLSX con las gestiones realizadas para insertarlas en la tabla local.
        </p>
    </div>

    <div class="rounded-lg bg-slate-50 border border-dashed border-slate-200 p-3 text-[11px]">
        <p class="font-semibold text-slate-700 mb-1">Encabezados requeridos:</p>
        <code class="break-words text-[11px] text-slate-700">
            fecha_gestion | dni | telefono | status | tipificacion | observacion | fecha_pago | monto_pago | nombre
        </code>
    </div>

    <form method="POST"
          action="{{ route('cargas.gestiones.upload') }}"
          enctype="multipart/form-data"
          class="flex flex-wrap items-end gap-3 text-xs">
        @csrf

        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Archivo XLSX
            </label>
            <input type="file"
                   name="archivo"
                   accept=".xlsx"
                   required
                   class="mt-1 block w-64 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[11px] shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
            Subir e importar
        </button>
    </form>
</section>
