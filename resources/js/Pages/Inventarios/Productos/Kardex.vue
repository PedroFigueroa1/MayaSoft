<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
  producto: Object,
  movimientos: Array,
  almacenes: Array,
  periodos: Array,
  filters: Object,
})

const filter = useForm({
  periodo_id: props.filters?.periodo_id || '',
  almacen_id: props.filters?.almacen_id || '',
  fecha_desde: props.filters?.fecha_desde || '',
  fecha_hasta: props.filters?.fecha_hasta || '',
})

const aplicar = () => filter.get(route('inventario.productos.kardex', props.producto.id), { preserveState: true, preserveScroll: true })

// Saldo acumulado (ENTRADAS suman, SALIDAS/VENTAS restan)
const esEntrada = (tipo) =>
  typeof tipo === 'string' &&
  (tipo.startsWith('entrada') || tipo.startsWith('compra') || tipo.startsWith('ingreso'))

const movimientosConSaldo = computed(() => {
  let saldo = 0
  return props.movimientos.map(m => {
    const entrada = esEntrada(m.tipo)
    saldo += entrada ? Number(m.cantidad) : -Number(m.cantidad)
    return { ...m, saldo, es_entrada: entrada }
  })
})
const formatearFecha = (fecha) => {
  if (!fecha) return '—'

  // Si viene como 2026-09-20T04:00:00.000000Z
  return fecha.substring(0, 10).split('-').reverse().join('/')
}
const totalEntradas = computed(() =>
  movimientosConSaldo.value.filter(m => m.es_entrada).reduce((s, m) => s + Number(m.cantidad), 0)
)
const totalSalidas = computed(() =>
  movimientosConSaldo.value.filter(m => !m.es_entrada).reduce((s, m) => s + Number(m.cantidad), 0)
)
</script>

<template>
  <AppLayoutAdmin :title="'Kardex: ' + producto.descripcion">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kardex — {{ producto.descripcion }}</h2>
        <Link :href="route('inventario.productos.show', producto.id)" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">Volver al producto</Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Periodo</label>
              <select v-model="filter.periodo_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todos</option>
                <option v-for="p in periodos" :key="p.id" :value="p.id">{{ p.nombre }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Almacén</label>
              <select v-model="filter.almacen_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todos</option>
                <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Desde</label>
              <input v-model="filter.fecha_desde" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Hasta</label>
              <input v-model="filter.fecha_hasta" type="date" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <button @click="aplicar" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-900">Filtrar</button>
          </div>
        </div>

        <!-- Resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Total Entradas</div>
            <div class="text-2xl font-bold text-green-600">+{{ totalEntradas.toFixed(2) }}</div>
          </div>
          <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Total Salidas</div>
            <div class="text-2xl font-bold text-red-600">-{{ totalSalidas.toFixed(2) }}</div>
          </div>
          <div class="bg-white shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Saldo Final</div>
            <div class="text-2xl font-bold text-gray-900">
              {{ (movimientosConSaldo.length ? movimientosConSaldo[movimientosConSaldo.length - 1].saldo : 0).toFixed(2) }}
              <span class="text-sm font-normal text-gray-500">{{ producto.unidad_medida?.nombre }}</span>
            </div>
          </div>
        </div>

        <!-- Tabla Kardex -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Entrada</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Salida</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Saldo</th>
                  <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Costo Unit.</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Documento</th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="m in movimientosConSaldo" :key="m.id" class="hover:bg-gray-50">
                  <td class="px-4 py-2 text-sm text-gray-900 whitespace-nowrap">{{ formatearFecha(m.fecha) }}</td>
                  <td class="px-4 py-2 text-sm">
                    <span class="px-2 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-800">{{ m.tipo }}</span>
                  </td>
                  <td class="px-4 py-2 text-sm text-gray-500 whitespace-nowrap">
                    {{ m.almacen?.nombre }}
                    <span v-if="m.almacen_destino" class="text-xs">→ {{ m.almacen_destino.nombre }}</span>
                  </td>
                <td class="...text-green-600">
                {{ m.es_entrada ? Number(m.cantidad).toFixed(2) : '' }}
                </td>
                  <td class="px-4 py-2 text-sm text-right font-mono text-red-600">
                    {{ !m.es_entrada ? Number(m.cantidad).toFixed(2) : '' }}
                  </td>
                  <td class="px-4 py-2 text-sm text-right font-mono font-bold text-gray-900">{{ m.saldo.toFixed(2) }}</td>
                  <td class="px-4 py-2 text-sm text-right font-mono text-gray-500">{{ Number(m.costo_unitario).toFixed(4) }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500">{{ m.documento_referencia || '—' }}</td>
                  <td class="px-4 py-2 text-sm text-gray-500 max-w-xs truncate">{{ m.concepto || '—' }}</td>
                </tr>
                <tr v-if="!movimientosConSaldo.length">
                  <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">No hay movimientos con los filtros aplicados.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
