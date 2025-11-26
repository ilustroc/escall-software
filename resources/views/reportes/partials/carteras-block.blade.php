<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-4">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Reportes de Carteras
        </h2>
        <p class="text-[11px] text-slate-500">
            Genera los reportes consolidados por mes de trabajo.
        </p>
    </div>

    <div class="flex flex-col gap-4 text-xs">
        {{-- XLSX (formateado) --}}
        <form method="GET"
              action="{{ route('reportes.carteras.exportData') }}"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-700">
                    Etiqueta del archivo (p.ej. OCTUBRE25)
                </label>
                <input type="text"
                       name="tag"
                       value="{{ $tag ?? '' }}"
                       class="mt-1 block w-44 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-700">
                    Mes de trabajo (YYYY-MM)
                </label>
                <input type="month"
                       name="mes"
                       value="{{ $mes ?? '' }}"
                       class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>

            <div class="space-y-1">
                <button class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700"
                        type="submit">
                    Generar <strong class="ml-1 font-semibold">REPORTE DATA</strong>
                </button>
                <div class="text-[11px] text-slate-500">
                    Nombre: <code>REPORTE {{ $tag ?? '' }} ESCALL.xlsx</code>
                </div>
            </div>
        </form>

        {{-- CSV rápido --}}
        <form method="GET"
              action="{{ route('reportes.carteras.exportDataCsv') }}"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-700">
                    Etiqueta del archivo (p.ej. OCTUBRE25)
                </label>
                <input type="text"
                       name="tag"
                       value="{{ $tag ?? '' }}"
                       class="mt-1 block w-44 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>

            <div>
                <label class="block text-[11px] font-medium text-slate-700">
                    Mes de trabajo (YYYY-MM)
                </label>
                <input type="month"
                       name="mes"
                       value="{{ $mes ?? '' }}"
                       class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>

            <div class="space-y-1">
                <button class="inline-flex items-center rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-slate-900"
                        type="submit">
                    Generar <strong class="ml-1 font-semibold">REPORTE DATA (CSV)</strong>
                </button>
                <div class="text-[11px] text-slate-500">
                    Nombre: <code>REPORTE {{ $tag ?? '' }} ESCALL.csv</code> — más veloz para volúmenes grandes
                </div>
            </div>
        </form>

        {{-- Data TEC desde Carteras --}}
        <form method="GET"
              action="{{ route('reportes.tec.data') }}"
              class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-[11px] font-medium text-slate-700">
                    Mes de trabajo (YYYY-MM)
                </label>
                <input type="month"
                       name="mes"
                       value="{{ $mes ?? now()->format('Y-m') }}"
                       class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
            </div>

            <button class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-emerald-700"
                    type="submit">
                Generar <strong class="ml-1 font-semibold">REPORTE DATA TEC CENTER</strong>
            </button>
        </form>
    </div>

    <p class="text-[11px] text-slate-500">
        El <em>REPORTE DATA</em> usa el mes seleccionado (YYYY-MM). MC/SITUACIÓN/TELEFONOS se calcula con la mejor gestión
        <span class="font-semibold">fuera</span> de ese mes; “Última”, “Mejor” e “Intensidad” se calculan
        <span class="font-semibold">dentro</span> de ese mes.
    </p>
</section>
