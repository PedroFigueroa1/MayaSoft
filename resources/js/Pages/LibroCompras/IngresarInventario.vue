<template>
  <AppLayoutAdmin>
    <Head title="Enviar compra a inventario" />

    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">Enviar compra a inventario</h2>
      <p class="text-xs text-gray-500">{{ periodo.nombre }}</p>
    </template>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="Object.keys(form.errors).length"
        class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm space-y-1">
        <p class="font-semibold">Errores del servidor:</p>
        <ul class="list-disc list-inside">
          <li v-for="(msg, key) in form.errors" :key="key">
            <span class="font-mono text-xs">{{ key }}:</span> {{ msg }}
          </li>
        </ul>
      </div>

      <form @submit.prevent="guardar">

        <!-- Datos de la factura -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Factura del libro de compras (SIAT)</h3>
          <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div><p class="text-xs text-gray-500">NIT</p><p class="font-mono">{{ libro.nit_proveedor }}</p></div>
            <div class="md:col-span-2"><p class="text-xs text-gray-500">Proveedor</p><p>{{ libro.razon_social }}</p></div>
            <div><p class="text-xs text-gray-500">N° Factura</p><p class="font-mono">{{ libro.nro_factura }}</p></div>
            <div><p class="text-xs text-gray-500">Fecha</p><p>{{ formatDate(libro.fecha_factura) }}</p></div>
          </div>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4 pt-4 border-t text-sm">
            <div><p class="text-xs text-gray-500">Importe total</p><p class="font-semibold">{{ fmt(libro.importe_total) }}</p></div>
            <div><p class="text-xs text-gray-500">Crédito fiscal (IVA)</p><p class="font-semibold text-purple-700">{{ fmt(iva) }}</p></div>
            <div><p class="text-xs text-gray-500">Descuento</p><p class="font-semibold">{{ fmt(libro.descuento || 0) }}</p></div>
            <div><p class="text-xs text-gray-500">Base neta para inventario</p><p class="font-bold text-blue-700">{{ fmt(baseNeta) }}</p></div>
          </div>
        </div>

        <!-- Almacén, cuentas, forma de pago -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-4">Destino y cuentas</h3>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Almacén destino *</label>
              <select v-model="form.almacen_id" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="">Seleccionar...</option>
                <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
              </select>
              <p v-if="form.errors.almacen_id" class="text-xs text-red-600 mt-1">{{ form.errors.almacen_id }}</p>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta IVA Crédito Fiscal</label>
              <select v-model="form.cuenta_iva_cf_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
              </select>
            </div>

            <!-- Contado: se elige la cuenta. Crédito: es la CxP del proveedor -->
            <div v-if="!esCredito">
              <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta de pago (Caja/Banco)</label>
              <select v-model="form.cuenta_pago_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
              </select>
            </div>
            <div v-else>
              <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta por pagar (proveedor)</label>
              <div v-if="proveedor_cxp"
                class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-1.5 text-sm text-gray-700">
                {{ proveedor_cxp.codigo }} - {{ proveedor_cxp.nombre }}
              </div>
              <div v-else
                class="w-full border border-amber-300 bg-amber-50 rounded-lg px-3 py-1.5 text-xs text-amber-800">
                El proveedor no tiene cuenta por pagar asignada. Podrás ingresar a inventario, pero no contabilizar hasta asignarla.
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago *</label>
              <select v-model="form.forma_pago" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
              </select>
              <p v-if="form.errors.forma_pago" class="text-xs text-red-600 mt-1">{{ form.errors.forma_pago }}</p>
            </div>
            <div v-if="esCredito">
              <label class="block text-xs font-medium text-gray-600 mb-1">Días de crédito</label>
              <input v-model.number="form.dias_credito" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              <p v-if="form.errors.dias_credito" class="text-xs text-red-600 mt-1">{{ form.errors.dias_credito }}</p>
            </div>
          </div>

          <p class="text-xs text-gray-500 mt-3">
            La cuenta de inventario (DEBE) sale de cada producto. Si un producto no tiene cuenta, se usa la del almacén.
          </p>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-semibold text-gray-700">Productos que ingresan</h3>
            <button type="button" @click="agregarLinea"
              class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
              + Agregar producto
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 border-b border-t">
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Producto</th>
                  <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Cuenta (DEBE)</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-28">Cantidad</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-36">Costo neto total</th>
                  <th class="px-3 py-2 text-right text-xs font-semibold text-gray-600 w-32">Costo unitario</th>
                  <th class="px-3 py-2 w-8"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(l, i) in form.lineas" :key="i" class="border-b border-gray-100">
                  <td class="px-3 py-2">
                    <select v-model="l.producto_id" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-blue-400">
                      <option value="">Seleccionar producto...</option>
                      <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.descripcion }}</option>
                    </select>
                    <p v-if="form.errors[`lineas.${i}.producto_id`]" class="text-xs text-red-600">
                      {{ form.errors[`lineas.${i}.producto_id`] }}
                    </p>
                  </td>
                  <td class="px-3 py-2 text-xs">
                    <template v-if="l.producto_id">
                      <span v-if="cuentaDeLinea(l)" class="text-gray-700">
                        {{ cuentaLabel(cuentaDeLinea(l)) }}
                        <span v-if="!productoMap[l.producto_id]?.cuenta_activo_id" class="text-amber-600">(del almacén)</span>
                      </span>
                      <span v-else class="text-red-600">Sin cuenta</span>
                    </template>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="l.cantidad" type="number" step="0.0001" min="0.0001" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="l.costo_total" type="number" step="0.0001" min="0.0001" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right focus:outline-none focus:ring-1 focus:ring-blue-400"/>
                  </td>
                  <td class="px-3 py-2 text-right text-xs text-gray-600">{{ fmt(costoUnit(l)) }}</td>
                  <td class="px-3 py-2 text-center">
                    <button type="button" v-if="form.lineas.length > 1" @click="form.lineas.splice(i, 1)"
                      class="text-gray-400 hover:text-red-500 transition">✕</button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-gray-50">
                  <td colspan="3" class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Suma de líneas:</td>
                  <td class="px-3 py-2 text-right text-sm font-bold text-gray-800">{{ fmt(sumaLineas) }}</td>
                  <td colspan="2"></td>
                </tr>
                <tr class="bg-gray-50">
                  <td colspan="3" class="px-3 py-2 text-right text-xs font-semibold text-gray-700">Base neta factura:</td>
                  <td class="px-3 py-2 text-right text-sm font-bold text-gray-800">{{ fmt(baseNeta) }}</td>
                  <td colspan="2"></td>
                </tr>
                <tr :class="cuadra ? 'bg-green-50' : 'bg-red-50'">
                  <td colspan="3" class="px-3 py-2 text-right text-sm font-bold" :class="cuadra ? 'text-green-700' : 'text-red-700'">
                    Diferencia:
                  </td>
                  <td class="px-3 py-2 text-right text-base font-bold" :class="cuadra ? 'text-green-700' : 'text-red-700'">
                    {{ fmt(baseNeta - sumaLineas) }}
                  </td>
                  <td colspan="2" class="px-3 py-2">
                    <button v-if="!cuadra" type="button" @click="completarUltima"
                      class="text-xs underline text-red-700">Completar última</button>
                    <button v-if="!cuadra && form.lineas.length > 1" type="button" @click="repartirPorCantidad"
                      class="text-xs underline text-red-700 ml-3">Repartir por cantidad</button>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Vista previa del asiento -->
        <div class="bg-white rounded-xl border shadow-sm p-5 mb-4">
          <h3 class="text-sm font-semibold text-gray-700 mb-3">Asiento que se generará al contabilizar</h3>
          <table class="w-full text-xs">
            <thead>
              <tr class="bg-gray-50 border-b border-t">
                <th class="px-3 py-2 text-left font-semibold text-gray-600">Cuenta</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-600 w-32">Debe</th>
                <th class="px-3 py-2 text-right font-semibold text-gray-600 w-32">Haber</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="d in asiento.debe" :key="d.id" class="border-b border-gray-100">
                <td class="px-3 py-1.5">{{ d.cuenta }}</td>
                <td class="px-3 py-1.5 text-right">{{ fmt(d.monto) }}</td>
                <td class="px-3 py-1.5"></td>
              </tr>
              <tr v-if="iva > 0" class="border-b border-gray-100">
                <td class="px-3 py-1.5">{{ cuentaLabel(form.cuenta_iva_cf_id) ?? 'IVA Crédito Fiscal (sin asignar)' }}</td>
                <td class="px-3 py-1.5 text-right">{{ fmt(iva) }}</td>
                <td class="px-3 py-1.5"></td>
              </tr>
              <tr class="border-b border-gray-100">
                <td class="px-3 py-1.5 pl-8">{{ asiento.haberCuenta ?? 'Cuenta de pago (sin asignar)' }}</td>
                <td class="px-3 py-1.5"></td>
                <td class="px-3 py-1.5 text-right">{{ fmt(asiento.haberTotal) }}</td>
              </tr>
            </tbody>
          </table>

          <p v-if="Number(libro.descuento) > 0" class="text-xs text-amber-700 mt-2">
            La factura tiene descuento: el haber ({{ fmt(asiento.haberTotal) }}) es la base neta más el IVA,
            no el importe total de la factura ({{ fmt(libro.importe_total) }}).
          </p>

          <ul v-if="asiento.avisos.length" class="mt-3 text-xs text-amber-700 list-disc list-inside space-y-0.5">
            <li v-for="(a, i) in asiento.avisos" :key="i">{{ a }}</li>
          </ul>

          <label class="flex items-start gap-2 mt-4 text-sm text-gray-700">
            <input type="checkbox" v-model="form.contabilizar_ahora" class="mt-0.5 rounded border-gray-300"/>
            <span>
              Contabilizar ahora
              <span class="block text-xs text-gray-500">
                Si no, queda pendiente y se contabiliza después desde el libro o desde la compra.
              </span>
            </span>
          </label>
        </div>

        <!-- Botones -->
        <div class="flex items-center justify-end gap-3">
          <Link :href="route('libro-compras.index', periodo.id)"
            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            Cancelar
          </Link>
          <button type="submit" :disabled="form.processing || !puedeGuardar"
            class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
            {{ form.processing ? 'Guardando...' : (form.contabilizar_ahora ? 'Ingresar y contabilizar' : 'Ingresar a inventario') }}
          </button>
        </div>
      </form>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { computed } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
  libro:     { type: Object, required: true },
  periodo:   { type: Object, required: true },
  almacenes: { type: Array, default: () => [] },
  productos: { type: Array, default: () => [] },
  cuentas:   { type: Array, default: () => [] },
  cuenta_iva_cf_default: { type: Number, default: null },
  proveedor_cxp:         { type: Object, default: null },
})

const iva = Number(props.libro.cf_iva_contable ?? props.libro.credito_fiscal ?? 0)
const baseNeta = computed(() =>
  Math.round((Number(props.libro.importe_total) - iva - Number(props.libro.descuento || 0)) * 10000) / 10000
)

const form = useForm({
  almacen_id:         props.libro.almacen_id || '',
  cuenta_iva_cf_id:   props.libro.cuenta_iva_cf_id ?? props.cuenta_iva_cf_default ?? null,
  cuenta_pago_id:     props.libro.cuenta_pago_id ?? null,
  forma_pago:         props.libro.forma_pago === 'credito' ? 'credito' : 'contado',
  dias_credito:       props.libro.dias_credito ?? 0,
  contabilizar_ahora: false,
  lineas: [{ producto_id: '', cantidad: 1, costo_total: baseNeta.value }],
})

const fmt = (n) => Number(n ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (d) => (d ? String(d).substring(0, 10) : '')
const costoUnit = (l) => (l.cantidad > 0 ? (Number(l.costo_total) || 0) / l.cantidad : 0)

const esCredito = computed(() => form.forma_pago === 'credito')

// ── Resolución de cuentas (misma prioridad que el backend) ──
const cuentaLabel = (id) => {
  const c = props.cuentas.find(x => x.id === id)
  return c ? `${c.codigo} - ${c.nombre}` : null
}
const productoMap = computed(() => Object.fromEntries(props.productos.map(p => [p.id, p])))
const almacenSel = computed(() => props.almacenes.find(a => a.id === form.almacen_id))
const cuentaAlmacen = computed(() => almacenSel.value?.cuenta_inventario_id ?? almacenSel.value?.cuenta_id ?? null)
const cuentaDeLinea = (l) => productoMap.value[l.producto_id]?.cuenta_activo_id ?? cuentaAlmacen.value ?? null

const sumaLineas = computed(() => form.lineas.reduce((s, l) => s + (Number(l.costo_total) || 0), 0))
const cuadra = computed(() => Math.abs(baseNeta.value - sumaLineas.value) <= 0.05)

// ── Vista previa del asiento ──
const asiento = computed(() => {
  const acumulado = {}
  const avisos = []

  form.lineas.forEach(l => {
    if (!l.producto_id) return
    const c = cuentaDeLinea(l)
    if (!c) {
      avisos.push(`"${productoMap.value[l.producto_id]?.descripcion}" no tiene cuenta de inventario (ni el almacén).`)
      return
    }
    acumulado[c] = (acumulado[c] || 0) + (Number(l.costo_total) || 0)
  })

  const debe = Object.entries(acumulado).map(([id, monto]) => ({
    id,
    cuenta: cuentaLabel(Number(id)) ?? `Cuenta #${id}`,
    monto,
  }))

  let haberCuenta = null
  if (esCredito.value) {
    haberCuenta = props.proveedor_cxp ? `${props.proveedor_cxp.codigo} - ${props.proveedor_cxp.nombre}` : null
    if (!haberCuenta) avisos.push('El proveedor no tiene cuenta por pagar asignada.')
  } else {
    haberCuenta = cuentaLabel(form.cuenta_pago_id)
    if (!haberCuenta) avisos.push('Falta la cuenta de pago.')
  }

  if (iva > 0 && !form.cuenta_iva_cf_id) avisos.push('Falta la cuenta de IVA Crédito Fiscal.')

  return { debe, haberCuenta, haberTotal: sumaLineas.value + iva, avisos }
})

const puedeGuardar = computed(() =>
  form.almacen_id &&
  form.forma_pago &&
  cuadra.value &&
  form.lineas.length > 0 &&
  form.lineas.every(l => l.producto_id && l.cantidad > 0 && l.costo_total > 0) &&
  // Para contabilizar ahora no puede haber avisos; para solo ingresar, sí se permiten
  (!form.contabilizar_ahora || asiento.value.avisos.length === 0)
)

const agregarLinea = () => form.lineas.push({ producto_id: '', cantidad: 1, costo_total: 0 })

const completarUltima = () => {
  const ult = form.lineas[form.lineas.length - 1]
  const otras = form.lineas.slice(0, -1).reduce((s, l) => s + (Number(l.costo_total) || 0), 0)
  ult.costo_total = Math.round((baseNeta.value - otras) * 10000) / 10000
}

const repartirPorCantidad = () => {
  const totalCant = form.lineas.reduce((s, l) => s + (Number(l.cantidad) || 0), 0)
  if (totalCant <= 0) return
  let acumulado = 0
  form.lineas.forEach((l, i) => {
    if (i === form.lineas.length - 1) {
      l.costo_total = Math.round((baseNeta.value - acumulado) * 10000) / 10000
    } else {
      l.costo_total = Math.round((baseNeta.value * (Number(l.cantidad) / totalCant)) * 10000) / 10000
      acumulado += l.costo_total
    }
  })
}

const guardar = () => {
  const dias = Number(form.dias_credito)
  form.dias_credito = Number.isFinite(dias) && dias >= 0 ? Math.trunc(dias) : 0
  if (form.forma_pago === 'contado') form.dias_credito = 0

  form.post(route('libro-compras.ingresar-inventario', props.libro.id), { preserveScroll: true })
}
</script>
