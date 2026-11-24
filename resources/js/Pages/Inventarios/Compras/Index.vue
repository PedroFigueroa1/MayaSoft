<script setup>
import { Link, usePage, useForm, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
  periodo:     Object,
  compras:     Object,
  filters:     Object,
  proveedores: Array,
  totales:     { type: Object, default: () => ({}) },
})

const flash = usePage().props.flash

const filter = useForm({
  buscar:       props.filters?.buscar       || '',
  estado:       props.filters?.estado       || '',
  proveedor_id: props.filters?.proveedor_id || '',
  fecha_desde:  props.filters?.fecha_desde  || '',
  fecha_hasta:  props.filters?.fecha_hasta  || '',
})

const aplicar = () =>
  filter.get(
    route('inventario.compras.index', {
      periodoId: props.periodo.id,
      ...props.filters,
      ...filter.data(),
    }),
    { preserveState: true, preserveScroll: true }
  )

const confirmar = (c) => {
  if (!confirm(`¿Confirmar compra ${c.nro_compra}? El stock ingresará al inventario.`)) return
  router.post(route('inventario.compras.confirmar', { periodoId: props.periodo.id, id: c.id }))
}

const contabilizar = (c) => {
  if (!confirm(`¿Contabilizar compra ${c.nro_compra}? Se generará el asiento EGR.`)) return
  router.post(route('inventario.compras.contabilizar', { periodoId: props.periodo.id, id: c.id }))
}

const estados = {
  borrador:      'bg-gray-100 text-gray-800',
  confirmada:    'bg-blue-100 text-blue-800',
  contabilizada: 'bg-green-100 text-green-800',
  anulada:       'bg-red-100 text-red-800',
}

// ── Helpers ──────────────────────────────────────────────
const fmt = (v) =>
  new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
  }).format(v ?? 0)

const fmtFecha = (f) => {
  if (!f) return '-'
  const solo = String(f).substring(0, 10)
  const [y, m, d] = solo.split('-')
  if (!y || !m || !d) return '-'
  return `${d}/${m}/${y}`
}

// 👇 NUEVO: formatea cantidad sin decimales basura
const fmtCantidad = (v) => {
  const n = Number(v ?? 0)
  return Number.isInteger(n) ? n.toString() : n.toFixed(4).replace(/\.?0+$/, '')
}

const nombreDetalle = (d) => {
  const cod  = d.producto?.codigo ?? ''
  const desc = d.producto?.descripcion ?? 'Producto'
  const uni  = d.producto?.unidad_medida?.codigo ?? ''
  return `${cod} - ${desc} (${fmtCantidad(d.cantidad)}${uni ? ' ' + uni : ''})`
}
</script>

<template>
  <AppLayoutAdmin title="Compras">
    <template #header>
      <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <div>
          <div class="flex items-center gap-2 text-xs text-gray-400 mb-1">
            <span>Inventario</span>
            <span>/</span>
            <span class="text-gray-600">Compras</span>
          </div>
          <h2 class="font-semibold text-xl text-gray-800 leading-tight">Compras</h2>
          <p class="text-xs text-gray-500 mt-0.5">Periodo: {{ periodo.nombre }}</p>
        </div>
        <Link
          :href="route('inventario.compras.create', { periodoId: periodo.id })"
          class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 shadow-sm transition"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva Compra
        </Link>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Flash -->
        <Transition
          enter-active-class="transition ease-out duration-200"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
        >
          <div v-if="flash?.success" class="flex items-center gap-2 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ flash.success }}
          </div>
        </Transition>

        <!-- Resumen -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</span>
              <span class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
              </span>
            </div>
            <div class="text-2xl font-bold text-gray-900 mt-2">{{ totales?.total ?? 0 }}</div>
            <div class="text-xs text-gray-400">compras registradas</div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Monto</span>
              <span class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </span>
            </div>
            <div class="text-lg font-bold text-blue-700 mt-2 font-mono">{{ fmt(totales?.monto_total) }}</div>
            <div class="text-xs text-gray-400">monto total</div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">IVA CF</span>
              <span class="w-8 h-8 rounded-full bg-purple-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                </svg>
              </span>
            </div>
            <div class="text-lg font-bold text-purple-700 mt-2 font-mono">{{ fmt(totales?.total_iva) }}</div>
            <div class="text-xs text-gray-400">crédito fiscal</div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Confirmadas</span>
              <span class="w-8 h-8 rounded-full bg-amber-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </span>
            </div>
            <div class="text-2xl font-bold text-amber-600 mt-2">{{ totales?.confirmadas ?? 0 }}</div>
            <div class="text-xs text-gray-400">pendientes de contabilizar</div>
          </div>

          <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between">
              <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Contabilizadas</span>
              <span class="w-8 h-8 rounded-full bg-green-50 flex items-center justify-center">
                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </span>
            </div>
            <div class="text-2xl font-bold text-green-600 mt-2">{{ totales?.contabilizadas ?? 0 }}</div>
            <div class="text-xs text-gray-400">con asiento EGR</div>
          </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
            <div class="lg:col-span-4">
              <label class="block text-xs font-medium text-gray-500 mb-1">Buscar</label>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input
                  v-model="filter.buscar"
                  @keyup.enter="aplicar"
                  placeholder="Nro compra, factura, proveedor..."
                  class="w-full pl-9 pr-3 py-2 rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                >
              </div>
            </div>
            <div class="lg:col-span-2">
              <label class="block text-xs font-medium text-gray-500 mb-1">Estado</label>
              <select v-model="filter.estado" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option value="borrador">Borrador</option>
                <option value="confirmada">Confirmada</option>
                <option value="contabilizada">Contabilizada</option>
                <option value="anulada">Anulada</option>
              </select>
            </div>
            <div class="lg:col-span-3">
              <label class="block text-xs font-medium text-gray-500 mb-1">Proveedor</label>
              <select v-model="filter.proveedor_id" class="w-full rounded-lg border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Todos</option>
                <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.razon_social }}</option>
              </select>
            </div>
            <div class="lg:col-span-2">
              <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
              <input v-model="filter.fecha_desde" type="date" class="w-full rounded-lg border-gray-200 text-sm">
            </div>
            <div class="lg:col-span-2">
              <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
              <input v-model="filter.fecha_hasta" type="date" class="w-full rounded-lg border-gray-200 text-sm">
            </div>
            <div class="lg:col-span-12 flex justify-end gap-2 pt-1">
              <button
                @click="aplicar"
                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-900 text-white rounded-lg text-sm font-medium hover:bg-gray-800 transition"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filtrar
              </button>
            </div>
          </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
              <thead class="bg-gray-50/50">
                <tr>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Nro.</th>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Proveedor</th>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Productos</th>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Factura</th>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Recepción</th>
                  <th class="px-4 py-3 text-left text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Almacén</th>
                  <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Total</th>
                  <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">IVA CF</th>
                  <th class="px-4 py-3 text-center text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Estado</th>
                  <th class="px-4 py-3 text-right text-[11px] font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-50">
                <tr v-for="c in compras.data" :key="c.id" class="hover:bg-indigo-50/30 transition">
                  <td class="px-4 py-3 text-sm font-mono font-bold text-gray-800">{{ c.nro_compra }}</td>
                  <td class="px-4 py-3 text-sm">
                    <div class="font-medium text-gray-800">{{ c.proveedor?.razon_social }}</div>
                    <div class="text-xs text-gray-400">{{ c.proveedor?.nit }}</div>
                  </td>

                  <!-- Productos -->
                  <td class="px-4 py-3 text-sm max-w-[16rem]">
                    <div v-if="c.detalles && c.detalles.length" class="space-y-1">
                      <div
                        v-for="d in c.detalles"
                        :key="d.id"
                        class="flex items-center gap-1.5 text-xs"
                        :title="nombreDetalle(d)"
                      >
                        <span class="font-mono text-[10px] text-indigo-500 bg-indigo-50 px-1.5 py-0.5 rounded shrink-0">
                          {{ d.producto?.codigo }}
                        </span>
                        <span class="text-gray-700 truncate">{{ d.producto?.descripcion }}</span>
                        <span v-if="d.producto?.unidad_medida" class="text-gray-400 text-[10px] shrink-0">
                          {{ fmtCantidad(d.cantidad) }} {{ d.producto.unidad_medida.codigo }}
                        </span>
                      </div>
                      <div v-if="c.detalles_count > c.detalles.length" class="text-[11px] text-gray-400 italic">
                        +{{ c.detalles_count - c.detalles.length }} más ({{ c.detalles_count }} ítems)
                      </div>
                      <div v-else class="text-[11px] text-gray-400">
                        {{ c.detalles_count }} {{ c.detalles_count === 1 ? 'ítem' : 'ítems' }}
                      </div>
                    </div>
                    <span v-else class="text-xs text-gray-300 italic">Sin items</span>
                  </td>

                  <td class="px-4 py-3 text-sm text-gray-500">
                    <span v-if="c.nro_factura" class="font-mono text-xs">{{ c.nro_factura }}</span>
                    <span v-else class="text-gray-300 italic text-xs">Sin factura</span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">{{ fmtFecha(c.fecha_recepcion) }}</td>
                  <td class="px-4 py-3 text-sm text-gray-500">{{ c.almacen?.nombre }}</td>
                  <td class="px-4 py-3 text-sm text-right font-mono font-semibold text-gray-800">{{ fmt(c.total) }}</td>
                  <td class="px-4 py-3 text-sm text-right font-mono text-purple-700">{{ fmt(c.iva_cf) }}</td>
                  <td class="px-4 py-3 text-center">
                    <span
                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold capitalize"
                      :class="estados[c.estado]"
                    >
                      <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                      {{ c.estado }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-right text-sm">
                    <div class="inline-flex items-center gap-1">
                      <Link
                        :href="route('inventario.compras.show', c.id)"
                        class="p-1.5 text-gray-500 hover:text-indigo-600 hover:bg-indigo-50 rounded-md transition"
                        title="Ver detalle"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                      </Link>
                      <Link
                        v-if="c.estado === 'borrador'"
                        :href="route('inventario.compras.edit', c.id)"
                        class="p-1.5 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 rounded-md transition"
                        title="Editar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                      </Link>
                      <button
                        v-if="c.estado === 'borrador'"
                        @click="confirmar(c)"
                        class="p-1.5 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-md transition"
                        title="Confirmar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                      </button>
                      <button
                        v-if="c.estado === 'confirmada'"
                        @click="contabilizar(c)"
                        class="p-1.5 text-purple-600 hover:text-purple-800 hover:bg-purple-50 rounded-md transition"
                        title="Contabilizar"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!compras.data.length">
                  <td colspan="10" class="px-6 py-16 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    <p class="text-sm font-medium text-gray-500">No hay compras registradas</p>
                    <p class="text-xs text-gray-400 mt-1">Prueba ajustando los filtros o crea una nueva compra</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Paginación -->
          <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap gap-1 justify-end bg-gray-50/30">
            <template v-for="link in compras.links" :key="link.label">
              <Link
                v-if="link.url"
                :href="link.url"
                v-html="link.label"
                :class="[
                  'min-w-[2rem] h-8 px-2 inline-flex items-center justify-center rounded-md text-sm font-medium transition',
                  link.active
                    ? 'bg-indigo-600 text-white shadow-sm'
                    : 'bg-white text-gray-600 border border-gray-200 hover:bg-gray-50',
                ]"
              />
              <span
                v-else
                v-html="link.label"
                class="min-w-[2rem] h-8 px-2 inline-flex items-center justify-center rounded-md text-sm bg-gray-100 text-gray-400 border border-gray-100 cursor-not-allowed"
              />
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
