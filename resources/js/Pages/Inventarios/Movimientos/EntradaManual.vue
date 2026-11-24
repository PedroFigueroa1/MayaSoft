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
          <h2 class="text-xl font-semibold text-gray-800 leading-tight">Entrada de inventario</h2>
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
        {{ $page.props.errors.error }}
      </div>

      <form @submit.prevent="submit">

        <!-- ── BLOQUE 1: Tipo y datos generales ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
          <h3 class="text-sm font-semibold text-gray-700 pb-2 border-b">Datos de la entrada</h3>

          <!-- Tipo de entrada como tarjetas -->
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-2">Tipo de entrada *</label>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <button v-for="t in tipos" :key="t.value" type="button"
                @click="form.tipo = t.value"
                :class="[
                  'text-left p-3 rounded-xl border-2 transition',
                  form.tipo === t.value
                    ? 'border-blue-500 bg-blue-50'
                    : 'border-gray-200 hover:border-gray-300 bg-white'
                ]">
                <div class="text-sm font-medium" :class="form.tipo === t.value ? 'text-blue-700' : 'text-gray-800'">
                  {{ t.label }}
                </div>
                <div class="text-xs mt-0.5" :class="form.tipo === t.value ? 'text-blue-500' : 'text-gray-400'">
                  {{ t.hint }}
                </div>
              </button>
            </div>
            <p v-if="errors.tipo" class="text-red-500 text-xs mt-1">{{ errors.tipo }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Almacén destino *</label>
              <select v-model="form.almacen_id" required
                class="w-full border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
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
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">
                Proveedor <span class="text-gray-400">(opcional)</span>
              </label>
              <select v-model="form.proveedor_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option :value="null">Sin proveedor</option>
                <option v-for="p in proveedores" :key="p.id" :value="p.id">
                  {{ p.razon_social }}
                </option>
              </select>
            </div>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Motivo</label>
            <input v-model="form.motivo" type="text" maxlength="200"
              placeholder="Ej: Saldo inicial apertura 2025, Compra mercado informal..."
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
        </div>

        <!-- ── BLOQUE 2: Datos factura SIN (solo entrada_compra) ── -->
        <div v-if="form.tipo === 'entrada_compra'"
          class="bg-amber-50 rounded-xl border border-amber-200 p-5 space-y-4">
          <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-amber-800">Factura SIN (opcional)</h3>
            <label class="flex items-center gap-2 text-sm text-amber-700 cursor-pointer">
              <input type="checkbox" v-model="form.tiene_factura"
                class="rounded border-amber-300 text-amber-600"/>
              Esta compra tiene factura del SIN
            </label>
          </div>

          <div v-if="form.tiene_factura" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">N° Factura *</label>
              <input v-model="form.nro_factura" type="text" maxlength="30"
                class="w-full border border-amber-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Cód. autorización</label>
              <input v-model="form.cod_autorizacion" type="text"
                class="w-full border border-amber-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Fecha factura</label>
              <input v-model="form.fecha_factura" type="date"
                class="w-full border border-amber-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400"/>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-700 mb-1">Cta. de pago (Caja/Banco)</label>
              <select v-model="form.cuenta_pago_id"
                class="w-full border border-amber-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas" :key="c.id" :value="c.id">
                  {{ c.codigo }} - {{ c.nombre }}
                </option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <label class="block text-xs font-medium text-gray-700 mb-1">Cta. IVA Crédito Fiscal</label>
              <select v-model="form.cuenta_iva_cf_id"
                class="w-full border border-amber-300 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas" :key="c.id" :value="c.id">
                  {{ c.codigo }} - {{ c.nombre }}
                </option>
              </select>
            </div>
            <div class="sm:col-span-2">
              <p class="text-xs text-amber-700 mt-2 bg-amber-100 rounded-lg px-3 py-2">
                Al guardar, esta compra aparecerá en el <strong>Libro de Compras</strong> pendiente de contabilizar.
              </p>
            </div>
          </div>
        </div>

        <!-- ── BLOQUE 3: Líneas de productos ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Productos</h3>
            <button type="button" @click="agregarLinea"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
              </svg>
              Agregar producto
            </button>
          </div>

          <!-- Tabla de líneas -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-t bg-gray-50">
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-24">Cantidad</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-32">Costo unitario</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Subtotal</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Stock actual</th>
                  <th class="w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(linea, i) in form.lineas" :key="i"
                  class="border-b border-gray-100 hover:bg-gray-50">

                  <!-- Selector producto -->
                  <td class="px-3 py-2">
                    <select v-model="linea.producto_id" required
                      @change="onProductoChange(i)"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                      <option value="">Seleccionar producto...</option>
                      <option v-for="p in productos" :key="p.id" :value="p.id">
                        {{ p.codigo }} — {{ p.descripcion }}
                      </option>
                    </select>
                    <!-- Unidad de medida -->
                    <span v-if="linea._unidad" class="text-xs text-gray-400 ml-1">{{ linea._unidad }}</span>
                  </td>

                  <!-- Cantidad -->
                  <td class="px-3 py-2">
                    <input v-model="linea.cantidad" type="number" step="0.0001" min="0.0001" required
                      @input="calcLinea(i)"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                  </td>

                  <!-- Costo unitario -->
                  <td class="px-3 py-2">
                    <input v-model="linea.costo_unitario" type="number" step="0.0001" min="0" required
                      @input="calcLinea(i)"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                    <span class="text-xs text-gray-400">Bs</span>
                  </td>

                  <!-- Subtotal calculado -->
                  <td class="px-3 py-2 text-right text-xs font-semibold text-gray-700">
                    {{ fmt(linea._subtotal) }}
                  </td>

                  <!-- Stock actual (badge informativo) -->
                  <td class="px-3 py-2">
                    <span v-if="linea._stockActual !== null"
                      class="inline-block text-xs px-2 py-0.5 rounded-full"
                      :class="linea._stockActual > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'">
                      {{ fmtCant(linea._stockActual) }} en stock
                    </span>
                    <span v-else class="text-xs text-gray-300">—</span>
                  </td>

                  <!-- Quitar línea -->
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
                <tr class="bg-blue-50 border-t">
                  <td colspan="3" class="px-3 py-2 text-right text-sm font-semibold text-blue-800">
                    Total entrada:
                  </td>
                  <td class="px-3 py-2 text-right text-base font-bold text-blue-800">
                    {{ fmt(totalEntrada) }}
                  </td>
                  <td colspan="2"></td>
                </tr>
              </tfoot>
            </table>
          </div>

          <div v-if="$page.props.errors['lineas']" class="mt-2 text-red-500 text-xs">
            {{ $page.props.errors['lineas'] }}
          </div>
        </div>

        <!-- ── Observación ── -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <label class="block text-xs font-semibold text-gray-700 mb-1">Observación interna</label>
          <textarea v-model="form.observacion" rows="2" maxlength="500"
            placeholder="Notas adicionales sobre esta entrada..."
            class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>

        <!-- ── Botones ── -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="route('inventario.movimientos.kardex', periodo.id)"
            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Cancelar
          </Link>
          <button type="submit" :disabled="guardando || !form.tipo || !form.almacen_id || form.lineas.length === 0"
            class="px-5 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition disabled:opacity-50 flex items-center gap-2">
            <svg v-if="guardando" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            {{ guardando ? 'Registrando...' : 'Registrar entrada' }}
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
    periodo:    { type: Object, required: true },
    productos:  { type: Array, default: () => [] },
    almacenes:  { type: Array, default: () => [] },
    proveedores:{ type: Array, default: () => [] },
    cuentas:    { type: Array, default: () => [] },
    tipos:      { type: Array, default: () => [] },
    errors:     { type: Object, default: () => ({}) },
  },
  data() {
    return {
      guardando: false,
      form: {
        periodo_id:       this.periodo.id,
        tipo:             'entrada_manual',
        almacen_id:       '',
        fecha:            new Date().toISOString().split('T')[0],
        motivo:           '',
        observacion:      '',
        proveedor_id:     null,
        tiene_factura:    false,
        nro_factura:      '',
        cod_autorizacion: '',
        fecha_factura:    '',
        cuenta_pago_id:   null,
        cuenta_iva_cf_id: null,
        lineas: [
          { producto_id: '', cantidad: 1, costo_unitario: 0, _subtotal: 0, _stockActual: null, _unidad: '' },
        ],
      },
      productoMap: {},
    }
  },
  computed: {
    totalEntrada() {
      return this.form.lineas.reduce((s, l) => s + (l._subtotal || 0), 0)
    },
  },
  mounted() {
    this.productos.forEach(p => { this.productoMap[p.id] = p })
  },
  methods: {
    agregarLinea() {
      this.form.lineas.push({
        producto_id: '', cantidad: 1, costo_unitario: 0,
        _subtotal: 0, _stockActual: null, _unidad: '',
      })
    },
    quitarLinea(i) {
      if (this.form.lineas.length > 1) this.form.lineas.splice(i, 1)
    },
    async onProductoChange(i) {
      const linea   = this.form.lineas[i]
      const prod    = this.productoMap[linea.producto_id]
      if (!prod) return

      linea._unidad        = prod.unidad_medida?.codigo ?? ''
      linea.costo_unitario = prod.precio_costo || 0

      // Consultar stock actual si hay almacén seleccionado
      if (this.form.almacen_id) {
        try {
          const res = await fetch(route('inventario.movimientos.stock-producto') +
            `?producto_id=${linea.producto_id}&almacen_id=${this.form.almacen_id}`)
          const data = await res.json()
          linea._stockActual   = data.cantidad
          // Pre-llenar costo con el promedio vigente (más preciso que el precio_costo del producto)
          if (data.costo_promedio > 0) linea.costo_unitario = data.costo_promedio
        } catch (e) {
          linea._stockActual = null
        }
      }

      this.calcLinea(i)
    },
    calcLinea(i) {
      const l = this.form.lineas[i]
      l._subtotal = Math.round((+l.cantidad || 0) * (+l.costo_unitario || 0) * 10000) / 10000
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
        if (!this.periodo?.id) {
            alert('No se encontró el período de inventario seleccionado.')
            return
        }

        this.guardando = true

        router.post(
            route('inventario.movimientos.entrada.store', {
                periodoId: this.periodo.id
            }),
            this.form,
            {
                onFinish: () => {
                    this.guardando = false
                },
            }
        )
    },
  },
  watch: {
    // Cuando cambia el almacén, refrescar el stock de todas las líneas ya cargadas
    'form.almacen_id'() {
      this.form.lineas.forEach((l, i) => {
        if (l.producto_id) this.onProductoChange(i)
      })
    },
  },
}
</script>
