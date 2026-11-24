<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">
        {{ compra ? `Editar Compra ${compra.nro_compra}` : 'Nueva Compra' }}
      </h2>
      <p class="text-xs text-gray-500">{{ periodo.nombre }}</p>
    </template>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">
      <div v-if="$page.props.errors?.error" class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        {{ $page.props.errors.error }}
      </div>

      <form @submit.prevent="submit">
        <!-- Cabecera -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Datos de la compra</h3>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">N° Compra</label>
              <input :value="nroCompra" readonly class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-1.5 text-sm font-mono text-gray-600"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Proveedor *</label>
              <select v-model="form.proveedor_id" required @change="onProveedorChange"
                class="w-full border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                :class="errors.proveedor_id ? 'border-red-400' : 'border-gray-200'">
                <option value="">Seleccionar...</option>
                <option v-for="p in proveedores" :key="p.id" :value="p.id">{{ p.razon_social }}</option>
              </select>
              <p v-if="errors.proveedor_id" class="text-xs text-red-500 mt-1">{{ errors.proveedor_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Almacén destino *</label>
              <select v-model="form.almacen_id" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Seleccionar...</option>
                <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
              </select>
              <p v-if="errors.almacen_id" class="text-xs text-red-500 mt-1">{{ errors.almacen_id }}</p>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Fecha recepción *</label>
              <input v-model="form.fecha_recepcion" type="date" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>
          </div>

          <!-- Datos factura SIN -->
          <div class="mt-4 pt-4 border-t">
            <p class="text-xs font-semibold text-gray-600 mb-3">Datos de la factura SIN (opcional)</p>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">N° Factura</label>
                <input v-model="form.nro_factura" type="text" placeholder="Ej: 1234"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Código de autorización</label>
                <input v-model="form.cod_autorizacion" type="text"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha factura</label>
                <input v-model="form.fecha_factura" type="date"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago</label>
                <select v-model="form.forma_pago"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option value="contado">Contado</option>
                  <option value="credito">Crédito</option>
                  <option value="mixto">Mixto</option>
                </select>
              </div>
            </div>
            <div v-if="form.forma_pago !== 'contado'" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Días de crédito</label>
                <input v-model.number="form.dias_credito" type="number" min="0"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
            </div>
          </div>

          <!-- Cuentas contables -->
          <div class="mt-4 pt-4 border-t">
            <p class="text-xs font-semibold text-gray-600 mb-3">Cuentas contables (para generar asiento)</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta de pago (Caja/Banco/CxP)</label>
                <select v-model="form.cuenta_pago_id"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option :value="null">Sin asignar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta IVA Crédito Fiscal</label>
                <select v-model="form.cuenta_iva_cf_id"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option :value="null">Sin asignar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Detalle de productos -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-semibold text-gray-700">Productos a comprar</h3>
              <p class="text-xs text-gray-400 mt-0.5">
                {{ form.detalles.length }} {{ form.detalles.length === 1 ? 'línea' : 'líneas' }}
              </p>
            </div>
            <button
              type="button"
              @click="agregarLinea"
              :disabled="guardando"
              :class="['inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition',
                       pulsoAgregar ? 'bg-blue-600 text-white' : 'bg-gray-100 hover:bg-gray-200 text-gray-700']"
            >
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Agregar producto
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 border-b border-t">
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Cantidad</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-32">Costo Unitario</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Descuento</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-32">Subtotal</th>
                  <th class="px-3 py-2 w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(det, i) in form.detalles" :key="i" class="border-b border-gray-100">
                  <td class="px-3 py-2">
                    <div class="relative">
                      <input
                        v-model="det._buscar"
                        type="text"
                        :placeholder="productoSel(det) ? productoSel(det).codigo + ' - ' + productoSel(det).descripcion : 'Buscar producto...'"
                        class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400"
                        @focus="det._buscando = true"
                        @blur="cerrarBuscador(i)"
                      />
                      <div v-if="det._buscando" class="absolute z-20 mt-1 w-full max-h-56 overflow-y-auto bg-white border border-gray-200 rounded shadow-lg">
                        <button
                          v-for="p in productosFiltrados(det._buscar)"
                          :key="p.id"
                          type="button"
                          class="block w-full text-left px-2 py-1.5 text-xs hover:bg-blue-50"
                          @mousedown.prevent="seleccionarProducto(i, p)"
                        >
                          <span class="font-mono text-gray-500">{{ p.codigo }}</span>
                          <span class="ml-2">{{ p.descripcion }}</span>
                          <span v-if="p.precio_costo" class="ml-2 text-gray-400">
                            (Bs {{ Number(p.precio_costo).toFixed(2) }})
                          </span>
                        </button>
                        <div v-if="!productosFiltrados(det._buscar).length" class="px-2 py-2 text-xs text-gray-400">
                          Sin resultados
                        </div>
                      </div>
                    </div>
                  </td>

                  <td class="px-3 py-2">
                    <input v-model.number="det.cantidad" type="number" step="0.0001" min="0.0001" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                      @input="calcularSubtotal(i)"/>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="det.costo_unitario" type="number" step="0.0001" min="0" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                      @input="calcularSubtotal(i)"/>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="det.descuento" type="number" step="0.0001" min="0"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"
                      @input="calcularSubtotal(i)"/>
                  </td>
                  <td class="px-3 py-2 text-right text-xs font-semibold text-gray-700">
                    {{ fmt(det._subtotal ?? 0) }}
                  </td>
                  <td class="px-3 py-2 text-center">
                    <button type="button" @click="quitarLinea(i)" v-if="form.detalles.length > 1"
                      class="text-gray-400 hover:text-red-500 transition">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                      </svg>
                    </button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-gray-50">
                  <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Total bruto (con IVA):</td>
                  <td class="px-3 py-2 text-right text-sm font-bold text-gray-800">{{ fmt(totalBruto) }}</td>
                  <td></td>
                </tr>
                <tr class="bg-gray-50">
                  <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-purple-700">IVA CF incluido (13/113):</td>
                  <td class="px-3 py-2 text-right text-sm font-bold text-purple-700">{{ fmt(ivaCalculado) }}</td>
                  <td></td>
                </tr>
                <tr class="bg-gray-50">
                  <td colspan="4" class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Base sin IVA:</td>
                  <td class="px-3 py-2 text-right text-sm font-bold text-gray-800">{{ fmt(baseSinIva) }}</td>
                  <td></td>
                </tr>
                <tr class="bg-blue-50">
                  <td colspan="4" class="px-3 py-2 text-right text-sm font-bold text-blue-800">TOTAL A PAGAR:</td>
                  <td class="px-3 py-2 text-right text-base font-bold text-blue-800">{{ fmt(totalBruto) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Observación -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <label class="block text-xs font-semibold text-gray-700 mb-1">Observación</label>
          <textarea v-model="form.observacion" rows="2"
            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>

        <!-- Botones -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="route('compras.index', periodo.id)"
            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Cancelar
          </Link>
          <button type="submit" :disabled="guardando || !form.detalles.length"
            class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
            {{ guardando ? 'Guardando...' : (compra ? 'Actualizar' : 'Guardar como borrador') }}
          </button>
        </div>
      </form>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

const filaVacia = () => ({
  producto_id:    '',
  cantidad:       1,
  costo_unitario: 0,
  descuento:      0,
  _subtotal:      0,
  _buscar:        '',
  _buscando:      false,
})

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    compra:      { type: Object,  default: null },
    periodo:     { type: Object,  required: true },
    nroCompra:   { type: String,  default: '' },
    proveedores: { type: Array,   default: () => [] },
    almacenes:   { type: Array,   default: () => [] },
    productos:   { type: Array,   default: () => [] },
    cuentas:     { type: Array,   default: () => [] },
    errors:      { type: Object,  default: () => ({}) },
  },
  data() {
    const c = this.compra

    // 🔑 FIX 1: garantizar SIEMPRE al menos una fila,
    // incluso si c.detalles viene [] (map devuelve [] y ?? no aplica)
    let detalles
    if (Array.isArray(c?.detalles) && c.detalles.length > 0) {
      detalles = c.detalles.map(d => {
        const prod = this.productos.find(p => p.id == d.producto_id)
        const row = {
          producto_id:    d.producto_id,
          cantidad:       Number(d.cantidad)       || 1,
          costo_unitario: Number(d.costo_unitario) || 0,
          descuento:      Number(d.descuento)      || 0,
          _subtotal:      Number(d.subtotal)       || 0,
          _buscar:        prod ? `${prod.codigo} - ${prod.descripcion}` : '',
          _buscando:      false,
        }
        // recalcular por si el backend no envió subtotal
        row._subtotal = Math.round(
          (row.cantidad * row.costo_unitario - row.descuento) * 10000
        ) / 10000
        return row
      })
    } else {
      detalles = [filaVacia()]
    }

    return {
      guardando:    false,
      pulsoAgregar: false,
      form: {
        periodo_id:       this.periodo.id,
        proveedor_id:     c?.proveedor_id     ?? '',
        almacen_id:       c?.almacen_id       ?? '',
        fecha_recepcion:  c?.fecha_recepcion  ?? new Date().toISOString().split('T')[0],
        nro_factura:      c?.nro_factura      ?? '',
        cod_autorizacion: c?.cod_autorizacion ?? '',
        fecha_factura:    c?.fecha_factura    ?? '',
        forma_pago:       c?.forma_pago       ?? 'contado',
        dias_credito:     c?.dias_credito     ?? 0,
        cuenta_pago_id:   c?.cuenta_pago_id   ?? null,
        cuenta_iva_cf_id: c?.cuenta_iva_cf_id ?? null,
        observacion:      c?.observacion      ?? '',
        detalles,
      },
    }
  },
  computed: {
    /** Suma de los subtotales de línea (cada _subtotal es BRUTO con IVA incluido) */
    totalBruto() {
      return this.form.detalles.reduce((s, d) => s + (Number(d._subtotal) || 0), 0)
    },
    /** IVA incluido: 13/113 del bruto */
    ivaCalculado() {
      return Math.round(this.totalBruto * 13 / 113 * 10000) / 10000
    },
    /** Base neta sin IVA */
    baseSinIva() {
      return Math.round((this.totalBruto - this.ivaCalculado) * 10000) / 10000
    },
  },
  mounted() {
    // Recalcular todas las filas al montar (por si vinieron con datos)
    this.form.detalles.forEach((_, i) => this.calcularSubtotal(i))
  },
  methods: {
    // ── Filas ────────────────────────────────────────────────
    agregarLinea() {
      this.form.detalles.push(filaVacia())
      // feedback visual
      this.pulsoAgregar = true
      setTimeout(() => (this.pulsoAgregar = false), 400)
      // scroll al final
      this.$nextTick(() => {
        const tbody = this.$el.querySelector('tbody')
        if (tbody) tbody.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' })
      })
    },
    quitarLinea(i) {
      if (this.form.detalles.length === 1) {
        // nunca dejar 0 filas: resetear
        this.form.detalles[0] = filaVacia()
        return
      }
      this.form.detalles.splice(i, 1)
    },

    // ── Buscador de productos ────────────────────────────────
    productoSel(det) {
      if (!det?.producto_id) return null
      return this.productos.find(p => p.id == det.producto_id) || null
    },
    productosFiltrados(q) {
      const term = (q || '').toLowerCase().trim()
      if (!term) return this.productos.slice(0, 30)
      return this.productos
        .filter(p =>
          (p.codigo || '').toLowerCase().includes(term) ||
          (p.descripcion || '').toLowerCase().includes(term)
        )
        .slice(0, 30)
    },
    seleccionarProducto(i, prod) {
      const det = this.form.detalles[i]
      const dup = this.form.detalles.some((d, idx) => idx !== i && d.producto_id == prod.id)
      if (dup) {
        if (!confirm(`El producto "${prod.descripcion}" ya está en otra línea. ¿Continuar?`)) return
      }
      det.producto_id    = prod.id
      det._buscar        = `${prod.codigo} - ${prod.descripcion}`
      det._buscando      = false
      det.costo_unitario = Number(prod.precio_costo) || 0
      this.calcularSubtotal(i)
    },
    cerrarBuscador(i) {
      // delay para permitir @mousedown.prevent del dropdown
      setTimeout(() => {
        const det = this.form.detalles[i]
        if (!det) return
        det._buscando = false
        // si no hay producto elegido y el texto no coincide, limpiar
        const sel = this.productoSel(det)
        if (!sel) det._buscar = ''
      }, 150)
    },

    // ── Proveedor ────────────────────────────────────────────
    onProveedorChange() {
      const prov = this.proveedores.find(p => p.id == this.form.proveedor_id)
      if (!prov) return
      if (prov.cuenta_por_pagar_id) this.form.cuenta_pago_id = prov.cuenta_por_pagar_id
      if (prov.dias_credito > 0) {
        this.form.forma_pago   = 'credito'
        this.form.dias_credito = prov.dias_credito
      }
    },

    // ── Cálculos ─────────────────────────────────────────────
    calcularSubtotal(i) {
      const d = this.form.detalles[i]
      if (!d) return
      const cant = Number(d.cantidad) || 0
      const cost = Number(d.costo_unitario) || 0
      const desc = Number(d.descuento) || 0
      // _subtotal = BRUTO (con IVA incluido) de la línea
      d._subtotal = Math.round((cant * cost - desc) * 10000) / 10000
    },

    // ── Formato ──────────────────────────────────────────────
    fmt(v) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency', currency: 'BOB', minimumFractionDigits: 2,
      }).format(v ?? 0)
    },

    // ── Submit ───────────────────────────────────────────────
    submit() {
      // Validación mínima cliente
      const invalida = this.form.detalles.some(d => !d.producto_id || !(Number(d.cantidad) > 0))
      if (invalida) {
        alert('Cada línea debe tener producto y cantidad mayor a 0.')
        return
      }

      this.guardando = true
      const method = this.compra ? 'put' : 'post'
      const url = this.compra
        ? route('compras.update', [this.periodo.id, this.compra.id])
        : route('compras.store', this.periodo.id)

      // Limpiar campos internos (_buscar, _buscando, _subtotal no van al back)
      const payload = {
        ...this.form,
        detalles: this.form.detalles.map(d => ({
          producto_id:    d.producto_id,
          cantidad:       Number(d.cantidad),
          costo_unitario: Number(d.costo_unitario),
          descuento:      Number(d.descuento) || 0,
        })),
      }

      router[method](url, payload, {
        preserveScroll: true,
        onFinish:  () => { this.guardando = false },
        onError:   (e) => console.warn('Errores:', e),
      })
    },
  },
}
</script>
