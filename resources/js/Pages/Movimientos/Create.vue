<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Registrar Movimiento de Inventario</h2>
          <p class="text-xs text-gray-500 mt-0.5">Entradas y salidas manuales del kardex</p>
        </div>
        <Link :href="route('movimientos.index')"
          class="inline-flex items-center gap-1.5 px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          Ver historial
        </Link>
      </div>
    </template>

    <div class="py-6 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Sin período activo -->
      <div v-if="!periodo" class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
        No hay un período contable activo. Activa un período antes de registrar movimientos.
      </div>

      <template v-else>
        <!-- Flash -->
        <div v-if="$page.props.flash?.success" class="flex items-start gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
          <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.errors?.error" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
          {{ $page.props.errors.error }}
        </div>

        <!-- Selector de tipo de operación: Entrada vs Salida -->
        <div class="bg-white rounded-xl border shadow-sm p-1.5 flex gap-1.5">
          <button type="button" @click="categoria = 'entrada'"
            :class="['flex-1 py-2.5 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2',
              categoria === 'entrada' ? 'bg-green-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100']">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Entrada (suma stock)
          </button>
          <button type="button" @click="categoria = 'salida'"
            :class="['flex-1 py-2.5 rounded-lg text-sm font-semibold transition flex items-center justify-center gap-2',
              categoria === 'salida' ? 'bg-red-600 text-white' : 'bg-gray-50 text-gray-600 hover:bg-gray-100']">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            Salida (resta stock)
          </button>
        </div>

        <form @submit.prevent="submit" class="bg-white rounded-xl border shadow-sm p-5 space-y-4">

          <!-- Tipo específico de movimiento -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de movimiento *</label>
            <select v-model="form.tipo" required
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
              <option v-for="t in tiposDisponibles" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
            <p class="text-xs text-gray-400 mt-1">{{ tipoDescripcion }}</p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Período -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Período *</label>
              <select v-model="form.periodo_id" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option v-for="p in periodos" :key="p.id" :value="p.id" :disabled="p.estado !== 'A'">
                  {{ p.nombre }} {{ p.estado !== 'A' ? '(cerrado)' : '' }}
                </option>
              </select>
            </div>
            <!-- Fecha -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha *</label>
              <input v-model="form.fecha" type="date" required
                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>
          </div>

          <!-- Almacén -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Almacén *</label>
            <select v-model="form.almacen_id" required @change="onAlmacenChange"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
              :class="errors.almacen_id ? 'border-red-400' : 'border-gray-200'">
              <option value="">Seleccionar almacén...</option>
              <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.codigo }} - {{ a.nombre }}</option>
            </select>
          </div>

          <!-- Producto -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Producto *</label>
            <select v-model="form.producto_id" required @change="onProductoChange"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
              :class="errors.producto_id ? 'border-red-400' : 'border-gray-200'">
              <option value="">Seleccionar producto...</option>
              <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.descripcion }}</option>
            </select>
            <p v-if="errors.producto_id" class="text-red-500 text-xs mt-1">{{ errors.producto_id }}</p>
          </div>

          <!-- Info de stock actual (solo en salidas) -->
          <div v-if="categoria === 'salida' && stockInfo" class="p-3 rounded-lg border"
            :class="stockSuficiente ? 'bg-blue-50 border-blue-200' : 'bg-red-50 border-red-200'">
            <div class="flex items-center justify-between text-sm">
              <span class="text-gray-600">Stock disponible en este almacén:</span>
              <span :class="stockSuficiente ? 'text-blue-700' : 'text-red-700'" class="font-bold">
                {{ fmtCant(stockInfo.cantidad) }} {{ unidadSeleccionada }}
              </span>
            </div>
            <div class="flex items-center justify-between text-xs text-gray-500 mt-1">
              <span>Costo promedio actual:</span>
              <span class="font-medium">{{ fmt(stockInfo.costo_promedio) }}</span>
            </div>
            <p v-if="!stockSuficiente && form.cantidad" class="text-xs text-red-600 mt-2 font-medium">
              ⚠ La cantidad ingresada supera el stock disponible.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Cantidad -->
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">
                Cantidad * <span v-if="unidadSeleccionada" class="text-gray-400 font-normal">({{ unidadSeleccionada }})</span>
              </label>
              <input v-model="form.cantidad" type="number" step="0.0001" min="0.0001" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                :class="errors.cantidad ? 'border-red-400' : 'border-gray-200'"/>
              <p v-if="errors.cantidad" class="text-red-500 text-xs mt-1">{{ errors.cantidad }}</p>
            </div>

            <!-- Costo unitario (solo entradas) -->
            <div v-if="categoria === 'entrada'">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Costo unitario *</label>
              <input v-model="form.costo_unitario" type="number" step="0.0001" min="0" required
                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                :class="errors.costo_unitario ? 'border-red-400' : 'border-gray-200'"/>
              <p v-if="errors.costo_unitario" class="text-red-500 text-xs mt-1">{{ errors.costo_unitario }}</p>
            </div>
            <!-- Costo que se aplicará (solo salidas, informativo) -->
            <div v-else-if="stockInfo">
              <label class="block text-xs font-semibold text-gray-700 mb-1">Costo que se aplicará</label>
              <input :value="fmt(costoSalidaEstimado)" readonly
                class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-sm text-gray-600"/>
              <p class="text-xs text-gray-400 mt-1">
                Calculado por método {{ metodoSeleccionado === 'PEPS' ? 'PEPS' : 'Promedio ponderado' }}
              </p>
            </div>
          </div>

          <!-- Total estimado -->
          <div v-if="form.cantidad" class="p-3 bg-gray-50 rounded-lg border border-gray-100 flex items-center justify-between text-sm">
            <span class="text-gray-600">Costo total del movimiento:</span>
            <span class="font-bold text-gray-800">{{ fmt(totalMovimiento) }}</span>
          </div>

          <!-- Motivo -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Motivo / Referencia</label>
            <input v-model="form.motivo" type="text" maxlength="200" placeholder="Ej: Saldo inicial, factura proveedor, daño por humedad..."
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>

          <!-- Observación -->
          <div>
            <label class="block text-xs font-semibold text-gray-700 mb-1">Observación</label>
            <textarea v-model="form.observacion" rows="2"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>

          <!-- Botón -->
          <div class="flex justify-end pt-2">
            <button type="submit" :disabled="guardando || (categoria === 'salida' && !stockSuficiente && form.cantidad)"
              :class="['px-6 py-2.5 text-sm font-semibold rounded-lg transition disabled:opacity-50',
                categoria === 'entrada' ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white']">
              {{ guardando ? 'Guardando...' : (categoria === 'entrada' ? 'Registrar entrada' : 'Registrar salida') }}
            </button>
          </div>
        </form>
      </template>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

const TIPOS_ENTRADA = [
  { value: 'entrada_manual',     label: 'Entrada manual (saldo inicial, donación)', desc: 'Para registrar stock inicial o ingresos sin compra formal.' },
  { value: 'entrada_devolucion', label: 'Devolución de cliente',                     desc: 'Producto que regresa porque el cliente lo devolvió.' },
  { value: 'ajuste_positivo',    label: 'Ajuste positivo (sobrante)',                desc: 'El recuento físico encontró más stock del que indica el sistema.' },
]

const TIPOS_SALIDA = [
  { value: 'salida_consumo',     label: 'Consumo interno',          desc: 'Uso interno: oficina, muestras, autoconsumo.' },
  { value: 'salida_merma',       label: 'Merma / pérdida',          desc: 'Producto dañado, vencido o perdido.' },
  { value: 'salida_devolucion',  label: 'Devolución a proveedor',   desc: 'Se regresa mercadería al proveedor.' },
  { value: 'ajuste_negativo',    label: 'Ajuste negativo (faltante)', desc: 'El recuento físico encontró menos stock del que indica el sistema.' },
]

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    periodo:     { type: Object, default: null },
    periodos:    { type: Array, default: () => [] },
    almacenes:   { type: Array, default: () => [] },
    productos:   { type: Array, default: () => [] },
    tipoInicial: { type: String, default: 'entrada_manual' },
    errors:      { type: Object, default: () => ({}) },
  },
  data() {
    const esSalida = TIPOS_SALIDA.some(t => t.value === this.tipoInicial)
    return {
      categoria: esSalida ? 'salida' : 'entrada',
      guardando: false,
      stockInfo: null,
      form: {
        periodo_id:     this.periodo?.id ?? '',
        tipo:           this.tipoInicial,
        producto_id:    '',
        almacen_id:     '',
        cantidad:       '',
        costo_unitario: '',
        fecha:          new Date().toISOString().split('T')[0],
        motivo:         '',
        observacion:    '',
      },
    }
  },
  computed: {
    tiposDisponibles() {
      return this.categoria === 'entrada' ? TIPOS_ENTRADA : TIPOS_SALIDA
    },
    tipoDescripcion() {
      const t = this.tiposDisponibles.find(x => x.value === this.form.tipo)
      return t?.desc ?? ''
    },
    productoSeleccionado() {
      return this.productos.find(p => p.id == this.form.producto_id)
    },
    unidadSeleccionada() {
      // El backend no manda código de unidad directamente aquí; se podría extender
      return this.productoSeleccionado?.unidad_medida_id ? '' : ''
    },
    metodoSeleccionado() {
      return this.productoSeleccionado?.metodo_costeo ?? 'PROM'
    },
    stockSuficiente() {
      if (!this.stockInfo || !this.form.cantidad) return true
      return parseFloat(this.stockInfo.cantidad) >= parseFloat(this.form.cantidad)
    },
    costoSalidaEstimado() {
      return this.stockInfo ? parseFloat(this.stockInfo.costo_promedio) : 0
    },
    totalMovimiento() {
      const cant = parseFloat(this.form.cantidad) || 0
      const costo = this.categoria === 'entrada'
        ? parseFloat(this.form.costo_unitario) || 0
        : this.costoSalidaEstimado
      return cant * costo
    },
  },
  watch: {
    categoria(nuevo) {
      // Al cambiar de categoría, resetear el tipo al primero de la nueva lista
      this.form.tipo = nuevo === 'entrada' ? TIPOS_ENTRADA[0].value : TIPOS_SALIDA[0].value
      if (nuevo === 'entrada') this.form.costo_unitario = ''
    },
  },
  methods: {
    async onProductoChange() {
      this.stockInfo = null
      if (!this.form.producto_id) return

      // Prellenar costo unitario con el precio_costo del producto (si entrada)
      if (this.categoria === 'entrada' && this.productoSeleccionado) {
        this.form.costo_unitario = this.productoSeleccionado.precio_costo ?? 0
      }

      if (this.form.almacen_id) {
        await this.cargarStock()
      }
    },
    async onAlmacenChange() {
      if (this.form.producto_id) {
        await this.cargarStock()
      }
    },
    async cargarStock() {
      try {
        const res = await fetch(route('api.productos.stock', this.form.producto_id))
        const data = await res.json()
        this.stockInfo = data.find(s => s.almacen_id == this.form.almacen_id) ?? { cantidad: 0, costo_promedio: 0 }
      } catch (e) {
        this.stockInfo = { cantidad: 0, costo_promedio: 0 }
      }
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', minimumFractionDigits: 2 }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 0, maximumFractionDigits: 4 }).format(v ?? 0)
    },
    submit() {
      this.guardando = true
      router.post(route('movimientos.store'), this.form, {
        onFinish: () => { this.guardando = false },
        onSuccess: () => {
          // Resetear formulario manteniendo período/almacén/tipo para captura rápida en serie
          this.form.producto_id = ''
          this.form.cantidad = ''
          this.form.costo_unitario = ''
          this.form.motivo = ''
          this.form.observacion = ''
          this.stockInfo = null
        },
      })
    },
  },
}
</script>
