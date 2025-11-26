<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Cargar Data de Cartera
        </h2>
        <p class="text-[11px] text-slate-500">
            Sube la base maestra de clientes y deudas. Puedes usar XLSX o un CSV rápido.
        </p>
    </div>

    <div class="rounded-lg bg-slate-50 border border-dashed border-slate-200 p-3 text-[11px]">
        <p class="font-semibold text-slate-700 mb-1">Encabezados requeridos:</p>
        <code class="break-words text-[11px] text-slate-700">
            CODIGO | DNI | TITULAR | CARTERA | ENTIDAD | COSECHA | SUB_CARTERA | PRODUCTO |
            SUB_PRODUCTO | HISTORICO | DEPARTAMENTO | DEUDA_TOTAL | DEUDA_CAPITAL |
            CAMPAÑA | PORCENTAJE
        </code>
    </div>

    {{-- XLSX --}}
    <form method="POST"
          action="{{ route('cargas.data.upload') }}"
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
                   class="mt-1 block w-72 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[11px] shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
            Subir e importar XLSX
        </button>
    </form>

    {{-- CSV --}}
    <form method="POST"
          action="{{ route('cargas.data.import.csv') }}"
          enctype="multipart/form-data"
          class="flex flex-wrap items-end gap-3 text-xs">
        @csrf

        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Archivo CSV
            </label>
            <input type="file"
                   name="csv"
                   accept=".csv,text/csv,text/plain"
                   required
                   class="mt-1 block w-72 rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-[11px] shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
            Importar CSV
        </button>
    </form>
</section>
