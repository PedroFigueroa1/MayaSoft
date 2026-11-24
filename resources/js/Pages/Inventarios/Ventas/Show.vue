<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('inventario.ventas.index', periodo.id)"
          class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h2 class="text-xl font-semibold text-gray-800">{{ venta.nro_venta }}</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
        <!-- Badges estado -->
        <span :class="estadoBadge(venta.estado)"
          class="text-xs px-2 py-0.5 rounded-full font-medium">
          {{ estadoLabel(venta.estado) }}
        </span>
        <span :class="siatBadge(venta.estado_siat)"
          class="text-xs px-2 py-0.5 rounded-full font-medium">
          SIAT: {{ siatLabel(venta.estado_siat) }}
        </span>
        <span :class="venta.estado_contable === 'contabilizada'
            ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800'"
          class="text-xs px-2 py-0.5 rounded-full font-medium">
          {{ venta.estado_contable === 'contabilizada' ? 'Contabilizada' : 'Sin contabilizar' }}
        </span>
      </div>
    </template>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.error"
        class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        {{ $page.props.errors.error }}
      </div>

      <!-- Acciones rápidas -->
      <div class="flex flex-wrap gap-2">
        <!-- Imprimir factura -->
        <!--<a v-if="venta.estado_siat === 'aprobada'"
          :href="route('inventario.ventas.factura', [periodo.id, venta.id])"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">-->
        <a
          :href="route('inventario.ventas.factura', [periodo.id, venta.id])"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
          </svg>
          Imprimir factura
        </a>
        <!-- Contabilizar -->
        <button v-if="venta.estado === 'confirmada' && venta.estado_contable === 'pendiente'"
          @click="contabilizar"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-600 text-white text-xs font-medium rounded-lg hover:bg-purple-700 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
          </svg>
          Contabilizar
        </button>
        <!-- Reenviar SIAT -->
        <button v-if="['pendiente','rechazada'].includes(venta.estado_siat)"
          @click="reenviarSiat"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-500 text-white text-xs font-medium rounded-lg hover:bg-amber-600 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          Reenviar al SIN
        </button>
        <!-- Anular -->
        <button v-if="venta.estado === 'confirmada'"
          @click="anular"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-red-200 text-red-600 text-xs font-medium rounded-lg hover:bg-red-50 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
          </svg>
          Anular venta
        </button>
        <!-- Ver asiento -->
        <a v-if="venta.asiento_id"
          :href="route('asientos.show', venta.asiento_id)"
          target="_blank"
          class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white border border-gray-200 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
          </svg>
          Ver asiento {{ venta.asiento?.numero_asiento }}
        </a>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- Columna principal -->
        <div class="lg:col-span-2 space-y-4">

          <!-- Datos generales -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 pb-2 border-b">Datos de la venta</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">N° Venta</span>
                <span class="font-mono font-bold text-gray-800">{{ venta.nro_venta }}</span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Fecha</span>
                <span class="text-gray-700">{{ fmtFecha(venta.fecha) }}
                  <span v-if="venta.hora" class="text-gray-400 text-xs ml-1">{{ venta.hora?.slice(0,5) }}</span>
                </span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Almacén</span>
                <span class="text-gray-700">{{ venta.almacen?.nombre }}</span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Forma de pago</span>
                <span class="text-gray-700 capitalize">{{ venta.forma_pago }}</span>
              </div>
              <div v-if="venta.dias_credito > 0">
                <span class="text-xs text-gray-400 block mb-0.5">Vence</span>
                <span class="text-gray-700">{{ fmtFecha(venta.fecha_vencimiento_cobro) }}</span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Registrado por</span>
                <span class="text-gray-700">{{ venta.creado_por?.name }}</span>
              </div>
            </div>
          </div>

          <!-- Cliente -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b">Cliente</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Razón social</span>
                <span class="font-medium text-gray-800">{{ venta.razon_social_cliente }}</span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">NIT / CI</span>
                <span class="font-mono text-gray-700">
                  {{ venta.nit_cliente || venta.ci_cliente || '—' }}
                  <span v-if="venta.complemento_cliente" class="text-gray-400">{{ venta.complemento_cliente }}</span>
                </span>
              </div>
            </div>
          </div>

          <!-- Detalle productos -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 pb-2 border-b">Detalle</h3>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-gray-50 border-b">
                    <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Cant.</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">P. Venta</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Desc.</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Subtotal</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Costo</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-gray-500">Utilidad</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="det in venta.detalles" :key="det.id" class="hover:bg-gray-50">
                    <td class="px-3 py-2">
                      <div class="text-xs font-medium text-gray-800">{{ det.producto?.descripcion }}</div>
                      <div class="text-xs text-gray-400 font-mono">{{ det.producto?.codigo }}</div>
                    </td>
                    <td class="px-3 py-2 text-right text-xs text-gray-700">
                      {{ fmtCant(det.cantidad) }} {{ det.producto?.unidad_medida?.codigo }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs text-gray-700">{{ fmt(det.precio_unitario) }}</td>
                    <td class="px-3 py-2 text-right text-xs text-gray-500">
                      {{ det.descuento > 0 ? fmt(det.descuento) : '—' }}
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-semibold text-gray-800">{{ fmt(det.subtotal) }}</td>
                    <td class="px-3 py-2 text-right text-xs text-gray-400">{{ fmt(det.costo_total) }}</td>
                    <td class="px-3 py-2 text-right text-xs"
                      :class="det.utilidad >= 0 ? 'text-green-600' : 'text-red-500'">
                      {{ fmt(det.utilidad) }}
                    </td>
                  </tr>
                </tbody>
                <tfoot>
                  <tr class="border-t bg-gray-50">
                    <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Subtotal:</td>
                    <td class="px-3 py-2 text-right text-sm font-bold text-gray-800">{{ fmt(venta.subtotal) }}</td>
                    <td class="px-3 py-2 text-right text-xs text-gray-400">{{ fmt(totalCosto) }}</td>
                    <td class="px-3 py-2 text-right text-xs font-semibold"
                      :class="totalUtilidad >= 0 ? 'text-green-700' : 'text-red-600'">
                      {{ fmt(totalUtilidad) }}
                    </td>
                  </tr>
                  <tr v-if="venta.iva_df > 0" class="bg-gray-50">
                    <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-purple-700">IVA Débito Fiscal:</td>
                    <td class="px-3 py-2 text-right text-sm font-bold text-purple-700">{{ fmt(venta.iva_df) }}</td>
                    <td colspan="2"></td>
                  </tr>
                  <tr class="bg-blue-50">
                    <td colspan="4" class="px-3 py-2 text-right text-sm font-bold text-blue-800">TOTAL:</td>
                    <td class="px-3 py-2 text-right text-base font-bold text-blue-800">{{ fmt(venta.total) }}</td>
                    <td colspan="2"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- Pagos -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b">Pagos recibidos</h3>
            <div class="divide-y divide-gray-100">
              <div v-for="pago in venta.pagos" :key="pago.id"
                class="flex items-center justify-between py-2.5">
                <div class="flex items-center gap-3">
                  <span :class="medioBadge(pago.medio)"
                    class="text-xs px-2 py-0.5 rounded-full font-medium">
                    {{ medioLabel(pago.medio) }}
                  </span>
                  <span v-if="pago.referencia" class="text-xs text-gray-400">Ref: {{ pago.referencia }}</span>
                  <span v-if="pago.cuenta" class="text-xs text-gray-400 font-mono">{{ pago.cuenta.codigo }}</span>
                </div>
                <span class="text-sm font-semibold text-gray-800">{{ fmt(pago.monto) }}</span>
              </div>
            </div>
          </div>

          <!-- Asiento contable -->
          <div v-if="venta.asiento" class="bg-white rounded-xl border p-5">
            <div class="flex items-center justify-between mb-3 pb-2 border-b">
              <h3 class="text-sm font-semibold text-gray-700">Asiento contable</h3>
              <span class="font-mono text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">
                {{ venta.asiento.numero_asiento }}
              </span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-xs">
                <thead>
                  <tr class="bg-gray-50 border-b">
                    <th class="px-3 py-1.5 text-left font-semibold text-gray-600">Cuenta</th>
                    <th class="px-3 py-1.5 text-right font-semibold text-green-700">DEBE</th>
                    <th class="px-3 py-1.5 text-right font-semibold text-red-700">HABER</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="det in venta.asiento.detalles" :key="det.id">
                    <td class="px-3 py-1.5 text-gray-700">
                      <span class="font-mono text-gray-400 mr-1">{{ det.cuenta?.codigo }}</span>
                      {{ det.cuenta?.nombre }}
                    </td>
                    <td class="px-3 py-1.5 text-right font-medium text-green-700">
                      {{ det.debe > 0 ? fmt(det.debe) : '' }}
                    </td>
                    <td class="px-3 py-1.5 text-right font-medium text-red-700">
                      {{ det.haber > 0 ? fmt(det.haber) : '' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Columna lateral -->
        <div class="space-y-4">

          <!-- Resumen financiero -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Resumen</h3>
            <div class="space-y-2">
              <div class="flex justify-between text-sm">
                <span class="text-gray-500">Subtotal</span>
                <span class="font-medium text-gray-800">{{ fmt(venta.subtotal) }}</span>
              </div>
              <div v-if="venta.descuento > 0" class="flex justify-between text-sm">
                <span class="text-gray-500">Descuento</span>
                <span class="text-red-600">-{{ fmt(venta.descuento) }}</span>
              </div>
              <div v-if="venta.iva_df > 0" class="flex justify-between text-sm">
                <span class="text-gray-500">IVA DF (incluido)</span>
                <span class="text-purple-700">{{ fmt(venta.iva_df) }}</span>
              </div>
              <div class="flex justify-between text-base font-bold pt-2 border-t">
                <span class="text-gray-800">Total</span>
                <span class="text-blue-700">{{ fmt(venta.total) }}</span>
              </div>
              <div class="pt-2 border-t">
                <div class="flex justify-between text-sm">
                  <span class="text-gray-500">Costo total</span>
                  <span class="text-gray-600">{{ fmt(totalCosto) }}</span>
                </div>
                <div class="flex justify-between text-sm mt-1">
                  <span class="text-gray-500">Utilidad bruta</span>
                  <span :class="totalUtilidad >= 0 ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'">
                    {{ fmt(totalUtilidad) }}
                  </span>
                </div>
                <div v-if="venta.total > 0" class="flex justify-between text-xs mt-1">
                  <span class="text-gray-400">Margen</span>
                  <span :class="margen >= 0 ? 'text-green-600' : 'text-red-500'">{{ margen }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Info SIAT -->
          <div class="bg-white rounded-xl border p-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Factura electrónica</h3>
            <div class="space-y-2 text-sm">
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">N° Factura</span>
                <span class="font-mono font-bold text-gray-800">{{ venta.nro_factura || '—' }}</span>
              </div>
              <div v-if="venta.cuf">
                <span class="text-xs text-gray-400 block mb-0.5">CUF</span>
                <span class="font-mono text-xs text-gray-600 break-all">{{ venta.cuf }}</span>
              </div>
              <div v-if="venta.fecha_emision_factura">
                <span class="text-xs text-gray-400 block mb-0.5">Emitida</span>
                <span class="text-gray-700 text-xs">{{ fmtDatetime(venta.fecha_emision_factura) }}</span>
              </div>
              <div>
                <span class="text-xs text-gray-400 block mb-0.5">Modalidad</span>
                <span class="text-gray-700 capitalize">{{ venta.modalidad_factura }}</span>
              </div>
              <!-- Alerta factura offline pendiente -->
              <div v-if="venta.estado_siat === 'pendiente'"
                class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                Esta factura está pendiente de envío al SIN. Se enviará automáticamente cuando haya conexión.
              </div>
              <div v-if="venta.estado_siat === 'rechazada'"
                class="mt-2 p-2 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
                El SIN rechazó esta factura. Verifica los datos y reenvía.
              </div>
            </div>
          </div>

          <!-- Motivo de anulación -->
          <div v-if="venta.estado === 'anulada'"
            class="bg-red-50 rounded-xl border border-red-200 p-5">
            <h3 class="text-sm font-semibold text-red-700 mb-2">Venta anulada</h3>
            <p class="text-xs text-red-600">{{ venta.motivo_anulacion }}</p>
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
    venta:   { type: Object, required: true },
    periodo: { type: Object, required: true },
    empresa: { type: Object, default: null },
  },
  computed: {
    totalCosto()    { return this.venta.detalles?.reduce((s,d) => s + (+d.costo_total||0), 0) ?? 0 },
    totalUtilidad() { return this.venta.detalles?.reduce((s,d) => s + (+d.utilidad||0), 0) ?? 0 },
    margen() {
      if (!this.venta.total || this.venta.total <= 0) return 0
      return Math.round((this.totalUtilidad / this.venta.total) * 100 * 10) / 10
    },
  },
  methods: {
    contabilizar() {
      if (!confirm('¿Contabilizar esta venta? Se generará el asiento ING.')) return
      router.post(route('inventario.ventas.contabilizar', [this.periodo.id, this.venta.id]))
    },
    reenviarSiat() {
      if (!confirm('¿Reenviar esta factura al SIN?')) return
      router.post(route('inventario.ventas.reenviar-siat', [this.periodo.id, this.venta.id]))
    },
    anular() {
      const motivo = prompt('¿Anular esta venta?\n\nIngresa el motivo de anulación:')
      if (!motivo) return
      router.post(route('inventario.ventas.anular', [this.periodo.id, this.venta.id]), { motivo })
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
    },
    fmtFecha(f) {
      if (!f) return '—'
      return new Date(f + 'T00:00:00').toLocaleDateString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric' })
    },
    fmtDatetime(f) {
      if (!f) return '—'
      return new Date(f).toLocaleString('es-BO', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' })
    },
    estadoLabel(e)  { return { borrador:'Borrador', confirmada:'Confirmada', anulada:'Anulada' }[e] ?? e },
    estadoBadge(e)  { return { borrador:'bg-gray-100 text-gray-700', confirmada:'bg-green-100 text-green-800', anulada:'bg-red-100 text-red-800' }[e] ?? 'bg-gray-100 text-gray-700' },
    siatLabel(s)    { return { pendiente:'Pendiente', enviada:'Enviada', aprobada:'Aprobada', rechazada:'Rechazada', anulada:'Anulada', sin_factura:'Sin factura' }[s] ?? s },
    siatBadge(s)    { return { pendiente:'bg-amber-100 text-amber-800', enviada:'bg-blue-100 text-blue-800', aprobada:'bg-green-100 text-green-800', rechazada:'bg-red-100 text-red-800', anulada:'bg-gray-100 text-gray-600', sin_factura:'bg-gray-100 text-gray-500' }[s] ?? 'bg-gray-100' },
    medioLabel(m)   { return { efectivo:'Efectivo', tarjeta_debito:'T. Débito', tarjeta_credito:'T. Crédito', qr:'QR', transferencia:'Transferencia', cheque:'Cheque', credito:'Crédito' }[m] ?? m },
    medioBadge(m)   { return { efectivo:'bg-green-100 text-green-800', tarjeta_debito:'bg-blue-100 text-blue-800', tarjeta_credito:'bg-purple-100 text-purple-800', qr:'bg-teal-100 text-teal-800', transferencia:'bg-indigo-100 text-indigo-800', credito:'bg-amber-100 text-amber-800' }[m] ?? 'bg-gray-100 text-gray-700' },
  },
}
</script>
