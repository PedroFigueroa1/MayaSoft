<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Ventas</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
        <div class="flex gap-2">
          <!-- Contabilizar masivo -->
          <button v-if="pendientesContabilizar > 0" @click="modalContabilizar = true"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-purple-600 text-white text-xs font-semibold rounded-lg hover:bg-purple-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
            Contabilizar pendientes ({{ pendientesContabilizar }})
          </button>
          <Link :href="route('pos.index')"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7H6a2 2 0 00-2 2v9a2 2 0 002 2h9a2 2 0 002-2v-3M9 7V5a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2h-2M9 7h6"/></svg>
            Ir al POS
          </Link>
        </div>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Flash -->
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
          <div class="text-xs text-gray-500 mt-0.5">Total ventas</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-base font-bold text-green-700">{{ fmt(totales?.monto) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Monto total</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-base font-bold text-purple-700">{{ fmt(totales?.iva) }}</div>
          <div class="text-xs text-gray-500 mt-0.5">IVA DF</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-amber-600">{{ totales?.pendientes_contabilizar ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Sin contabilizar</div>
        </div>
        <div class="bg-white rounded-xl border p-3 text-center">
          <div class="text-2xl font-bold text-red-600">{{ totales?.pendientes_siat ?? 0 }}</div>
          <div class="text-xs text-gray-500 mt-0.5">Pendientes SIAT</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Estado venta</label>
          <select v-model="filtros.estado" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option value="confirmada">Confirmada</option>
            <option value="anulada">Anulada</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Estado SIAT</label>
          <select v-model="filtros.estado_siat" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option value="aprobada">Aprobada</option>
            <option value="pendiente">Pendiente</option>
            <option value="rechazada">Rechazada</option>
            <option value="sin_factura">Sin factura</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Estado contable</label>
          <select v-model="filtros.estado_contable" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option value="pendiente">Pendiente</option>
            <option value="contabilizada">Contabilizada</option>
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
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
          <input type="date" v-model="filtros.hasta" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <div class="flex-1 min-w-40">
          <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
          <input v-model="filtros.buscar" @keyup.enter="aplicar" type="text" placeholder="NIT, razón social, N° factura..."
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
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">N° Venta</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Cliente</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Fecha</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Total</th>
                <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">IVA DF</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">SIAT</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Contable</th>
                <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Acciones</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!ventas.data.length">
                <td colspan="9" class="px-6 py-12 text-center text-gray-400">
                  No hay ventas registradas para este período.
                </td>
              </tr>
              <tr v-for="v in ventas.data" :key="v.id"
                :class="['hover:bg-gray-50 transition-colors', v.estado === 'anulada' ? 'opacity-50 line-through' : '']">
                <td class="px-4 py-2">
                  <div class="font-mono text-xs font-semibold text-gray-800">{{ v.nro_venta }}</div>
                  <div v-if="v.nro_factura" class="text-xs text-gray-400">F-{{ v.nro_factura }}</div>
                </td>
                <td class="px-4 py-2">
                  <div class="text-xs font-medium text-gray-800 max-w-36 truncate">{{ v.razon_social_cliente }}</div>
                  <div class="text-xs text-gray-400 font-mono">{{ v.nit_cliente || v.ci_cliente }}</div>
                </td>
                <td class="px-4 py-2 text-xs text-gray-600">{{ v.almacen?.nombre }}</td>
                <td class="px-4 py-2 text-xs text-gray-600 whitespace-nowrap">
                  {{ fmtFecha(v.fecha) }}
                  <span v-if="v.hora" class="text-gray-400 ml-1">{{ v.hora?.slice(0,5) }}</span>
                </td>
                <td class="px-4 py-2 text-right text-xs font-bold text-gray-800">{{ fmt(v.total) }}</td>
                <td class="px-4 py-2 text-right text-xs text-purple-700">{{ fmt(v.iva_df) }}</td>
                <!-- Estado SIAT -->
                <td class="px-4 py-2 text-center">
                  <span :class="siatBadge(v.estado_siat)" class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ siatLabel(v.estado_siat) }}
                  </span>
                </td>
                <!-- Estado Contable -->
                <td class="px-4 py-2 text-center">
                  <span :class="v.estado_contable === 'contabilizada' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
                    class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ v.estado_contable === 'contabilizada' ? 'Contabilizada' : 'Pendiente' }}
                  </span>
                </td>
                <td class="px-4 py-2">
                  <div class="flex items-center justify-center gap-1">
                    <Link :href="route('ventas.show', [periodo.id, v.id])"
                      class="p-1 text-gray-500 hover:text-blue-600 transition" title="Ver detalle">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </Link>
                    <!-- Imprimir factura -->
                    <a v-if="v.estado_siat === 'aprobada'" :href="route('ventas.factura', [periodo.id, v.id])" target="_blank"
                      class="p-1 text-gray-500 hover:text-green-600 transition" title="Imprimir factura">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </a>
                    <!-- Contabilizar -->
                    <button v-if="v.estado === 'confirmada' && v.estado_contable === 'pendiente'"
                      @click="contabilizarUna(v)"
                      class="px-2 py-0.5 bg-purple-600 hover:bg-purple-700 text-white text-xs rounded font-medium transition" title="Contabilizar esta venta">
                      Contabilizar
                    </button>
                    <!-- Reenviar SIAT -->
                    <button v-if="v.estado_siat === 'pendiente' || v.estado_siat === 'rechazada'"
                      @click="reenviarSiat(v)"
                      class="px-2 py-0.5 bg-amber-500 hover:bg-amber-600 text-white text-xs rounded font-medium transition" title="Reenviar al SIN">
                      SIAT
                    </button>
                    <!-- Anular -->
                    <button v-if="v.estado === 'confirmada'"
                      @click="anular(v)"
                      class="p-1 text-gray-400 hover:text-red-500 transition" title="Anular venta">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="ventas.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
          <div class="text-xs text-gray-500">{{ ventas.from }}–{{ ventas.to }} de {{ ventas.total }}</div>
          <div class="flex gap-1">
            <Link v-for="link in ventas.links" :key="link.label" :href="link.url || '#'"
              :class="['px-2.5 py-1 text-xs rounded border transition',
                link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50',
                !link.url ? 'opacity-40 cursor-not-allowed' : '']"
              v-html="link.label"/>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal contabilizar masivo -->
    <Teleport to="body">
      <div v-if="modalContabilizar" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" @click.self="modalContabilizar = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
          <h3 class="text-lg font-semibold text-gray-800 mb-3">Contabilizar ventas pendientes</h3>
          <p class="text-sm text-gray-600 mb-4">Se generará un asiento por cada venta pendiente de contabilizar. También puedes filtrar por rango de fechas.</p>
          <div class="grid grid-cols-2 gap-3 mb-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
              <input type="date" v-model="masivo.desde" class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
              <input type="date" v-model="masivo.hasta" class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>
          </div>
          <div class="flex gap-3">
            <button @click="modalContabilizar = false" class="flex-1 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition">Cancelar</button>
            <button @click="ejecutarContabilizarMasivo" :disabled="procesando" class="flex-1 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition disabled:opacity-50">
              {{ procesando ? 'Procesando...' : 'Contabilizar todos' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    periodo:  { type: Object, required: true },
    ventas:   { type: Object, required: true },
    totales:  { type: Object, default: () => ({}) },
    almacenes:{ type: Array, default: () => [] },
    filters:  { type: Object, default: () => ({}) },
  },
  data() {
    return {
      filtros: {
        estado:           this.filters.estado || '',
        estado_siat:      this.filters.estado_siat || '',
        estado_contable:  this.filters.estado_contable || '',
        almacen_id:       this.filters.almacen_id || '',
        desde:            this.filters.desde || '',
        hasta:            this.filters.hasta || '',
        buscar:           this.filters.buscar || '',
      },
      modalContabilizar: false,
      procesando: false,
      masivo: { desde: '', hasta: '' },
    }
  },
  computed: {
    pendientesContabilizar() { return this.totales?.pendientes_contabilizar ?? 0 },
  },
  methods: {
    aplicar() {
      router.get(route('ventas.index', this.periodo.id), this.filtros, { preserveState: true, replace: true })
    },
    contabilizarUna(v) {
      if (!confirm(`¿Contabilizar venta ${v.nro_venta}?`)) return
      router.post(route('ventas.contabilizar', [this.periodo.id, v.id]))
    },
    reenviarSiat(v) {
      if (!confirm(`¿Reenviar factura de ${v.nro_venta} al SIN?`)) return
      router.post(route('ventas.reenviar-siat', [this.periodo.id, v.id]))
    },
    anular(v) {
      const motivo = prompt(`¿Anular venta ${v.nro_venta}?\n\nIngresa el motivo:`)
      if (!motivo) return
      router.post(route('ventas.anular', [this.periodo.id, v.id]), { motivo })
    },
    ejecutarContabilizarMasivo() {
      this.procesando = true
      router.post(route('ventas.contabilizar-masivo', this.periodo.id), this.masivo, {
        onFinish: () => { this.procesando = false; this.modalContabilizar = false }
      })
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
    },
    fmtFecha(f) {
      if (!f) return '-'
      return new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' })
    },
    siatLabel(s) {
      return { pendiente: 'Pendiente', enviada: 'Enviada', aprobada: 'Aprobada', rechazada: 'Rechazada', anulada: 'Anulada', sin_factura: 'Sin factura' }[s] ?? s
    },
    siatBadge(s) {
      return { pendiente: 'bg-amber-100 text-amber-800', enviada: 'bg-blue-100 text-blue-800', aprobada: 'bg-green-100 text-green-800', rechazada: 'bg-red-100 text-red-800', anulada: 'bg-gray-100 text-gray-600', sin_factura: 'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100'
    },
  },
}
</script>
