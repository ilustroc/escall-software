<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Reporte KP INVEST
        </h2>
        <p class="text-[11px] text-slate-500">
            Gestiones consolidadas para KP INVEST.
        </p>
    </div>

    {{-- Filtros --}}
    <form method="GET"
          action="{{ route('reportes.kp.index') }}"
          class="flex flex-wrap items-end gap-3 text-xs">
        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha inicio
            </label>
            <input type="date"
                   name="fi"
                   value="{{ $kpFi ?? '' }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha fin
            </label>
            <input type="date"
                   name="ff"
                   value="{{ $kpFf ?? '' }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
            Buscar
        </button>

        @if(($kpCount ?? 0) > 0)
            <a href="{{ route('reportes.kp.export', ['fi'=>$kpFi ?? '', 'ff'=>$kpFf ?? '']) }}"
               class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Exportar Excel
            </a>
        @endif
    </form>
</section>

