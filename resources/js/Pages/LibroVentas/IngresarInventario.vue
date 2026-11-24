<template>
  <AppLayoutAdmin>
    <Head title="Enviar venta a inventario" />

    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">Enviar venta a inventario</h2>
      <p class="text-xs text-gray-500">{{ periodo.nombre }}</p>
    </template>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="Object.keys(form.errors).length"
           class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <ul class="list-disc list-inside">
          <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
        </ul>
      </div>

      <form @submit.prevent="guardar" class="space-y-4">

        <!-- Factura -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div class="grid grid-cols-2 md:grid-cols-5 gap-4 text-sm">
            <div><p class="text-xs text-gray-500">NIT</p><p class="font-mono">{{ libro.nit_cliente }}</p></div>
            <div class="md:col-span-2"><p class="text-xs text-gray-500">Cliente</p><p>{{ libro.razon_social_cliente }}</p></div>
            <div><p class="text-xs text-gray-500">N° Factura</p><p class="font-mono">{{ libro.nro_factura }}</p></div>
            <div><p class="text-xs text-gray-500">Fecha</p><p>{{ formatDate(libro.fecha_factura) }}</p></div>
          </div>
          <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t text-sm">
            <div><p class="text-xs text-gray-500">Importe total</p><p class="font-semibold">{{ fmt(libro.importe_total) }}</p></div>
            <div><p class="text-xs text-gray-500">Débito fiscal</p><p class="font-semibold text-purple-700">{{ fmt(iva) }}</p></div>
            <div><p class="text-xs text-gray-500">Descuento</p><p class="font-semibold">{{ fmt(descuento) }}</p></div>
          </div>
        </div>

        <!-- Destino y pago -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Almacén origen *</label>
              <select v-model="form.almacen_id" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <option value="">Seleccionar...</option>
                <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
              </select>
            </div>

            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago *</label>
              <select v-model="form.forma_pago"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
              </select>
            </div>

            <div v-if="!esCredito">
              <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta de cobro (Caja/Banco)</label>
              <select v-model="form.cuenta_cobro_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
              </select>
            </div>
            <div v-else>
              <label class="block text-xs font-medium text-gray-600 mb-1">Días de crédito</label>
              <input v-model.number="form.dias_credito" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm"/>
              <p class="text-xs mt-1" :class="cliente_cxc ? 'text-gray-500' : 'text-amber-700'">
                CxC: {{ cliente_cxc ? `${cliente_cxc.codigo} - ${cliente_cxc.nombre}` : 'el cliente no tiene cuenta por cobrar' }}
              </p>
            </div>
          </div>

          <div class="mt-4 md:w-1/3">
            <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta IVA Débito Fiscal</label>
            <select v-model="form.cuenta_iva_df_id"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
              <option :value="null">Sin asignar</option>
              <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
            </select>
          </div>
        </div>

        <!-- Productos -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-sm font-semibold text-gray-700">Productos que salen</h3>
            <button type="button" @click="agregarLinea"
              class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg">
              + Agregar producto
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="bg-gray-50 border-b border-t text-xs text-gray-600">
                  <th class="px-3 py-2 text-left font-semibold">Producto</th>
                  <th class="px-3 py-2 text-right font-semibold w-24">Cantidad</th>
                  <th class="px-3 py-2 text-right font-semibold w-32">P. unit. (c/IVA)</th>
                  <th class="px-3 py-2 text-right font-semibold w-28">Total</th>
                  <th class="px-3 py-2 w-16"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(l, i) in form.lineas" :key="i" class="border-b border-gray-100">
                  <td class="px-3 py-2">
                    <select v-model="l.producto_id" required @change="alElegirProducto(l)"
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                      <option value="">Seleccionar...</option>
                      <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} - {{ p.descripcion }}</option>
                    </select>
                    <p v-if="productoMap[l.producto_id]" class="text-[11px] text-gray-400 mt-0.5">
                      Costeo: {{ productoMap[l.producto_id].metodo_costeo }}
                      <span v-if="!productoMap[l.producto_id].controla_stock"> · servicio (no mueve stock)</span>
                    </p>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="l.cantidad" type="number" step="0.0001" min="0.0001" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right"/>
                  </td>
                  <td class="px-3 py-2">
                    <input v-model.number="l.precio_unitario" type="number" step="0.0001" min="0" required
                      class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right"/>
                  </td>
                  <td class="px-3 py-2 text-right text-xs font-semibold">{{ fmt(totalLinea(l)) }}</td>
                  <td class="px-3 py-2 text-center whitespace-nowrap">
                    <button v-if="!cuadra" type="button" @click="ajustarPrecio(l)"
                      title="Ajustar el precio de esta línea para que cuadre con la factura"
                      class="text-xs underline text-blue-700 mr-2">Ajustar</button>
                    <button v-if="form.lineas.length > 1" type="button" @click="form.lineas.splice(i, 1)"
                      class="text-gray-400 hover:text-red-500">✕</button>
                  </td>
                </tr>
              </tbody>
              <tfoot>
                <tr class="bg-gray-50 text-xs">
                  <td colspan="3" class="px-3 py-2 text-right font-semibold text-gray-700">Suma de líneas</td>
                  <td class="px-3 py-2 text-right font-bold">{{ fmt(sumaLineas) }}</td>
                  <td></td>
                </tr>
                <tr class="bg-gray-50 text-xs">
                  <td colspan="3" class="px-3 py-2 text-right font-semibold text-gray-700">
                    Debe cuadrar con (total − descuento)
                  </td>
                  <td class="px-3 py-2 text-right font-bold">{{ fmt(esperado) }}</td>
                  <td></td>
                </tr>
                <tr :class="cuadra ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                  <td colspan="3" class="px-3 py-2 text-right text-sm font-bold">Diferencia</td>
                  <td class="px-3 py-2 text-right font-bold">{{ fmt(esperado - sumaLineas) }}</td>
                  <td></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- Cuentas y contabilizar -->
        <div class="bg-white rounded-xl border shadow-sm p-5">
          <div v-if="avisos.length" class="text-xs text-amber-700 space-y-0.5 mb-3">
            <p class="font-semibold">Faltan cuentas para contabilizar:</p>
            <ul class="list-disc list-inside">
              <li v-for="(a, i) in avisos" :key="i">{{ a }}</li>
            </ul>
            <p class="text-gray-500">Puedes ingresar a inventario igual y contabilizar después.</p>
          </div>
          <p v-else class="text-xs text-green-700 mb-3">Todas las cuentas están definidas.</p>

          <label class="flex items-start gap-2 text-sm text-gray-700">
            <input type="checkbox" v-model="form.contabilizar_ahora" class="mt-0.5 rounded border-gray-300"/>
            <span>
              Contabilizar ahora
              <span class="block text-xs text-gray-500">
                Genera el asiento (ingreso, IVA, IT, costo de venta e inventario).
              </span>
            </span>
          </label>
        </div>

        <div class="flex items-center justify-end gap-3">
          <Link :href="route('libro-ventas.index', periodo.id)"
            class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            Cancelar
          </Link>
          <button type="submit" :disabled="form.processing || !puedeGuardar"
            class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50">
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
  cuenta_iva_df_default: { type: Number, default: null },
  cliente_cxc:           { type: Object, default: null },
})

const round4 = (n) => Math.round(n * 10000) / 10000
const iva       = Number(props.libro.debito_fiscal ?? 0)
const descuento = Number(props.libro.descuento ?? 0)
const esperado  = round4(Number(props.libro.importe_total) - descuento)   // igual que el servicio

const form = useForm({
  almacen_id:         props.libro.almacen_id || '',
  forma_pago:         'contado',
  dias_credito:       0,
  cuenta_cobro_id:    props.libro.cuenta_cobro_id ?? null,
  cuenta_iva_df_id:   props.libro.cuenta_iva_df_id ?? props.cuenta_iva_df_default ?? null,
  contabilizar_ahora: false,
  lineas: [{ producto_id: '', cantidad: 1, precio_unitario: 0 }],
})

const fmt = (n) => Number(n ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (d) => (d ? String(d).substring(0, 10) : '')
const esCredito = computed(() => form.forma_pago === 'credito')

const productoMap = computed(() => Object.fromEntries(props.productos.map(p => [p.id, p])))
const totalLinea = (l) => round4((Number(l.cantidad) || 0) * (Number(l.precio_unitario) || 0))
const sumaLineas = computed(() => round4(form.lineas.reduce((s, l) => s + totalLinea(l), 0)))
const cuadra = computed(() => Math.abs(esperado - sumaLineas.value) <= 0.05)

// ── Verificación de cuentas (misma prioridad que ContabilizarVentaService) ──
const almacenSel = computed(() => props.almacenes.find(a => a.id === form.almacen_id))
const cuentaAlmacen = computed(() => almacenSel.value?.cuenta_inventario_id ?? almacenSel.value?.cuenta_id ?? null)

const avisos = computed(() => {
  const out = new Set()
  form.lineas.forEach(l => {
    const p = productoMap.value[l.producto_id]
    if (!p) return
    if (!p.cuenta_ingreso_id && !props.libro.cuenta_ingreso_id)
      out.add(`"${p.descripcion}": sin cuenta de ingreso`)
    if (p.controla_stock) {
      if (!p.cuenta_costo_venta_id) out.add(`"${p.descripcion}": sin cuenta de costo de venta`)
      if (!p.cuenta_activo_id && !cuentaAlmacen.value) out.add(`"${p.descripcion}": sin cuenta de inventario (ni en el almacén)`)
    }
  })
  if (iva > 0 && !form.cuenta_iva_df_id) out.add('Falta la cuenta de IVA Débito Fiscal')
  if (esCredito.value && !props.cliente_cxc) out.add('El cliente no tiene cuenta por cobrar')
  if (!esCredito.value && !form.cuenta_cobro_id) out.add('Falta la cuenta de cobro')
  return [...out]
})

const puedeGuardar = computed(() =>
  form.almacen_id && cuadra.value &&
  form.lineas.every(l => l.producto_id && l.cantidad > 0 && l.precio_unitario >= 0) &&
  (!form.contabilizar_ahora || avisos.value.length === 0)
)

const agregarLinea = () => form.lineas.push({ producto_id: '', cantidad: 1, precio_unitario: 0 })

// Al elegir producto, sugiere su precio de venta; si hay una sola línea, ajusta al total
const alElegirProducto = (l) => {
  const p = productoMap.value[l.producto_id]
  if (!p) return
  if (!Number(l.precio_unitario)) l.precio_unitario = Number(p.precio_venta) || 0
  if (form.lineas.length === 1) ajustarPrecio(l)
}

// Ajusta el precio unitario de la línea para que la suma cuadre con la factura
const ajustarPrecio = (l) => {
  const otras = form.lineas.filter(x => x !== l).reduce((s, x) => s + totalLinea(x), 0)
  const cant = Number(l.cantidad) || 1
  l.precio_unitario = round4((esperado - otras) / cant)
}

const guardar = () => {
  const dias = Number(form.dias_credito)
  form.dias_credito = esCredito.value && Number.isFinite(dias) && dias >= 0 ? Math.trunc(dias) : 0
  form.post(route('libro-ventas.ingresar-inventario', props.libro.id), { preserveScroll: true })
}
</script>