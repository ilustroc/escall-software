@php
    // Estos nombres se esperan desde el controlador que maneja el SP:
    // $spFi, $spFf, $spPreview, $spCount
@endphp

<section class="rounded-2xl border border-slate-200 bg-white p-4 md:p-5 shadow-sm space-y-3">
    <div>
        <h2 class="text-sm font-semibold text-slate-800">
            Carga por SP (Gestiones)
        </h2>
        <p class="text-[11px] text-slate-500">
            Ejecuta el procedimiento almacenado en un rango de fechas, revisa la vista previa
            y luego importa las gestiones borrando el rango local.
        </p>
    </div>

    {{-- Filtros --}}
    <form method="GET"
          action="{{ route('cargas.sp.preview') }}"
          class="flex flex-wrap items-end gap-3 text-xs">
        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha inicio
            </label>
            <input type="date"
                   name="fi"
                   value="{{ old('fi', $spFi ?? '') }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <div>
            <label class="block text-[11px] font-medium text-slate-700">
                Fecha fin
            </label>
            <input type="date"
                   name="ff"
                   value="{{ old('ff', $spFf ?? '') }}"
                   required
                   class="mt-1 block w-40 rounded-lg border border-slate-300 bg-white px-2 py-1.5 shadow-sm focus:border-red-500 focus:ring-red-500">
        </div>

        <button type="submit"
                class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
            Vista previa
        </button>
    </form>

    @if(!is_null($spPreview ?? null))
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-[11px]">
            <p class="text-slate-700">
                Se muestran hasta <strong>100</strong> filas de vista previa.  
                Total devuelto por el SP:
                <strong>{{ number_format($spCount ?? 0,0,',','.') }}</strong> registros.
            </p>
        </div>

        <div class="overflow-auto max-h-80 rounded-xl border border-slate-200">
            <table class="min-w-full border-collapse text-[11px]">
                <thead class="bg-slate-50">
                    <tr>
                        @foreach (['fecha_gestion','dni','telefono','status','tipificacion','observacion','fecha_pago','monto_pago','nombre'] as $th)
                            <th class="border-b border-slate-200 px-3 py-2 text-left font-semibold text-slate-700">
                                {{ $th }}
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($spPreview as $r)
                        <tr class="odd:bg-white even:bg-slate-50/60">
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['fecha_gestion'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['dni'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['telefono'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['status'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['tipificacion'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5">
                                {{ \Illuminate\Support\Str::limit($r['observacion'] ?? '', 80) }}
                            </td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['fecha_pago'] }}</td>
                            <td class="border-b border-slate-100 px-3 py-1.5 text-right font-mono">
                                {{ $r['monto_pago'] }}
                            </td>
                            <td class="border-b border-slate-100 px-3 py-1.5">{{ $r['nombre'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-3 text-center text-[11px] text-slate-500">
                                Sin filas en el rango.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <form method="POST"
              action="{{ route('cargas.sp.import') }}"
              class="mt-3 text-xs">
            @csrf
            <input type="hidden" name="fi" value="{{ $spFi }}">
            <input type="hidden" name="ff" value="{{ $spFf }}">
            <button type="submit"
                    onclick="return confirm('Se borrarán las gestiones locales entre {{ $spFi }} y {{ $spFf }} y se importarán desde el SP. ¿Continuar?')"
                    class="inline-flex items-center rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-red-700">
                Borrar rango e importar desde SP
            </button>
        </form>
    @endif
</section>
