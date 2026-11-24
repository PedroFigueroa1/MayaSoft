<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Kardex: {{ producto.descripcion }}</h2>
          <p class="text-xs text-gray-500 mt-0.5 font-mono">{{ producto.codigo }} · {{ producto.unidad_medida?.nombre }}</p>
        </div>
        <Link :href="route('productos.show', producto.id)"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition">
          ← Volver al producto
        </Link>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Período</label>
          <select v-model="filtros.periodo_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option v-for="p in periodos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos los almacenes</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
          <input type="date" v-model="filtros.desde" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
          <input type="date" v-model="filtros.hasta" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <button @click="aplicar" class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">Filtrar</button>
      </div>

      <!-- Resumen -->
      <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-gray-800">{{ kardex.length }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Movimientos</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-blue-700">{{ fmtCant(saldoFinal) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Saldo final</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-lg font-bold text-green-700">{{ fmt(metodoCosto === 'PROM' ? (kardex.at(-1)?.costo_unitario ?? 0) * saldoFinal : null) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Valor en inventario (aprox.)</div>
        </div>
      </div>

      <!-- Tabla Kardex -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Fecha</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Tipo</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Entrada</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Salida</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Costo Unit.</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Saldo</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Motivo / Referencia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!kardex.length">
                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                  Este producto no tiene movimientos registrados en el período seleccionado.
                </td>
              </tr>
              <tr v-for="m in kardex" :key="m.id"
                :class="['hover:bg-gray-50 transition-colors', m.estado === 'anulado' ? 'opacity-50 line-through' : '']">
                <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ fmtFecha(m.fecha) }}</td>
                <td class="px-3 py-2">
                  <span :class="esEntrada(m.tipo) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                    class="text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                    {{ tipoLabel(m.tipo) }}
                  </span>
                </td>
                <td class="px-3 py-2 text-xs text-gray-600">{{ m.almacen?.nombre }}</td>
                <td class="px-3 py-2 text-right text-xs font-semibold text-green-700">
                  {{ esEntrada(m.tipo) ? fmtCant(m.cantidad) : '' }}
                </td>
                <td class="px-3 py-2 text-right text-xs font-semibold text-red-700">
                  {{ !esEntrada(m.tipo) ? fmtCant(m.cantidad) : '' }}
                </td>
                <td class="px-3 py-2 text-right text-xs text-gray-600">{{ fmt(m.costo_unitario) }}</td>
                <td class="px-3 py-2 text-right text-xs font-bold text-gray-800">{{ fmtCant(m.saldo_acumulado) }}</td>
                <td class="px-3 py-2 text-xs text-gray-600 max-w-60 truncate" :title="m.motivo">
                  {{ m.motivo || '-' }}
                  <span v-if="m.referencia_tipo" class="text-gray-400">· {{ m.referencia_tipo }} #{{ m.referencia_id }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

const LABELS = {
  entrada_compra: 'Entrada compra', entrada_devolucion: 'Devolución cliente',
  entrada_produccion: 'Entrada producción', entrada_manual: 'Entrada manual',
  entrada_traspaso: 'Entrada traspaso',
  salida_venta: 'Salida venta', salida_consumo: 'Consumo interno',
  salida_merma: 'Merma', salida_devolucion: 'Devolución proveedor',
  salida_produccion: 'Salida producción', salida_traspaso: 'Salida traspaso',
  ajuste_positivo: 'Ajuste positivo', ajuste_negativo: 'Ajuste negativo',
}
const ENTRADAS = ['entrada_compra','entrada_devolucion','entrada_produccion','entrada_manual','entrada_traspaso','ajuste_positivo']

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    producto:  { type: Object, required: true },
    kardex:    { type: Array, default: () => [] },
    almacenes: { type: Array, default: () => [] },
    periodos:  { type: Array, default: () => [] },
    periodoId: { type: [Number, String], default: null },
    filters:   { type: Object, default: () => ({}) },
  },
  data() {
    return {
      filtros: {
        periodo_id: this.periodoId,
        almacen_id: this.filters.almacen_id || '',
        desde:      this.filters.desde || '',
        hasta:      this.filters.hasta || '',
      },
    }
  },
  computed: {
    saldoFinal() {
      return this.kardex.length ? parseFloat(this.kardex.at(-1).saldo_acumulado) : 0
    },
    metodoCosto() {
      return this.producto.metodo_costeo
    },
  },
  methods: {
    aplicar() {
      router.get(route('productos.kardex', this.producto.id), this.filtros, { preserveState: true, replace: true })
    },
    esEntrada(tipo) { return ENTRADAS.includes(tipo) },
    tipoLabel(tipo) { return LABELS[tipo] ?? tipo },
    fmt(v) {
      if (v === null || v === undefined) return '-'
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
    },
    fmtFecha(f) {
      if (!f) return '-'
      return new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' })
    },
  },
}
</script>
