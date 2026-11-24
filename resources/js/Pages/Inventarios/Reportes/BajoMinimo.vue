<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Productos bajo mínimo</h2>
          <p class="text-xs text-gray-500 mt-0.5">Alertas de reposición de stock</p>
        </div>
        <button @click="window.print()"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition print:hidden">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Imprimir
        </button>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Filtro almacén -->
      <div class="bg-white rounded-xl border p-3 flex items-end gap-3 print:hidden">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
      </div>

      <!-- Sin alertas -->
      <div v-if="!alertas.length"
        class="bg-green-50 border border-green-200 rounded-xl p-8 text-center">
        <svg class="mx-auto w-12 h-12 text-green-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-green-700 font-medium">¡Todo en orden!</p>
        <p class="text-green-600 text-sm mt-1">No hay productos por debajo del stock mínimo.</p>
      </div>

      <!-- Tabla de alertas -->
      <div v-else class="bg-white rounded-xl border overflow-hidden">
        <div class="px-5 py-3 bg-amber-50 border-b border-amber-200 flex items-center gap-2">
          <svg class="w-4 h-4 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
          </svg>
          <span class="text-sm font-semibold text-amber-800">{{ alertas.length }} producto(s) requieren reposición</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Producto</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Stock actual</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Mínimo</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-red-600 uppercase tracking-wide">Diferencia</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Urgencia</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="a in alertas" :key="`${a.producto}-${a.almacen}`"
                class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-2">
                  <div class="text-xs font-medium text-gray-800">{{ a.producto }}</div>
                  <div class="text-xs text-gray-400 font-mono">{{ a.codigo }}</div>
                </td>
                <td class="px-4 py-2 text-xs text-gray-600">{{ a.almacen }}</td>
                <td class="px-4 py-2 text-right">
                  <span :class="a.stock_actual <= 0 ? 'text-red-700 font-bold' : 'text-amber-700 font-semibold'"
                    class="text-sm">
                    {{ fmtCant(a.stock_actual) }}
                  </span>
                </td>
                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmtCant(a.stock_minimo) }}</td>
                <td class="px-4 py-2 text-right text-xs font-bold text-red-600">
                  {{ fmtCant(a.diferencia) }}
                </td>
                <td class="px-4 py-2 text-center">
                  <span v-if="a.stock_actual <= 0"
                    class="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-800">
                    Sin stock
                  </span>
                  <span v-else-if="a.stock_actual <= a.stock_minimo * 0.25"
                    class="text-xs px-2 py-0.5 rounded-full font-medium bg-red-100 text-red-700">
                    Crítico
                  </span>
                  <span v-else
                    class="text-xs px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700">
                    Bajo
                  </span>
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
import { router } from '@inertiajs/vue3'

export default {
  components: { AppLayoutAdmin },
  props: {
    alertas:  { type: Array, default: () => [] },
    almacenes:{ type: Array, default: () => [] },
    filters:  { type: Object, default: () => ({}) },
  },
  data() {
    return { filtros: { almacen_id: this.filters?.almacen_id || '' } }
  },
  methods: {
    aplicar() {
      router.get(route('reportes.inventario.bajo-minimo'), this.filtros, { preserveState: true, replace: true })
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
    },
  },
}
</script>
