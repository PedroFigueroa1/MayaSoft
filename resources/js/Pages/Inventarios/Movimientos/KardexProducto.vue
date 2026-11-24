<script setup>
import { computed, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const imprimir = () => window.print()

const props = defineProps({
  periodo:   { type: Object, required: true },
  producto:  { type: Object, required: true },
  kardex:    { type: Object, required: true },
  almacenes: { type: Array,  default: () => [] },
  productos: { type: Array,  default: () => [] },
  filters:   { type: Object, default: () => ({}) },
})

const filtros = reactive({
  almacen_id: props.filters.almacen_id ?? '',
  desde:      props.filters.desde ?? '',
  hasta:      props.filters.hasta ?? '',
})
const productoId = reactive({ v: props.producto.id })

const ENTRADAS = ['entrada_compra','entrada_devolucion','entrada_produccion','entrada_manual','entrada_traspaso','ajuste_positivo']

const urlBase = (id) => route('inventario.reportes.kardex.producto', [props.periodo.id, id])

function aplicar() {
  router.get(urlBase(productoId.v), { ...filtros }, { preserveState: true, replace: true })
}
function cambiarProducto() {
  router.get(urlBase(productoId.v), { ...filtros }, { replace: true })
}
const exportUrl = computed(() => {
  const qs = new URLSearchParams({ ...filtros, export: 'csv' })
  return `${urlBase(props.producto.id)}?${qs.toString()}`
})

const k = computed(() => props.kardex)
const costoProm = computed(() => k.value.totales.saldo_f > 0 ? k.value.totales.saldo_v / k.value.totales.saldo_f : 0)

const fmt  = (v) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v ?? 0)
const fmtC = (v) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
const fmtFecha = (f) => f ? new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' }) : '—'

function badge(t) {
  if (ENTRADAS.includes(t)) return 'bg-green-100 text-green-800'
  if (t === 'salida_venta') return 'bg-blue-100 text-blue-800'
  if (t === 'salida_merma') return 'bg-red-100 text-red-800'
  return 'bg-orange-100 text-orange-800'
}
</script>

<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 class="text-xl font-semibold text-gray-800 leading-tight">
            Kardex · <span class="font-mono text-blue-700">{{ producto.codigo }}</span> {{ producto.descripcion }}
          </h2>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ periodo.nombre }} · Unidad: {{ producto.unidad_medida?.codigo ?? '—' }} · Método: {{ producto.metodo_costeo }}
          </p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('inventario.movimientos.kardex', periodo.id)"
            class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
            ← Kardex general
          </Link>
          <a :href="exportUrl"
            class="px-3 py-2 text-xs font-semibold rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 transition">
            Exportar a Excel
          </a>
          <button @click="imprimir"
            class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
            Imprimir
          </button>
        </div>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Alerta de conciliación kardex ↔ stock -->
      <div v-if="k.conciliacion?.cuadra === false"
        class="p-3 bg-amber-50 border border-amber-300 text-amber-900 rounded-lg text-sm">
        <strong>El kardex no cuadra con el stock del sistema.</strong>
        Kardex: {{ fmtC(k.totales.saldo_f) }} · Stock actual: {{ fmtC(k.conciliacion.stock_sistema) }}
        (diferencia {{ fmtC(k.conciliacion.dif_cantidad) }}). Revisa movimientos anulados o ajustes pendientes.
      </div>

      <!-- Tarjetas resumen -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Saldo inicial</div>
          <div class="text-lg font-bold text-gray-800">{{ fmtC(k.saldo_inicial.cantidad) }}</div>
          <div class="text-xs text-gray-400">Bs {{ fmt(k.saldo_inicial.valor) }}</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4">
          <div class="text-xs text-green-700">Entradas</div>
          <div class="text-lg font-bold text-green-800">{{ fmtC(k.totales.entrada_f) }}</div>
          <div class="text-xs text-green-700/70">Bs {{ fmt(k.totales.entrada_v) }}</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4">
          <div class="text-xs text-red-700">Salidas</div>
          <div class="text-lg font-bold text-red-800">{{ fmtC(k.totales.salida_f) }}</div>
          <div class="text-xs text-red-700/70">Bs {{ fmt(k.totales.salida_v) }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Saldo físico</div>
          <div class="text-lg font-bold text-gray-800">{{ fmtC(k.totales.saldo_f) }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Saldo valorado</div>
          <div class="text-lg font-bold text-gray-800">Bs {{ fmt(k.totales.saldo_v) }}</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4">
          <div class="text-xs text-blue-700">Costo promedio</div>
          <div class="text-lg font-bold text-blue-800">Bs {{ fmt(costoProm) }}</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-52">
          <label class="block text-xs font-medium text-gray-600 mb-1">Producto</label>
          <select v-model="productoId.v" @change="cambiarProducto"
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} — {{ p.descripcion }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
          <input type="date" v-model="filtros.desde" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400" />
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
          <input type="date" v-model="filtros.hasta" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400" />
        </div>
      </div>

      <!-- Tabla: mismas columnas que la hoja KARDEX del Excel -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-xs uppercase tracking-wide">
                <th colspan="4" class="px-4 py-1.5 bg-gray-50"></th>
                <th colspan="3" class="px-4 py-1.5 text-center bg-emerald-50 text-emerald-700 border-x">Físico</th>
                <th class="px-4 py-1.5 bg-gray-50"></th>
                <th colspan="3" class="px-4 py-1.5 text-center bg-amber-50 text-amber-700 border-l">Valorado (Bs)</th>
              </tr>
              <tr class="bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase tracking-wide">
                <th class="px-4 py-2 text-left">Fecha</th>
                <th class="px-4 py-2 text-left">Operación</th>
                <th class="px-4 py-2 text-left">Comprobante</th>
                <th class="px-4 py-2 text-left">Almacén</th>
                <th class="px-4 py-2 text-right text-green-700 border-l">Entrada</th>
                <th class="px-4 py-2 text-right text-red-700">Salida</th>
                <th class="px-4 py-2 text-right border-r">Saldo</th>
                <th class="px-4 py-2 text-right">Costo unit.</th>
                <th class="px-4 py-2 text-right text-green-700">Entrada</th>
                <th class="px-4 py-2 text-right text-red-700">Salida</th>
                <th class="px-4 py-2 text-right">Saldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <!-- Saldo inicial -->
              <tr class="bg-gray-50/70 font-medium">
                <td class="px-4 py-2 text-xs text-gray-500">{{ fmtFecha(filtros.desde) }}</td>
                <td class="px-4 py-2 text-xs" colspan="3">Saldo inicial</td>
                <td class="px-4 py-2 border-l"></td><td class="px-4 py-2"></td>
                <td class="px-4 py-2 text-right text-xs border-r">{{ fmtC(k.saldo_inicial.cantidad) }}</td>
                <td class="px-4 py-2"></td><td class="px-4 py-2"></td><td class="px-4 py-2"></td>
                <td class="px-4 py-2 text-right text-xs">{{ fmt(k.saldo_inicial.valor) }}</td>
              </tr>

              <tr v-if="!k.lineas.length">
                <td colspan="11" class="px-6 py-10 text-center text-gray-400">Sin movimientos en este rango.</td>
              </tr>

              <tr v-for="l in k.lineas" :key="l.id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ fmtFecha(l.fecha) }}</td>
                <td class="px-4 py-2">
                  <span :class="badge(l.tipo)" class="inline-block text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                    {{ l.operacion }}
                  </span>
                </td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ l.comprobante ?? '—' }}</td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ l.almacen }}</td>

                <td class="px-4 py-2 text-right text-xs font-semibold text-green-700 border-l">{{ l.entrada_f != null ? '+' + fmtC(l.entrada_f) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-red-600">{{ l.salida_f != null ? '−' + fmtC(l.salida_f) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs font-bold text-gray-800 border-r">{{ fmtC(l.saldo_f) }}</td>

                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmt(l.costo_unit) }}</td>

                <td class="px-4 py-2 text-right text-xs text-green-700">{{ l.entrada_v != null ? fmt(l.entrada_v) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs text-red-600">{{ l.salida_v != null ? fmt(l.salida_v) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs font-bold text-gray-800">{{ fmt(l.saldo_v) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-gray-50 border-t-2 font-bold text-xs">
                <td class="px-4 py-2.5" colspan="4">TOTALES</td>
                <td class="px-4 py-2.5 text-right text-green-700 border-l">{{ fmtC(k.totales.entrada_f) }}</td>
                <td class="px-4 py-2.5 text-right text-red-600">{{ fmtC(k.totales.salida_f) }}</td>
                <td class="px-4 py-2.5 text-right border-r">{{ fmtC(k.totales.saldo_f) }}</td>
                <td class="px-4 py-2.5"></td>
                <td class="px-4 py-2.5 text-right text-green-700">{{ fmt(k.totales.entrada_v) }}</td>
                <td class="px-4 py-2.5 text-right text-red-600">{{ fmt(k.totales.salida_v) }}</td>
                <td class="px-4 py-2.5 text-right">{{ fmt(k.totales.saldo_v) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
