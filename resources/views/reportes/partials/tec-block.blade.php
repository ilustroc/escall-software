<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Reporte TEC CENTER
        </h2>
        <p class="text-[11px] text-slate-500">
            Formato FRMT_GESTIONES CP para la cartera TEC CENTER.
        </p>
    </div>

    <form method="GET"
          action="{{ route('reportes.tec.index') }}"
          class="flex flex-wrap items-end gap-3 text-xs">
        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha inicio
            </label>
            <input type="date"
                   name="fi"
                   value="{{ $tecFi ?? '' }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha fin
            </label>
            <input type="date"
                   name="ff"
                   value="{{ $tecFf ?? '' }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
            Buscar
        </button>

        @if(($tecCount ?? 0) > 0)
            <a href="{{ route('reportes.tec.export', ['fi'=>$tecFi ?? '', 'ff'=>$tecFf ?? '']) }}"
               class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50">
                Exportar Excel (FRMT_GESTIONES CP)
            </a>
        @endif
    </form>
</section>
