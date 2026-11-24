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
          <h2 class="text-xl font-semibold text-gray-800">Editar Venta</h2>
          <p class="text-xs text-gray-500 mt-0.5">
            {{ venta.nro_venta }} — {{ periodo.nombre }}
          </p>
        </div>
        <span v-if="venta.estado === 'anulada'"
          class="ml-auto px-2 py-1 text-xs font-semibold bg-red-100 text-red-700 rounded-lg">
          ANULADA
        </span>
        <span v-else-if="venta.estado === 'confirmada'"
          class="ml-auto px-2 py-1 text-xs font-semibold bg-green-100 text-green-700 rounded-lg">
          CONFIRMADA
        </span>
      </div>
    </template>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Error global -->
      <div v-if="$page.props.errors?.error"
        class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $page.props.errors.error }}</span>
      </div>

      <!-- Aviso de edición restringida -->
      <div v-if="!editable"
        class="flex items-start gap-2 p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-sm">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
        </svg>
        <span>{{ motivoNoEditable }}</span>
      </div>

      <!-- Estado SIAT -->
      <div v-if="venta.con_factura && venta.estado_siat"
        :class="venta.estado_siat === 'aprobada' ? 'bg-green-50 border-green-200 text-green-800'
              : venta.estado_siat === 'rechazada' ? 'bg-red-50 border-red-200 text-red-800'
              : 'bg-amber-50 border-amber-200 text-amber-800'"
        class="flex items-center gap-2 p-2.5 border rounded-lg text-xs">
        Factura SIAT: <strong>{{ venta.estado_siat }}</strong>
        <span v-if="venta.nro_factura"> — N° {{ venta.nro_factura }}</span>
        <span v-if="venta.cuf"> — CUF {{ venta.cuf?.substring(0, 20) }}...</span>
      </div>

      <form @submit.prevent="submit">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

          <!-- Columna izquierda -->
          <div class="lg:col-span-2 space-y-4">

            <!-- ── Datos generales ── -->
            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
              <h3 class="text-sm font-semibold text-gray-700 pb-2 border-b">Datos de la venta</h3>

              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">N° Venta</label>
                  <input :value="venta.nro_venta" readonly
                    class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-1.5 text-sm font-mono text-gray-600"/>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Almacén *</label>
                  <select v-model="form.almacen_id" required @change="refrescarStocks"
                    :disabled="!editable"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50">
                    <option value="">Seleccionar...</option>
                    <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                  </select>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                  <input v-model="form.fecha" type="date" required :disabled="!editable"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago *</label>
                  <select v-model="form.forma_pago" required :disabled="!editable"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50">
                    <option value="contado">Contado</option>
                    <option value="credito">Crédito</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="qr">QR</option>
                    <option value="mixto">Mixto</option>
                  </select>
                </div>
              </div>

              <div v-if="form.forma_pago === 'credito'" class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">Días de crédito</label>
                  <input v-model="form.dias_credito" type="number" min="1" :disabled="!editable"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
                </div>
              </div>
            </div>

            <!-- ── Cliente ── -->
            <div class="bg-white rounded-xl border shadow-sm p-5 space-y-3">
              <div class="flex items-center justify-between pb-2 border-b">
                <h3 class="text-sm font-semibold text-gray-700">Cliente</h3>
                <label class="flex items-center gap-1.5 text-xs text-gray-600 cursor-pointer">
                  <input type="checkbox" v-model="clienteDelMaestro" :disabled="!editable"
                    class="rounded border-gray-300 text-blue-600"/>
                  Buscar en maestro de clientes
                </label>
              </div>

              <div v-if="clienteDelMaestro">
                <label class="block text-xs font-medium text-gray-600 mb-1">Cliente</label>
                <select v-model="form.proveedor_cliente_id" @change="onClienteChange"
                  :disabled="!editable"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50">
                  <option :value="null">Seleccionar cliente...</option>
                  <option v-for="c in clientes" :key="c.id" :value="c.id">
                    {{ c.razon_social }} — {{ c.nit || c.ci }}
                  </option>
                </select>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="sm:col-span-2">
                  <label class="block text-xs font-medium text-gray-600 mb-1">Razón social / Nombre *</label>
                  <input v-model="form.razon_social_cliente" type="text" required maxlength="200"
                    :disabled="!editable"
                    placeholder="Nombre del cliente para la factura"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-600 mb-1">NIT / CI</label>
                  <div class="flex gap-1">
                    <input v-model="form.nit_cliente" type="text" placeholder="NIT" :disabled="!editable"
                      class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
                    <input v-model="form.complemento_cliente" type="text" placeholder="Comp." :disabled="!editable"
                      class="w-16 border border-gray-200 rounded-lg px-2 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
                  </div>
                </div>
              </div>
            </div>

            <!-- ── Productos ── -->
            <div class="bg-white rounded-xl border shadow-sm p-5">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Productos</h3>
                <button type="button" @click="agregarLinea" v-if="editable"
                  class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                  </svg>
                  Agregar línea
                </button>
              </div>

              <div class="overflow-x-auto">
                <table class="w-full text-sm">
                  <thead>
                    <tr class="bg-gray-50 border-b border-t">
                      <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                      <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-24">Cant.</th>
                      <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Precio</th>
                      <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-24">Desc.</th>
                      <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Subtotal</th>
                      <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 w-20">Stock</th>
                      <th class="w-8"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(linea, i) in form.detalles" :key="i"
                      class="border-b border-gray-100"
                      :class="linea._sinStock ? 'bg-red-50' : 'hover:bg-gray-50'">

                      <td class="px-3 py-2">
                        <select v-model="linea.producto_id" required :disabled="!editable"
                          @change="onProductoChange(i)"
                          class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50">
                          <option value="">Seleccionar...</option>
                          <option v-for="p in productos" :key="p.id" :value="p.id">
                            {{ p.codigo }} — {{ p.descripcion }}
                          </option>
                        </select>
                        <div v-if="linea._unidad" class="text-xs text-gray-400 mt-0.5 ml-1">{{ linea._unidad }}</div>
                      </td>

                      <td class="px-3 py-2">
                        <input v-model="linea.cantidad" type="number" step="0.0001" min="0.0001" required
                          @input="calcLinea(i)" :disabled="!editable"
                          class="w-full border rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50"
                          :class="linea._sinStock ? 'border-red-400' : 'border-gray-200'"/>
                      </td>

                      <td class="px-3 py-2">
                        <div class="flex items-center gap-1">
                          <input v-model="linea.precio_unitario" type="number" step="0.0001" min="0" required
                            @input="calcLinea(i)" :disabled="!editable"
                            class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50"/>
                          <button v-if="linea._precios?.length > 1 && editable" type="button"
                            @click="ciclarPrecio(i)"
                            class="px-1.5 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded transition"
                            :title="'Lista: ' + linea._listaActual">
                            P{{ linea._listaActual }}
                          </button>
                        </div>
                      </td>

                      <td class="px-3 py-2">
                        <input v-model="linea.descuento" type="number" step="0.0001" min="0"
                          @input="calcLinea(i)" :disabled="!editable"
                          class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50"/>
                      </td>

                      <td class="px-3 py-2 text-right text-xs font-semibold text-gray-700">
                        {{ fmt(linea._subtotal) }}
                      </td>

                      <td class="px-3 py-2 text-center">
                        <span v-if="linea._stockActual !== null"
                          :class="[
                            'text-xs font-medium px-1.5 py-0.5 rounded-full',
                            linea._stockActual <= 0        ? 'bg-red-100 text-red-700' :
                            linea._sinStock                ? 'bg-red-100 text-red-700' :
                            linea._stockActual < 5         ? 'bg-amber-100 text-amber-700' :
                                                              'bg-green-100 text-green-700'
                          ]">
                          {{ fmtCant(linea._stockActual) }}
                        </span>
                      </td>

                      <td class="px-3 py-2">
                        <button type="button" @click="quitarLinea(i)"
                          v-if="form.detalles.length > 1 && editable"
                          class="text-gray-300 hover:text-red-500 transition">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                          </svg>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tfoot>
                    <tr class="border-t bg-gray-50">
                      <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-gray-600">Subtotal:</td>
                      <td class="px-3 py-2 text-right text-sm font-bold text-gray-700">{{ fmt(subtotal) }}</td>
                      <td colspan="2"></td>
                    </tr>
                    <tr v-if="ivaTotal > 0" class="bg-gray-50">
                      <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-purple-700">IVA DF (13/113):</td>
                      <td class="px-3 py-2 text-right text-sm font-bold text-purple-700">{{ fmt(ivaTotal) }}</td>
                      <td colspan="2"></td>
                    </tr>
                    <tr class="bg-blue-50">
                      <td colspan="4" class="px-3 py-2 text-right text-sm font-bold text-blue-800">TOTAL:</td>
                      <td class="px-3 py-2 text-right text-base font-bold text-blue-800">{{ fmt(total) }}</td>
                      <td colspan="2"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>

              <div v-if="hayErroresStock" class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
                ⚠ Hay productos con stock insuficiente. Corrígelos antes de guardar.
              </div>
            </div>
          </div>

          <!-- Columna derecha -->
          <div class="space-y-4">

            <!-- Resumen -->
            <div class="bg-blue-600 rounded-xl p-4 text-white">
              <div class="text-xs text-blue-200 mb-1">Total a cobrar</div>
              <div class="text-3xl font-bold">{{ fmt(total) }}</div>
              <div v-if="ivaTotal > 0" class="text-xs text-blue-200 mt-1">
                IVA incluido: {{ fmt(ivaTotal) }}
              </div>
            </div>

            <!-- Pagos -->
            <div class="bg-white rounded-xl border shadow-sm p-4 space-y-3">
              <div class="flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-700">Forma de pago</h3>
                <button type="button" @click="agregarPago"
                  v-if="form.forma_pago === 'mixto' && editable"
                  class="text-xs text-blue-600 hover:underline">+ Medio</button>
              </div>

              <div v-for="(pago, i) in form.pagos" :key="i" class="space-y-2">
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Medio</label>
                    <select v-model="pago.medio" @change="onMedioPagoChange(i)" :disabled="!editable"
                      class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50">
                      <option value="efectivo">Efectivo</option>
                      <option value="tarjeta_debito">T. Débito</option>
                      <option value="tarjeta_credito">T. Crédito</option>
                      <option value="qr">QR</option>
                      <option value="transferencia">Transferencia</option>
                      <option value="credito">A crédito</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Monto</label>
                    <input v-model="pago.monto" type="number" step="0.01" min="0"
                      @input="calcCambio" :disabled="!editable"
                      class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50"/>
                  </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                  <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta contable</label>
                    <select v-model="pago.cuenta_id" :disabled="!editable"
                      class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50">
                      <option :value="null">Sin asignar</option>
                      <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} {{ c.nombre }}</option>
                    </select>
                  </div>
                  <div v-if="pago.medio === 'efectivo'">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Cambio</label>
                    <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-2 py-1.5 text-xs text-right font-semibold"
                      :class="cambio >= 0 ? 'text-green-700' : 'text-red-600'">
                      {{ fmt(cambio) }}
                    </div>
                  </div>
                </div>
                <button v-if="form.pagos.length > 1 && editable" type="button" @click="form.pagos.splice(i, 1)"
                  class="text-xs text-red-400 hover:text-red-600">Quitar este pago</button>
              </div>

              <div class="pt-2 border-t space-y-1">
                <div class="flex justify-between text-xs">
                  <span class="text-gray-500">Total pagado:</span>
                  <span :class="totalPagado >= total ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'">
                    {{ fmt(totalPagado) }}
                  </span>
                </div>
                <div v-if="totalPagado < total" class="flex justify-between text-xs">
                  <span class="text-gray-500">Falta:</span>
                  <span class="text-red-600 font-semibold">{{ fmt(total - totalPagado) }}</span>
                </div>
              </div>
            </div>

            <!-- Facturación -->
            <div class="bg-white rounded-xl border shadow-sm p-4 space-y-3">
              <h3 class="text-sm font-semibold text-gray-700">Facturación</h3>

              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" v-model="form.con_factura" :disabled="!editable || venta.estado_siat === 'aprobada'"
                  class="rounded border-gray-300 text-blue-600"/>
                Emitir factura electrónica (SIAT)
              </label>

              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta cobro (Caja / CxC)</label>
                <select v-model="form.cuenta_cobro_id" :disabled="!editable"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50">
                  <option :value="null">Sin asignar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta IVA Débito Fiscal</label>
                <select v-model="form.cuenta_iva_df_id" :disabled="!editable"
                  class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400 disabled:bg-gray-50">
                  <option :value="null">Sin asignar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
            </div>

            <!-- Botón guardar -->
            <button type="submit"
              :disabled="guardando || !editable || hayErroresStock || totalPagado < total"
              class="w-full py-3 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 transition disabled:opacity-50 flex items-center justify-center gap-2">
              <svg v-if="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
              </svg>
              {{ guardando ? 'Procesando...' : 'Guardar cambios' }}
            </button>

            <Link :href="route('ventas.show', [periodo.id, venta.id])"
              class="block w-full py-2.5 text-center text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition">
              Ver detalle
            </Link>

            <p v-if="totalPagado < total && editable" class="text-xs text-red-500 text-center">
              El monto pagado no cubre el total de la venta.
            </p>
          </div>
        </div>
      </form>
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
    venta:      { type: Object, required: true },
    almacenes:  { type: Array, default: () => [] },
    clientes:   { type: Array, default: () => [] },
    productos:  { type: Array, default: () => [] },
    cuentas:    { type: Array, default: () => [] },
    errors:     { type: Object, default: () => ({}) },
  },
  data() {
    return {
      guardando:         false,
      clienteDelMaestro: !!this.venta.proveedor_cliente_id,
      form: {
        _method:               'put',
        periodo_id:            this.periodo.id,
        almacen_id:            this.venta.almacen_id,
        fecha:                 this.venta.fecha?.substring(0, 10) || '',
        forma_pago:            this.venta.forma_pago,
        dias_credito:          this.venta.dias_credito ?? 0,
        razon_social_cliente:  this.venta.razon_social_cliente || '',
        nit_cliente:           this.venta.nit_cliente || '',
        ci_cliente:            this.venta.ci_cliente || '',
        complemento_cliente:   this.venta.complemento_cliente || '',
        proveedor_cliente_id:  this.venta.proveedor_cliente_id || null,
        con_factura:           !!this.venta.con_factura,
        cuenta_cobro_id:       this.venta.cuenta_cobro_id || null,
        cuenta_iva_df_id:      this.venta.cuenta_iva_df_id || null,
        contabilizar_ahora:    false,
        detalles:              [],
        pagos:                 [],
      },
      productoMap: {},
      clienteMap:  {},
    }
  },
  computed: {
    subtotal() {
      return this.form.detalles.reduce((s, l) => s + (l._subtotal || 0), 0)
    },
    ivaTotal() {
      return Math.round(this.subtotal * 13 / 113 * 10000) / 10000
    },
    total() { return this.subtotal },
    totalPagado() {
      return this.form.pagos.reduce((s, p) => s + (+p.monto || 0), 0)
    },
    cambio() {
      const efectivo = this.form.pagos
        .filter(p => p.medio === 'efectivo')
        .reduce((s, p) => s + (+p.monto || 0), 0)
      return Math.max(0, efectivo - this.total)
    },
    hayErroresStock() {
      return this.form.detalles.some(l => l._sinStock)
    },
    editable() {
      // No editable si: anulada, contabilizada, o factura SIAT ya aprobada
      return this.venta.estado !== 'anulada'
        && this.venta.estado_contable !== 'contabilizado'
        && this.venta.estado_siat !== 'aprobada'
    },
    motivoNoEditable() {
      if (this.venta.estado === 'anulada') return 'Esta venta está anulada y no puede modificarse.'
      if (this.venta.estado_contable === 'contabilizado') return 'Esta venta ya fue contabilizada. Anúlala o reversa el asiento para editar.'
      if (this.venta.estado_siat === 'aprobada') return 'La factura SIAT ya fue aprobada. Solo se puede consultar.'
      return ''
    },
  },
  mounted() {
    this.productos.forEach(p => { this.productoMap[p.id] = p })
    this.clientes.forEach(c => { this.clienteMap[c.id] = c })
    this.hidratarDesdeVenta()
    if (this.form.almacen_id) this.refrescarStocks()
  },
  methods: {
    hidratarDesdeVenta() {
      // Detalles
      this.form.detalles = (this.venta.detalles || []).map(d => {
        const prod = this.productoMap[d.producto_id]
        const precios = prod ? this.preciosDeProducto(prod) : []
        return {
          producto_id:     d.producto_id,
          cantidad:        +d.cantidad,
          precio_unitario: +d.precio_unitario,
          descuento:       +(d.descuento || 0),
          _subtotal:       +(d.total ?? (d.cantidad * d.precio_unitario - (d.descuento || 0))),
          _stockActual:    null,
          _sinStock:       false,
          _unidad:         prod?.unidad_medida?.codigo ?? '',
          _precios:        precios,
          _listaActual:    precios.findIndex(p => +p === +d.precio_unitario) + 1 || 1,
        }
      })

      if (!this.form.detalles.length) this.agregarLinea()

      // Pagos
      this.form.pagos = (this.venta.pagos || []).map(p => ({
        medio:      p.medio,
        monto:      +p.monto,
        cambio:     +(p.cambio || 0),
        referencia: p.referencia || '',
        cuenta_id:  p.cuenta_id || null,
      }))

      if (!this.form.pagos.length) {
        this.form.pagos = [{ medio: 'efectivo', monto: this.total, cambio: 0, referencia: '', cuenta_id: null }]
      }
    },
    preciosDeProducto(prod) {
      const precios = [prod.precio_venta]
      if (prod.precio_venta_2 > 0) precios.push(prod.precio_venta_2)
      if (prod.precio_venta_3 > 0) precios.push(prod.precio_venta_3)
      return precios
    },
    agregarLinea() {
      this.form.detalles.push({
        producto_id: '', cantidad: 1, precio_unitario: 0, descuento: 0,
        _subtotal: 0, _stockActual: null, _sinStock: false, _unidad: '',
        _precios: [], _listaActual: 1,
      })
    },
    quitarLinea(i) {
      if (this.form.detalles.length > 1) this.form.detalles.splice(i, 1)
    },
    async onProductoChange(i) {
      const linea = this.form.detalles[i]
      const prod  = this.productoMap[linea.producto_id]
      if (!prod) return

      const precios = this.preciosDeProducto(prod)
      linea._precios        = precios
      linea._listaActual    = 1
      linea._unidad         = prod.unidad_medida?.codigo ?? ''
      linea.precio_unitario = prod.precio_venta || 0
      linea._sinStock       = false

      if (this.form.almacen_id) await this.cargarStock(i)
      this.calcLinea(i)
    },
    ciclarPrecio(i) {
      const linea = this.form.detalles[i]
      const lista = linea._listaActual % linea._precios.length + 1
      linea._listaActual    = lista
      linea.precio_unitario = linea._precios[lista - 1]
      this.calcLinea(i)
    },
    async cargarStock(i) {
      const linea = this.form.detalles[i]
      if (!linea.producto_id || !this.form.almacen_id) return
      try {
        const res  = await fetch(route('movimientos.stock-producto') +
          `?producto_id=${linea.producto_id}&almacen_id=${this.form.almacen_id}`)
        const data = await res.json()
        // Al editar, el stock actual ya incluye la cantidad que estamos vendiendo.
        // Sumamos la cantidad de esta línea para saber el stock "real" disponible.
        const esMismaVenta = this.venta.detalles?.some(d => d.producto_id === linea.producto_id)
        linea._stockActual = (data.cantidad || 0) + (esMismaVenta ? (+linea.cantidad || 0) : 0)
        this.validarStock(i)
      } catch { linea._stockActual = null }
    },
    validarStock(i) {
      const l    = this.form.detalles[i]
      const prod = this.productoMap[l.producto_id]
      l._sinStock = prod?.controla_stock && l._stockActual !== null
        && (+l.cantidad || 0) > l._stockActual
    },
    calcLinea(i) {
      const l = this.form.detalles[i]
      l._subtotal = Math.round(
        ((+l.cantidad || 0) * (+l.precio_unitario || 0) - (+l.descuento || 0)) * 10000
      ) / 10000
      this.validarStock(i)
      this.calcCambio()
    },
    calcCambio() {
      if (this.form.forma_pago !== 'mixto' && this.form.pagos.length === 1) {
        this.form.pagos[0].monto = this.total
      }
    },
    agregarPago() {
      this.form.pagos.push({ medio: 'efectivo', monto: 0, cambio: 0, referencia: '', cuenta_id: null })
    },
    onMedioPagoChange(i) {
      if (this.form.pagos[i].medio === 'credito') {
        this.form.pagos[i].monto = this.total - this.totalPagado + (+this.form.pagos[i].monto || 0)
      }
    },
    onClienteChange() {
      const c = this.clienteMap[this.form.proveedor_cliente_id]
      if (!c) return
      this.form.razon_social_cliente = c.razon_social
      this.form.nit_cliente          = c.nit ?? ''
      this.form.ci_cliente           = c.ci  ?? ''
      this.form.complemento_cliente  = c.complemento ?? ''
      if (c.dias_credito > 0) {
        this.form.forma_pago   = 'credito'
        this.form.dias_credito = c.dias_credito
      }
      if (c.cuenta_por_cobrar_id) this.form.cuenta_cobro_id = c.cuenta_por_cobrar_id
    },
    async refrescarStocks() {
      for (let i = 0; i < this.form.detalles.length; i++) {
        if (this.form.detalles[i].producto_id) await this.cargarStock(i)
      }
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency', currency: 'BOB', minimumFractionDigits: 2,
      }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 0, maximumFractionDigits: 4,
      }).format(v ?? 0)
    },
    submit() {
      if (!this.editable || this.hayErroresStock || this.totalPagado < this.total) return
      this.guardando = true
      router.post(route('inventario.ventas.update', [this.periodo.id, this.venta.id]), this.form, {
        onFinish: () => { this.guardando = false },
      })
    },
  },
  watch: {
    'form.forma_pago'(v) {
      if (v !== 'mixto') {
        this.form.pagos = [{
          medio:      v === 'credito' ? 'credito' : v === 'tarjeta' ? 'tarjeta_debito' : v === 'qr' ? 'qr' : 'efectivo',
          monto:      this.total,
          cambio:     0,
          referencia: '',
          cuenta_id:  null,
        }]
      }
    },
    total(v) {
      if (this.form.forma_pago !== 'mixto') {
        this.form.pagos[0].monto = v
      }
    },
  },
}
</script>
