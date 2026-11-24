<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('inventario.movimientos.kardex', periodo.id)"
          class="p-1.5 text-gray-400 hover:text-gray-600 rounded-lg transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </Link>
        <div>
          <h2 class="text-xl font-semibold text-gray-800 leading-tight">Salida de inventario</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
      </div>
    </template>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <!-- Error global -->
      <div v-if="$page.props.errors?.error"
        class="flex items-start gap-2 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <svg class="w-4 h-4 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
        </svg>
        <span>{{ $page.props.errors.error }}</span>
      </div>

      <!-- Nota: ventas no van por aquí -->
      <div class="flex items-center gap-2 p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Las ventas a clientes con factura se registran desde el módulo de Ventas / POS. Aquí solo se registran bajas internas.
      </div>

      <form @submit.prevent="submit">

        <!-- ── BLOQUE 1: Tipo y datos generales ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700 pb-2 border-b">Datos de la salida</h3>

          <!-- Tipo como tarjetas -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-2">Tipo de salida *</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <button v-for="t in tipos" :key="t.value" type="button"
                @click="form.tipo = t.value"
                :class="[
                  'text-left p-3 rounded-xl border-2 transition',
                  form.tipo === t.value
                    ? 'border-red-400 bg-red-50'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                ]">
                <div class="text-sm font-medium" :class="form.tipo === t.value ? 'text-red-700' : 'text-gray-800'">
                  {{ t.label }}
                </div>
                <div class="text-xs mt-0.5" :class="form.tipo === t.value ? 'text-red-400' : 'text-gray-400'">
                  {{ t.hint }}
                </div>
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Almacén de origen *</label>
              <select v-model="form.almacen_id" required @change="refrescarStocks"
                class="w-full border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                :class="errors.almacen_id ? 'border-red-400' : 'border-gray-200'">
                <option value="">Seleccionar...</option>
                <option v-for="a in almacenes" :key="a.id" :value="a.id">
                  {{ a.codigo }} — {{ a.nombre }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
              <input v-model="form.fecha" type="date" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Motivo *</label>
              <input v-model="form.motivo" type="text" required maxlength="200"
                :placeholder="motivoPlaceholder"
                class="w-full border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                :class="errors.motivo ? 'border-red-400' : 'border-gray-200'"/>
              <p v-if="errors.motivo" class="text-red-500 text-xs mt-1">{{ errors.motivo }}</p>
            </div>
          </div>
        </div>

        <!-- ── BLOQUE 2: Líneas de productos ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Productos a dar de baja</h3>
            <button type="button" @click="agregarLinea"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Agregar producto
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-t bg-gray-50">
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Cantidad</th>
                  <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600 w-36">Stock disponible</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-32">Costo estimado</th>
                  <th class="w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(linea, i) in form.lineas" :key="i"
                  class="border-b border-gray-100 hover:bg-gray-50"
                  :class="linea._sinStock ? 'bg-red-50' : ''">

                  <!-- Selector producto -->
                  <td class="px-3 py-2">
                    <select v-model="linea.producto_id" required
                      @change="onProductoChange(i)"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-red-400">
                      <option value="">Seleccionar producto...</option>
                      <option v-for="p in productos" :key="p.id" :value="p.id">
                        {{ p.codigo }} — {{ p.descripcion }}
                      </option>
                    </select>
                    <span v-if="linea._unidad" class="text-xs text-gray-400 ml-1">{{ linea._unidad }}</span>
                  </td>

                  <!-- Cantidad -->
                  <td class="px-3 py-2">
                    <input v-model="linea.cantidad" type="number" step="0.0001" min="0.0001" required
                      @input="validarCantidad(i)"
                      class="w-full border rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-red-400"
                      :class="linea._sinStock ? 'border-red-400 bg-red-50' : 'border-gray-200'"/>
                    <p v-if="linea._sinStock" class="text-red-500 text-xs mt-0.5">
                      Máx: {{ fmtCant(linea._stockActual) }}
                    </p>
                  </td>

                  <!-- Stock actual -->
                  <td class="px-3 py-2 text-center">
                    <span v-if="linea._stockActual !== null"
                      :class="[
                        'inline-block text-xs px-2 py-0.5 rounded-full font-medium',
                        linea._stockActual <= 0 ? 'bg-red-100 text-red-700' :
                        linea._stockActual < 5  ? 'bg-amber-100 text-amber-700' :
                                                   'bg-green-100 text-green-700'
                      ]">
                      {{ fmtCant(linea._stockActual) }}
                    </span>
                    <span v-else class="text-xs text-gray-300">—</span>
                  </td>

                  <!-- Costo estimado de la salida (informativo) -->
                  <td class="px-3 py-2 text-right text-xs text-gray-500">
                    <template v-if="linea._costoPromedio > 0">
                      {{ fmt(linea.cantidad * linea._costoPromedio) }}
                    </template>
                    <span v-else class="text-gray-300">—</span>
                  </td>

                  <!-- Quitar -->
                  <td class="px-3 py-2 text-center">
                    <button type="button" @click="quitarLinea(i)"
                      v-if="form.lineas.length > 1"
                      class="text-gray-300 hover:text-red-500 transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-red-50 border-t">
                  <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-red-800">
                    Total costo estimado de baja:
                  </td>
                  <td class="px-3 py-2 text-right text-base font-bold text-red-800">
                    {{ fmt(totalCosto) }}
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Alerta stock insuficiente -->
          <div v-if="hayErroresStock"
            class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-700">
            Hay productos con stock insuficiente. Corrígelos antes de guardar.
          </div>
        </div>

        <!-- ── Observación ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <label class="block text-xs font-semibold text-gray-700 mb-1">Observación</label>
          <textarea v-model="form.observacion" rows="2"
            placeholder="Detalles adicionales sobre esta baja..."
            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"/>
        </div>

        <!-- ── Botones ── -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="route('inventario.movimientos.kardex', periodo.id)"
            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Cancelar
          </Link>
          <button type="submit"
            :disabled="guardando || !form.tipo || !form.almacen_id || hayErroresStock"
            class="px-5 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition disabled:opacity-50 flex items-center gap-2">
            <svg v-if="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ guardando ? 'Registrando...' : 'Registrar salida' }}
          </button>
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
    periodo:   { type: Object, required: true },
    productos: { type: Array, default: () => [] },
    almacenes: { type: Array, default: () => [] },
    tipos:     { type: Array, default: () => [] },
    errors:    { type: Object, default: () => ({}) },
  },
  data() {
    return {
      guardando: false,
      form: {
        periodo_id:  this.periodo.id,
        tipo:        'salida_consumo',
        almacen_id:  '',
        fecha:       new Date().toISOString().split('T')[0],
        motivo:      '',
        observacion: '',
        lineas: [
          { producto_id: '', cantidad: 1, _stockActual: null, _costoPromedio: 0, _unidad: '', _sinStock: false },
        ],
      },
      productoMap: {},
    }
  },
  computed: {
    hayErroresStock() {
      return this.form.lineas.some(l => l._sinStock)
    },
    totalCosto() {
      return this.form.lineas.reduce((s, l) => {
        return s + ((+l.cantidad || 0) * (l._costoPromedio || 0))
      }, 0)
    },
    motivoPlaceholder() {
      return {
        salida_consumo:    'Ej: Material de oficina mes enero, Muestra para cliente...',
        salida_merma:      'Ej: Producto vencido, Daño en almacén, Robo...',
        salida_devolucion: 'Ej: Devolución factura 1234, Producto defectuoso...',
      }[this.form.tipo] ?? 'Describe el motivo...'
    },
  },
  mounted() {
    this.productos.forEach(p => { this.productoMap[p.id] = p })
  },
  methods: {
    agregarLinea() {
      this.form.lineas.push({
        producto_id: '', cantidad: 1,
        _stockActual: null, _costoPromedio: 0, _unidad: '', _sinStock: false,
      })
    },
    quitarLinea(i) {
      if (this.form.lineas.length > 1) this.form.lineas.splice(i, 1)
    },
    async onProductoChange(i) {
      const linea = this.form.lineas[i]
      const prod  = this.productoMap[linea.producto_id]
      if (!prod) return

      linea._unidad        = prod.unidad_medida?.codigo ?? ''
      linea._costoPromedio = prod.precio_costo || 0
      linea._stockActual   = null
      linea._sinStock      = false

      if (this.form.almacen_id) {
        await this.cargarStock(i)
      }
    },
    async cargarStock(i) {
      const linea = this.form.lineas[i]
      if (!linea.producto_id || !this.form.almacen_id) return

      try {
        const res  = await fetch(route('inventario.movimientos.stock-producto') +
          `?producto_id=${linea.producto_id}&almacen_id=${this.form.almacen_id}`)
        const data = await res.json()
        linea._stockActual   = data.cantidad
        linea._costoPromedio = data.costo_promedio || linea._costoPromedio
        this.validarCantidad(i)
      } catch (e) {
        linea._stockActual = null
      }
    },
    validarCantidad(i) {
      const l = this.form.lineas[i]
      l._sinStock = l._stockActual !== null && (+l.cantidad || 0) > l._stockActual
    },
    async refrescarStocks() {
      for (let i = 0; i < this.form.lineas.length; i++) {
        if (this.form.lineas[i].producto_id) await this.cargarStock(i)
      }
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency', currency: 'BOB', minimumFractionDigits: 2
      }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 0, maximumFractionDigits: 4
      }).format(v ?? 0)
    },
    submit() {
      if (this.hayErroresStock) return
      this.guardando = true
      router.post(route('inventario.movimientos.salida.store'), this.form, {
        onFinish: () => { this.guardando = false },
      })
    },
  },
}
</script>
