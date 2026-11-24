<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Historial de Movimientos</h2>
          <p class="text-xs text-gray-500 mt-0.5">Kardex general - todos los productos</p>
        </div>
        <Link :href="route('movimientos.create')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Nuevo movimiento
        </Link>
      </div>
    </template>

    <div class="py-6 max-w-full mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="$page.props.flash?.success" class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.error" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        {{ $page.props.errors.error }}
      </div>

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
          <label class="block text-xs font-medium text-gray-600 mb-1">Producto</label>
          <select v-model="filtros.producto_id" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.descripcion }}</option>
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
          <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
          <select v-model="filtros.tipo" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <optgroup label="Entradas">
              <option value="entrada_manual">Entrada manual</option>
              <option value="entrada_compra">Entrada por compra</option>
              <option value="entrada_devolucion">Devolución cliente</option>
              <option value="ajuste_positivo">Ajuste positivo</option>
            </optgroup>
            <optgroup label="Salidas">
              <option value="salida_venta">Salida por venta</option>
              <option value="salida_consumo">Consumo interno</option>
              <option value="salida_merma">Merma</option>
              <option value="salida_devolucion">Devolución proveedor</option>
              <option value="ajuste_negativo">Ajuste negativo</option>
            </optgroup>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Estado</label>
          <select v-model="filtros.estado" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="activo">Activos</option>
            <option value="anulado">Anulados</option>
            <option value="">Todos</option>
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

      <!-- Tabla -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-b">
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Fecha</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Tipo</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Producto</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Almacén</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Cantidad</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Costo Unit.</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Costo Total</th>
                <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wide">Saldo</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Motivo</th>
                <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Usuario</th>
                <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Acción</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-if="!movimientos.data.length">
                <td colspan="11" class="px-6 py-12 text-center text-gray-400">
                  <svg class="mx-auto w-10 h-10 mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                  </svg>
                  No hay movimientos registrados con estos filtros.
                  <Link :href="route('movimientos.create')" class="text-blue-600 hover:underline">Registrar el primero</Link>
                </td>
              </tr>
              <tr v-for="m in movimientos.data" :key="m.id"
                :class="['hover:bg-gray-50 transition-colors', m.estado === 'anulado' ? 'opacity-50 line-through' : '']">
                <td class="px-3 py-2 text-xs text-gray-600 whitespace-nowrap">{{ fmtFecha(m.fecha) }}</td>
                <td class="px-3 py-2">
                  <span :class="tipoBadge(m.tipo)" class="text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap">
                    {{ tipoLabel(m.tipo) }}
                  </span>
                </td>
                <td class="px-3 py-2">
                  <div class="text-xs font-medium text-gray-800">{{ m.producto?.descripcion }}</div>
                  <div class="text-xs text-gray-400 font-mono">{{ m.producto?.codigo }}</div>
                </td>
                <td class="px-3 py-2 text-xs text-gray-600">
                  {{ m.almacen?.nombre }}
                  <span v-if="m.almacen_destino" class="text-gray-400"> → {{ m.almacen_destino.nombre }}</span>
                </td>
                <td class="px-3 py-2 text-right text-xs font-semibold"
                  :class="esEntrada(m.tipo) ? 'text-green-700' : 'text-red-700'">
                  {{ esEntrada(m.tipo) ? '+' : '-' }}{{ fmtCant(m.cantidad) }}
                </td>
                <td class="px-3 py-2 text-right text-xs text-gray-600">{{ fmt(m.costo_unitario) }}</td>
                <td class="px-3 py-2 text-right text-xs font-medium text-gray-700">{{ fmt(m.costo_total) }}</td>
                <td class="px-3 py-2 text-right text-xs font-bold text-gray-800">{{ fmtCant(m.stock_resultante) }}</td>
                <td class="px-3 py-2 text-xs text-gray-600 max-w-40 truncate" :title="m.motivo">{{ m.motivo || '-' }}</td>
                <td class="px-3 py-2 text-xs text-gray-500">{{ m.creado_por?.name }}</td>
                <td class="px-3 py-2 text-center">
                  <button v-if="m.estado === 'activo' && !m.referencia_tipo"
                    @click="anular(m)"
                    class="p-1 text-gray-400 hover:text-red-500 transition" title="Anular y reversar">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                  </button>
                  <span v-else-if="m.referencia_tipo" class="text-xs text-gray-300 italic" :title="'Origen: ' + m.referencia_tipo">🔒</span>
                  <span v-else class="text-xs text-gray-300">Anulado</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-if="movimientos.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
          <div class="text-xs text-gray-500">{{ movimientos.from }}–{{ movimientos.to }} de {{ movimientos.total }}</div>
          <div class="flex gap-1">
            <Link v-for="link in movimientos.links" :key="link.label" :href="link.url || '#'"
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
    movimientos: { type: Object, required: true },
    periodos:    { type: Array, default: () => [] },
    periodoId:   { type: [Number, String], default: null },
    productos:   { type: Array, default: () => [] },
    almacenes:   { type: Array, default: () => [] },
    filters:     { type: Object, default: () => ({}) },
  },
  data() {
    return {
      filtros: {
        periodo_id:  this.filters.periodo_id || this.periodoId || '',
        producto_id: this.filters.producto_id || '',
        almacen_id:  this.filters.almacen_id || '',
        tipo:        this.filters.tipo || '',
        estado:      this.filters.estado ?? 'activo',
        desde:       this.filters.desde || '',
        hasta:       this.filters.hasta || '',
      },
    }
  },
  methods: {
    aplicar() {
      router.get(route('movimientos.index'), this.filtros, { preserveState: true, replace: true })
    },
    anular(m) {
      const motivo = prompt(`¿Anular este movimiento de ${m.producto?.descripcion}?\n\nSe creará un movimiento inverso. Ingresa el motivo:`)
      if (!motivo) return
      router.post(route('movimientos.anular', m.id), { motivo })
    },
    esEntrada(tipo) { return ENTRADAS.includes(tipo) },
    tipoLabel(tipo) { return LABELS[tipo] ?? tipo },
    tipoBadge(tipo) {
      return this.esEntrada(tipo) ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
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
