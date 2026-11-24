<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Compras</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
        <Link :href="route('compras.create', periodo.id)"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva Compra
        </Link>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Flash / Errores -->
      <div v-if="$page.props.flash?.success" class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors && Object.keys($page.props.errors).length" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <div v-for="(e,k) in $page.props.errors" :key="k">{{ e }}</div>
      </div>

      <!-- Tarjetas resumen -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-gray-800">{{ totales?.total ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Total compras</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-base font-bold text-blue-700">{{ fmt(totales?.monto_total) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Monto total</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-base font-bold text-purple-700">{{ fmt(totales?.total_iva) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Total IVA CF</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-amber-600">{{ totales?.confirmadas ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Confirmadas</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-green-600">{{ totales?.contabilizadas ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Contabilizadas</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
          <select v-model="filtros.estado" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option value="borrador">Borrador</option>
            <option value="confirmada">Confirmada</option>
            <option value="contabilizada">Contabilizada</option>
            <option value="anulada">Anulada</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Proveedor</label>
          <select v-model="filtros.proveedor_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.razon_social }}</option>
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
        <div class="flex-1 min-w-40">
          <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
          <input v-model="filtros.buscar" @keyup.enter="aplicar" type="text" placeholder="N° compra, factura..."
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <button @click="aplicar" class="px-4 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">Filtrar</button>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">N° Compra</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Proveedor</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">N° Factura</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Fecha</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Total</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">IVA CF</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Estado</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!compras.data.length">
                <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                  No hay compras registradas.
                  <Link :href="route('compras.create', periodo.id)" class="text-blue-600 hover:underline">Registrar la primera</Link>
                </td>
              </tr>
              <tr v-for="c in compras.data" :key="c.id"
                :class="['hover:bg-gray-50 transition-colors', c.estado === 'anulada' ? 'opacity-50' : '']">
                <td class="px-4 py-2">
                  <span class="font-mono text-xs font-semibold text-gray-800">{{ c.nro_compra }}</span>
                </td>
                <td class="px-4 py-2">
                  <div class="text-xs font-medium text-gray-800 max-w-36 truncate" :title="c.proveedor?.razon_social">{{ c.proveedor?.razon_social }}</div>
                  <div class="text-xs text-gray-400">{{ c.proveedor?.nit }}</div>
                </td>
                <td class="px-4 py-2 text-xs text-gray-600">{{ c.almacen?.nombre }}</td>
                <td class="px-4 py-2">
                  <span v-if="c.nro_factura" class="font-mono text-xs text-gray-700">{{ c.nro_factura }}</span>
                  <span v-else class="text-xs text-gray-300 italic">Sin factura</span>
                </td>
                <td class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">{{ fmtFecha(c.fecha_recepcion) }}</td>
                <td class="px-4 py-2 text-right text-xs font-semibold text-gray-800">{{ fmt(c.total) }}</td>
                <td class="px-4 py-2 text-right text-xs text-purple-700">{{ fmt(c.iva_cf) }}</td>
                <td class="px-4 py-2 text-center">
                  <span :class="estadoBadge(c.estado)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ estadoLabel(c.estado) }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex items-center justify-center gap-1">
                    <Link :href="route('compras.show', [periodo.id, c.id])"
                      class="p-1 text-gray-500 hover:text-blue-600 transition" title="Ver">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </Link>
                    <!-- Confirmar (solo borradores) -->
                    <button v-if="c.estado === 'borrador'" @click="confirmar(c)"
                      class="px-2 py-0.5 bg-green-600 hover:bg-green-700 text-white text-xs rounded font-medium transition" title="Confirmar e ingresar al stock">
                      Confirmar
                    </button>
                    <!-- Contabilizar (solo confirmadas) -->
                    <button v-if="c.estado === 'confirmada'" @click="contabilizar(c)"
                      class="px-2 py-0.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded font-medium transition">
                      Contabilizar
                    </button>
                    <!-- Editar (solo borradores) -->
                    <Link v-if="c.estado === 'borrador'" :href="route('compras.edit', [periodo.id, c.id])"
                      class="p-1 text-gray-500 hover:text-amber-600 transition" title="Editar">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="compras.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
          <div class="text-xs text-gray-500">{{ compras.from }}–{{ compras.to }} de {{ compras.total }}</div>
          <div class="flex gap-1">
            <Link v-for="link in compras.links" :key="link.label" :href="link.url || '#'"
              :class="['px-2.5 py-1 text-xs rounded border transition',
                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50',
                !link.url ? 'opacity-40 cursor-not-allowed' : '']"
              v-html="link.label"/>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    periodo:    { type: Object, required: true },
    compras:    { type: Object, required: true },
    totales:    { type: Object, default: () => ({}) },
    proveedores:{ type: Array, default: () => [] },
    filters:    { type: Object, default: () => ({}) },
  },
  data() {
    return {
      filtros: {
        estado:       this.filters.estado || '',
        proveedor_id: this.filters.proveedor_id || '',
        desde:        this.filters.desde || '',
        hasta:        this.filters.hasta || '',
        buscar:       this.filters.buscar || '',
      },
    }
  },
  methods: {
    aplicar() {
      router.get(route('compras.index', this.periodo.id), this.filtros, { preserveState: true, replace: true })
    },
    confirmar(c) {
      if (!confirm(`¿Confirmar compra ${c.nro_compra}? El stock ingresará al inventario.`)) return
      router.post(route('compras.confirmar', [this.periodo.id, c.id]))
    },
    contabilizar(c) {
      if (!confirm(`¿Contabilizar compra ${c.nro_compra}? Se generará el asiento EGR.`)) return
      router.post(route('compras.contabilizar', [this.periodo.id, c.id]))
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
    },
    fmtFecha(f) {
      if (!f) return '-'
      return new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' })
    },
    estadoLabel(e) {
      return { borrador: 'Borrador', confirmada: 'Confirmada', contabilizada: 'Contabilizada', anulada: 'Anulada' }[e] ?? e
    },
    estadoBadge(e) {
      return { borrador: 'bg-gray-100 text-gray-700', confirmada: 'bg-amber-100 text-amber-800', contabilizada: 'bg-green-100 text-green-800', anulada: 'bg-red-100 text-red-800' }[e] ?? 'bg-gray-100 text-gray-700'
    },
  },
}
</script>
