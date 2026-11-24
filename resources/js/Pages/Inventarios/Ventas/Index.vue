<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between gap-3">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Ventas</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
        <Link :href="route(R.create, periodo.id)"
          class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Nueva venta
        </Link>
      </div>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Mensajes -->
      <div v-if="$page.props.flash?.success"
        class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.error"
        class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        {{ $page.props.errors.error }}
      </div>

      <!-- Totales -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <div class="bg-white rounded-xl border shadow-sm p-4">
          <div class="text-xs text-gray-500">Ventas confirmadas</div>
          <div class="text-2xl font-bold text-gray-800 mt-1">{{ totales?.total ?? 0 }}</div>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-4">
          <div class="text-xs text-gray-500">Monto total</div>
          <div class="text-2xl font-bold text-gray-800 mt-1">{{ fmt(totales?.monto_total) }}</div>
          <div class="text-xs text-purple-600 mt-0.5">IVA DF: {{ fmt(totales?.total_iva) }}</div>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-4">
          <div class="text-xs text-gray-500">Pendientes de contabilizar</div>
          <div class="text-2xl font-bold mt-1"
            :class="+totales?.pendientes_contabilizar > 0 ? 'text-amber-600' : 'text-gray-800'">
            {{ totales?.pendientes_contabilizar ?? 0 }}
          </div>
        </div>
        <div class="bg-white rounded-xl border shadow-sm p-4">
          <div class="text-xs text-gray-500">Pendientes en SIAT</div>
          <div class="text-2xl font-bold mt-1"
            :class="+totales?.pendientes_siat > 0 ? 'text-red-600' : 'text-gray-800'">
            {{ totales?.pendientes_siat ?? 0 }}
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border shadow-sm p-4">
        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
          <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Buscar</label>
            <input v-model="f.buscar" type="text" placeholder="N° venta, factura, cliente, NIT..."
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
            <select v-model="f.estado"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">Todos</option>
              <option value="borrador">Borrador</option>
              <option value="confirmada">Confirmada</option>
              <option value="anulada">Anulada</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">SIAT</label>
            <select v-model="f.estado_siat"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="enviada">Enviada</option>
              <option value="aprobada">Aprobada</option>
              <option value="rechazada">Rechazada</option>
              <option value="anulada">Anulada</option>
              <option value="sin_factura">Sin factura</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Contable</label>
            <select v-model="f.estado_contable"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">Todos</option>
              <option value="pendiente">Pendiente</option>
              <option value="contabilizada">Contabilizada</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
            <select v-model="f.almacen_id"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option value="">Todos</option>
              <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
            <input v-model="f.desde" type="date"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
            <input v-model="f.hasta" type="date"
              class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div class="flex items-end">
            <button type="button" @click="limpiar"
              class="px-3 py-1.5 text-xs text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition">
              Limpiar filtros
            </button>
          </div>
        </div>
      </div>

      <!-- Barra de selección -->
      <div v-if="seleccionadas.length"
        class="flex items-center justify-between gap-3 p-3 bg-blue-50 border border-blue-200 rounded-lg text-sm">
        <span class="text-blue-800">{{ seleccionadas.length }} venta(s) seleccionada(s)</span>
        <div class="flex gap-2">
          <button type="button" @click="seleccionadas = []"
            class="px-3 py-1.5 text-xs text-blue-700 hover:bg-blue-100 rounded-lg transition">Deseleccionar</button>
          <button type="button" @click="contabilizarSeleccion" :disabled="procesando"
            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition disabled:opacity-50">
            Contabilizar selección
          </button>
        </div>
      </div>

      <!-- Tabla -->
      <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-2.5 w-8">
                  <input type="checkbox" :checked="todasMarcadas" :disabled="!contabilizables.length"
                    @change="toggleTodas" class="rounded border-gray-300 text-blue-600"/>
                </th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600">N° Venta</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600">Fecha</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600">Cliente</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600">Almacén</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600">Total</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600">Estado</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600">SIAT</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600">Contable</th>
                <th class="px-3 py-2.5 w-24"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="v in ventas.data" :key="v.id"
                class="border-b border-gray-100 hover:bg-gray-50"
                :class="v.estado === 'anulada' ? 'opacity-60' : ''">
                <td class="px-3 py-2">
                  <input v-if="esContabilizable(v)" type="checkbox" :value="v.id" v-model="seleccionadas"
                    class="rounded border-gray-300 text-blue-600"/>
                </td>
                <td class="px-3 py-2">
                  <Link :href="route(R.show, [periodo.id, v.id])"
                    class="font-mono text-xs text-blue-600 hover:underline">{{ v.nro_venta }}</Link>
                  <div v-if="v.nro_factura" class="text-xs text-gray-400">Fact. {{ v.nro_factura }}</div>
                </td>
                <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ fmtFecha(v.fecha) }}</td>
                <td class="px-3 py-2">
                  <div class="text-xs text-gray-800 max-w-[220px] truncate">{{ v.razon_social_cliente || '—' }}</div>
                  <div class="text-xs text-gray-400">{{ v.nit_cliente || v.ci_cliente || 'S/N' }}</div>
                </td>
                <td class="px-3 py-2 text-xs text-gray-600">{{ v.almacen?.nombre }}</td>
                <td class="px-3 py-2 text-right text-xs font-semibold text-gray-800 whitespace-nowrap">{{ fmt(v.total) }}</td>
                <td class="px-3 py-2 text-center">
                  <span :class="badge(estadoClase, v.estado)" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                    {{ v.estado }}
                  </span>
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="badge(siatClase, v.estado_siat)" class="text-xs font-medium px-2 py-0.5 rounded-full">
                    {{ siatLabel[v.estado_siat] || v.estado_siat }}
                  </span>
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="badge(contableClase, v.estado_contable)" class="text-xs font-medium px-2 py-0.5 rounded-full capitalize">
                    {{ v.estado_contable }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <div class="flex items-center justify-end gap-1">
                    <Link :href="route(R.show, [periodo.id, v.id])" title="Ver"
                      class="p-1.5 text-gray-400 hover:text-blue-600 rounded transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                      </svg>
                    </Link>
                    <Link v-if="puedeEditar(v)" :href="route(R.edit, [periodo.id, v.id])" title="Editar"
                      class="p-1.5 text-gray-400 hover:text-amber-600 rounded transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                      </svg>
                    </Link>
                  </div>
                </td>
              </tr>
              <tr v-if="!ventas.data.length">
                <td colspan="10" class="px-3 py-12 text-center text-sm text-gray-400">
                  No hay ventas con estos filtros.
                  <Link :href="route(R.create, periodo.id)" class="text-blue-600 hover:underline ml-1">Registrar una venta</Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div v-if="ventas.links?.length > 3"
          class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 border-t bg-gray-50">
          <div class="text-xs text-gray-500">
            Mostrando {{ ventas.from ?? 0 }}–{{ ventas.to ?? 0 }} de {{ ventas.total }}
          </div>
          <div class="flex flex-wrap gap-1">
            <template v-for="(l, i) in ventas.links" :key="i">
              <Link v-if="l.url" :href="l.url" preserve-state preserve-scroll
                class="px-2.5 py-1 text-xs rounded-lg border transition"
                :class="l.active ? 'bg-blue-600 border-blue-600 text-white' : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-100'"
                v-html="l.label"/>
              <span v-else class="px-2.5 py-1 text-xs rounded-lg border border-gray-100 text-gray-300"
                v-html="l.label"/>
            </template>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

// Ajusta estos nombres a los de tu routes/web.php
const R = {
  index:  'inventario.ventas.index',
  create: 'inventario.ventas.create',
  show:   'inventario.ventas.show',
  edit:   'inventario.ventas.edit',
  masivo: 'inventario.ventas.contabilizar-masivo',
}

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    periodo:   { type: Object, required: true },
    ventas:    { type: Object, required: true },
    totales:   { type: Object, default: null },
    almacenes: { type: Array, default: () => [] },
    filters:   { type: Object, default: () => ({}) },
  },
  data() {
    return {
      R,
      procesando: false,
      seleccionadas: [],
      timer: null,
      f: {
        buscar:          this.filters.buscar          ?? '',
        estado:          this.filters.estado          ?? '',
        estado_siat:     this.filters.estado_siat     ?? '',
        estado_contable: this.filters.estado_contable ?? '',
        almacen_id:      this.filters.almacen_id      ?? '',
        desde:           this.filters.desde           ?? '',
        hasta:           this.filters.hasta           ?? '',
      },
      estadoClase: {
        borrador:   'bg-gray-100 text-gray-700',
        confirmada: 'bg-green-100 text-green-700',
        anulada:    'bg-red-100 text-red-700',
      },
      siatClase: {
        pendiente:   'bg-amber-100 text-amber-700',
        enviada:     'bg-blue-100 text-blue-700',
        aprobada:    'bg-green-100 text-green-700',
        rechazada:   'bg-red-100 text-red-700',
        anulada:     'bg-red-100 text-red-700',
        sin_factura: 'bg-gray-100 text-gray-600',
      },
      siatLabel: {
        pendiente: 'Pendiente', enviada: 'Enviada', aprobada: 'Aprobada',
        rechazada: 'Rechazada', anulada: 'Anulada', sin_factura: 'Sin factura',
      },
      contableClase: {
        pendiente:     'bg-amber-100 text-amber-700',
        contabilizada: 'bg-green-100 text-green-700',
      },
    }
  },
  computed: {
    contabilizables() {
      return this.ventas.data.filter(this.esContabilizable)
    },
    todasMarcadas() {
      return this.contabilizables.length > 0
        && this.contabilizables.every(v => this.seleccionadas.includes(v.id))
    },
  },
  watch: {
    f: {
      deep: true,
      handler() {
        clearTimeout(this.timer)
        this.timer = setTimeout(this.aplicar, 350)
      },
    },
  },
  beforeUnmount() { clearTimeout(this.timer) },
  methods: {
    aplicar() {
      const params = Object.fromEntries(
        Object.entries(this.f).filter(([, v]) => v !== '' && v !== null)
      )
      router.get(route(R.index, this.periodo.id), params, {
        preserveState: true, preserveScroll: true, replace: true,
        onSuccess: () => { this.seleccionadas = [] },
      })
    },
    limpiar() {
      Object.keys(this.f).forEach(k => { this.f[k] = '' })
    },
    esContabilizable(v) {
      return v.estado === 'confirmada' && v.estado_contable === 'pendiente' && !!v.cuenta_cobro_id
    },
    puedeEditar(v) {
      return v.estado !== 'anulada'
    },
    toggleTodas(e) {
      this.seleccionadas = e.target.checked ? this.contabilizables.map(v => v.id) : []
    },
    contabilizarSeleccion() {
      if (!this.seleccionadas.length) return
      if (!confirm(`¿Contabilizar ${this.seleccionadas.length} venta(s)? Se generará un asiento por venta.`)) return
      this.procesando = true
      router.post(route(R.masivo, this.periodo.id), { ids: this.seleccionadas }, {
        preserveScroll: true,
        onSuccess: () => { this.seleccionadas = [] },
        onFinish:  () => { this.procesando = false },
      })
    },
    badge(map, key) { return map[key] || 'bg-gray-100 text-gray-600' },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency', currency: 'BOB', minimumFractionDigits: 2,
      }).format(v ?? 0)
    },
    fmtFecha(d) {
      if (!d) return ''
      const [y, m, day] = String(d).substring(0, 10).split('-')
      return `${day}/${m}/${y}`
    },
  },
}
</script>
