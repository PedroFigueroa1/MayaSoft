<script setup>
import { computed, reactive, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const imprimir = () => window.print()

const props = defineProps({
  periodo:   { type: Object, required: true },
  reporte:   { type: Object, required: true },
  almacenes: { type: Array,  default: () => [] },
  filters:   { type: Object, default: () => ({}) },
})

const filtros = reactive({
  almacen_id:     props.filters.almacen_id ?? '',
  hasta:          props.filters.hasta ?? '',
  solo_con_saldo: props.filters.solo_con_saldo ? 1 : 0,
})
const buscar = ref('')

const ruta = () => route('inventario.reportes.fisico', props.periodo.id)

function aplicar() {
  router.get(ruta(), { ...filtros, solo_con_saldo: filtros.solo_con_saldo ? 1 : 0 },
    { preserveState: true, replace: true })
}
function corte(fecha) { filtros.hasta = fecha; aplicar() }

// Atajos de corte
const hoy = new Date().toISOString().slice(0, 10)
const finMesAnterior = (() => {
  const d = new Date(); d.setDate(0); return d.toISOString().slice(0, 10)
})()

const exportUrl = computed(() =>
  `${ruta()}?${new URLSearchParams({ ...filtros, export: 'csv' }).toString()}`)

const filas = computed(() => {
  const q = buscar.value.trim().toLowerCase()
  return props.reporte.filas.filter(r =>
    !q || r.codigo.toLowerCase().includes(q) || r.descripcion.toLowerCase().includes(q))
})
const n = (v) => Number(v ?? 0)

const fmt  = (v) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n(v))
const fmtC = (v) => new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(n(v))
const fmtFecha = (f) => f ? new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day: '2-digit', month: 'long', year: 'numeric' }) : ''
const t = computed(() => props.reporte.totales)
const urlKardex = (id) => route('inventario.reportes.kardex.producto', [props.periodo.id, id])
</script>

<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 class="text-xl font-semibold text-gray-800 leading-tight">Inventario físico valorado</h2>
          <p class="text-xs text-gray-500 mt-0.5">
            Al {{ fmtFecha(reporte.hasta) }} · {{ periodo.nombre }}
          </p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('inventario.movimientos.kardex', periodo.id)"
            class="px-3 py-2 text-xs font-semibold rounded-lg border border-gray-300 text-gray-600 hover:bg-gray-50 transition">
            Kardex general
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

      <!-- Tarjetas -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Productos con saldo</div>
          <div class="text-2xl font-bold text-gray-800">{{ reporte.filas.filter(r => n(r.saldo_f) > 0).length }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Unidades en stock</div>
          <div class="text-2xl font-bold text-gray-800">{{ fmtC(t.saldo_f) }}</div>
        </div>
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-4">
          <div class="text-xs text-amber-700">Valor del inventario</div>
          <div class="text-2xl font-bold text-amber-800">Bs {{ fmt(t.saldo_v) }}</div>
        </div>
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500">Inventario inicial</div>
          <div class="text-2xl font-bold text-gray-800">Bs {{ fmt(t.inv_ini_v) }}</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Fecha de corte</label>
          <input type="date" v-model="filtros.hasta" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400" />
        </div>
        <div class="flex gap-1 pb-0.5">
          <button @click="corte(hoy)" class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Hoy</button>
          <button @click="corte(finMesAnterior)" class="px-2.5 py-1.5 text-xs rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">Fin mes anterior</button>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
        <label class="flex items-center gap-2 text-sm text-gray-600 pb-1.5">
          <input type="checkbox" v-model="filtros.solo_con_saldo" :true-value="1" :false-value="0" @change="aplicar" />
          Solo con saldo
        </label>
        <div class="flex-1 min-w-48">
          <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
          <input v-model="buscar" placeholder="Código o producto…"
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400" />
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-xs uppercase tracking-wide">
                <th colspan="2" class="bg-gray-50"></th>
                <th colspan="4" class="px-4 py-1.5 text-center bg-emerald-50 text-emerald-700 border-x">Físico</th>
                <th class="bg-gray-50"></th>
                <th colspan="4" class="px-4 py-1.5 text-center bg-amber-50 text-amber-700 border-l">Valorado (Bs)</th>
              </tr>
              <tr class="bg-gray-50 border-b text-xs font-semibold text-gray-600 uppercase tracking-wide">
                <th class="px-4 py-2 text-left">Código</th>
                <th class="px-4 py-2 text-left">Producto</th>
                <th class="px-4 py-2 text-right border-l">Inv. inicial</th>
                <th class="px-4 py-2 text-right text-green-700">Entradas</th>
                <th class="px-4 py-2 text-right text-red-700">Salidas</th>
                <th class="px-4 py-2 text-right border-r">Saldo</th>
                <th class="px-4 py-2 text-right">Costo prom.</th>
                <th class="px-4 py-2 text-right">Inv. inicial</th>
                <th class="px-4 py-2 text-right text-green-700">Entradas</th>
                <th class="px-4 py-2 text-right text-red-700">Salidas</th>
                <th class="px-4 py-2 text-right">Saldo</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!filas.length">
                <td colspan="11" class="px-6 py-10 text-center text-gray-400">Sin productos para mostrar.</td>
              </tr>
              <tr v-for="r in filas" :key="r.producto_id" class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2 text-xs font-mono text-gray-500">{{ r.codigo }}</td>
                <td class="px-4 py-2">
                  <Link :href="urlKardex(r.producto_id)" class="text-xs font-medium text-gray-800 hover:text-blue-600 hover:underline">
                    {{ r.descripcion }}
                  </Link>
                </td>
                <td class="px-4 py-2 text-right text-xs text-gray-600 border-l">{{ fmtC(r.inv_ini_f) }}</td>
                <td class="px-4 py-2 text-right text-xs text-green-700">{{ n(r.entrada_f) ? fmtC(r.entrada_f) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs text-red-600">{{ n(r.salida_f) ? fmtC(r.salida_f) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs font-bold border-r"
                    :class="n(r.saldo_f) <= 0 ? 'text-red-600' : 'text-gray-800'">{{ fmtC(r.saldo_f) }}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmt(r.costo_prom) }}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-600">{{ fmt(r.inv_ini_v) }}</td>
                <td class="px-4 py-2 text-right text-xs text-green-700">{{ n(r.entrada_v) ? fmt(r.entrada_v) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs text-red-600">{{ n(r.salida_v) ? fmt(r.salida_v) : '' }}</td>
                <td class="px-4 py-2 text-right text-xs font-bold text-gray-800">{{ fmt(r.saldo_v) }}</td>
              </tr>
            </tbody>
            <tfoot>
              <tr class="bg-gray-50 border-t-2 font-bold text-xs">
                <td class="px-4 py-2.5" colspan="2">TOTAL GENERAL</td>
                <td class="px-4 py-2.5 text-right border-l">{{ fmtC(t.inv_ini_f) }}</td>
                <td class="px-4 py-2.5 text-right text-green-700">{{ fmtC(t.entrada_f) }}</td>
                <td class="px-4 py-2.5 text-right text-red-600">{{ fmtC(t.salida_f) }}</td>
                <td class="px-4 py-2.5 text-right border-r">{{ fmtC(t.saldo_f) }}</td>
                <td class="px-4 py-2.5"></td>
                <td class="px-4 py-2.5 text-right">{{ fmt(t.inv_ini_v) }}</td>
                <td class="px-4 py-2.5 text-right text-green-700">{{ fmt(t.entrada_v) }}</td>
                <td class="px-4 py-2.5 text-right text-red-600">{{ fmt(t.salida_v) }}</td>
                <td class="px-4 py-2.5 text-right">{{ fmt(t.saldo_v) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
