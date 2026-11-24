<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">Stock valorizado</h2>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos los almacenes</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
        <button @click="aplicar" class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">
          Actualizar
        </button>
        <button @click="imprimir" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Imprimir
        </button>
      </div>

      <!-- Resumen totales -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border p-4 text-center">
          <div class="text-2xl font-bold text-gray-800">{{ stock.length }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Productos con stock</div>
        </div>
        <div class="bg-blue-50 rounded-xl border border-blue-200 p-4 text-center">
          <div class="text-xl font-bold text-blue-800">{{ fmt(totalCosto) }}</div>
          <div class="text-xs text-blue-600 mt-0.5">Valor en costo (inventario)</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4 text-center">
          <div class="text-xl font-bold text-green-800">{{ fmt(totalVenta) }}</div>
          <div class="text-xs text-green-600 mt-0.5">Valor potencial de venta</div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Código</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Producto</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Categoría</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Cantidad</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Costo prom.</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-blue-700 uppercase tracking-wide">Valor costo</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">P. Venta</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-green-700 uppercase tracking-wide">Valor venta</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!stock.length">
                <td colspan="9" class="px-6 py-12 text-center text-gray-400">No hay stock registrado.</td>
              </tr>
              <tr v-for="s in stock" :key="`${s.producto_id}-${s.almacen}`"
                class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2">
                  <span class="font-mono text-xs bg-gray-100 px-1.5 py-0.5 rounded">{{ s.codigo }}</span>
                </td>
                <td class="px-4 py-2 text-xs font-medium text-gray-800 max-w-48 truncate" :title="s.descripcion">
                  {{ s.descripcion }}
                </td>
                <td class="px-4 py-2 text-xs text-gray-500">{{ s.categoria || '—' }}</td>
                <td class="px-4 py-2 text-xs text-gray-600">{{ s.almacen }}</td>
                <td class="px-4 py-2 text-right">
                  <span :class="s.cantidad <= 0 ? 'text-red-600 font-semibold' : 'text-gray-800'"
                    class="text-xs">{{ fmtCant(s.cantidad) }} {{ s.unidad }}</span>
                </td>
                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmt(s.costo_promedio) }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-blue-700">{{ fmt(s.valor_total) }}</td>
                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmt(s.precio_venta) }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-green-700">{{ fmt(s.valor_venta) }}</td>
              </tr>
            </tbody>
            <tfoot v-if="stock.length">
              <tr class="bg-gray-100 border-t-2 font-bold">
                <td colspan="6" class="px-4 py-2.5 text-right text-sm text-gray-700">TOTALES:</td>
                <td class="px-4 py-2.5 text-right text-sm text-blue-800">{{ fmt(totalCosto) }}</td>
                <td class="px-4 py-2.5"></td>
                <td class="px-4 py-2.5 text-right text-sm text-green-800">{{ fmt(totalVenta) }}</td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { router } from '@inertiajs/vue3'

export default {
  components: { AppLayoutAdmin },
  props: {
    stock:     { type: Array, default: () => [] },
    totalCosto:{ type: Number, default: 0 },
    totalVenta:{ type: Number, default: 0 },
    almacenes: { type: Array, default: () => [] },
    filtros:   { type: Object, default: () => ({}) },
  },
  data() {
    return { filtros: { almacen_id: this.filtros?.almacen_id || '' } }
  },
  methods: {
    aplicar() {
      router.get(route('reportes.inventario.stock'), this.filtros, { preserveState: true, replace: true })
    },
    imprimir() { window.print() },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
    },
  },
}
</script>
