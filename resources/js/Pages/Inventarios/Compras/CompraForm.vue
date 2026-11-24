<script setup>
import { useForm } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
  compra: { type: Object, default: null },
  periodoId: { type: Number, required: true },
  proveedores: Array,
  almacenes: Array,
  productos: Array,
  cuentasPago: Array,
  cuentasIvaCf: Array,
})

/* ============================================================
   TASA DE IVA (Bolivia: 13%)
   ============================================================ */
const TASA_IVA = 0.13

const form = useForm({
  periodo_id: props.compra?.periodo_id ?? props.periodoId,
  proveedor_id: props.compra?.proveedor_id ?? '',
  almacen_id: props.compra?.almacen_id ?? '',
  nro_factura: props.compra?.nro_factura ?? '',
  cod_autorizacion: props.compra?.cod_autorizacion ?? '',
  fecha_factura: props.compra?.fecha_factura ?? '',
  fecha_recepcion: props.compra?.fecha_recepcion ?? new Date().toISOString().slice(0, 10),
  descuento: props.compra?.descuento ?? 0,
  forma_pago: props.compra?.forma_pago ?? 'contado',
  dias_credito: props.compra?.dias_credito ?? 0,
  cuenta_pago_id: props.compra?.cuenta_pago_id ?? '',
  cuenta_iva_cf_id: props.compra?.cuenta_iva_cf_id ?? '',
  observacion: props.compra?.observacion ?? '',
  detalles: props.compra?.detalles?.map(d => ({
    producto_id: d.producto_id,
    cantidad: Number(d.cantidad),
    // ⚠️ costo_unitario ahora representa el COSTO CON IVA INCLUIDO
    costo_unitario: Number(d.costo_unitario),
    descuento: Number(d.descuento),
  })) ?? [],
})

// Buscador de productos
const busqueda = ref('')
const productosFiltrados = computed(() => {
  if (!busqueda.value) return props.productos.slice(0, 20)
  const b = busqueda.value.toLowerCase()
  return props.productos.filter(p =>
    p.descripcion.toLowerCase().includes(b) || p.codigo.toLowerCase().includes(b)
  ).slice(0, 20)
})

const agregarLinea = (p) => {
  if (form.detalles.some(d => d.producto_id === p.id)) return
  form.detalles.push({
    producto_id: p.id,
    cantidad: 1,
    // El costo del producto ya se maneja como "con IVA incluido"
    costo_unitario: Number(p.precio_costo),
    descuento: 0,
  })
  busqueda.value = ''
}

const quitarLinea = (i) => form.detalles.splice(i, 1)
const nombreProducto = (id) => props.productos.find(p => p.id === id)?.descripcion ?? `#${id}`

/* ============================================================
   CÁLCULOS — IVA INCLUIDO EN EL TOTAL
   ------------------------------------------------------------
   Cada línea: cantidad * costo_unitario - descuento = TOTAL con IVA
   Base  = Total / (1 + TASA_IVA)
   IVA   = Total - Base
   ============================================================ */

// Total bruto por línea (lo que el usuario ingresa con IVA incluido)
const totalLinea = (d) =>
  Math.max((Number(d.cantidad) || 0) * (Number(d.costo_unitario) || 0) - (Number(d.descuento) || 0), 0)

// TOTAL del documento = suma de líneas − descuento global  (¡ya trae IVA dentro!)
const total = computed(() =>
  Math.max(
    form.detalles.reduce((s, d) => s + totalLinea(d), 0) - (Number(form.descuento) || 0),
    0
  )
)

// Extraer base imponible: total / (1 + tasa)
const neto = computed(() => +(total.value).toFixed(2))

// IVA incluido: total − base  (resta, no multiplicación)
const iva = computed(() => +(neto.value * TASA_IVA ).toFixed(2))

// Subtotal bruto (sin restar descuento global) — solo informativo
const subtotal = computed(() =>
  form.detalles.reduce((s, d) => s + totalLinea(d), 0)
)

// Proveedor → autocompletar crédito y cuenta CxP
const onProveedor = (prov) => {
  if (!prov) return
  if (prov.dias_credito > 0) {
    form.forma_pago = 'credito'
    form.dias_credito = prov.dias_credito
  }
  if (prov.cuenta_por_pagar_id && !form.cuenta_pago_id) {
    form.cuenta_pago_id = prov.cuenta_por_pagar_id
  }
}

// Opciones para SearchSelect
const proveedoresOpts = computed(() =>
  props.proveedores.map(p => ({
    id: p.id,
    razon_social: `${p.razon_social}${p.nit ? ' (' + p.nit + ')' : ''}`,
    nit: p.nit,
    dias_credito: p.dias_credito,
    cuenta_por_pagar_id: p.cuenta_por_pagar_id,
  }))
)
const almacenesOpts = computed(() =>
  props.almacenes.map(a => ({ id: a.id, nombre: a.nombre, codigo: a.codigo }))
)
const cuentasPagoOpts = computed(() =>
  props.cuentasPago.map(c => ({ id: c.id, nombre: c.nombre, codigo: c.codigo }))
)
const cuentasIvaCfOpts = computed(() =>
  props.cuentasIvaCf.map(c => ({ id: c.id, nombre: c.nombre, codigo: c.codigo }))
)

const submit = () => {
  // Enviamos también los totales calculados para que el backend los use/valide
  form.transform((data) => ({
    ...data,
    subtotal: neto.value, // base imponible
    iva: iva.value,
    total: total.value,
  }))
  if (props.compra) {
    form.post(route('inventario.compras.update', props.compra.id))
  } else {
    form.post(route('entrada.store', { periodoId: props.periodoId }))
  }
}

const fmt = (n) => Number(n).toFixed(2)
</script>

<template>
  <form @submit.prevent="submit" class="space-y-6">

    <!-- ─────────── SECCIÓN 1: DATOS GENERALES ─────────── -->
    <section class="bg-gray-50 rounded-lg border border-gray-200 p-4">
      <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
        📋 Datos de la compra
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Proveedor -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Proveedor <span class="text-red-500">*</span>
          </label>
          <SearchSelect
            v-model="form.proveedor_id"
            :options="proveedoresOpts"
            label-key="razon_social"
            placeholder="Buscar por razón social o NIT..."
            :error="form.errors.proveedor_id"
            @change="onProveedor"
          />
        </div>

        <!-- Almacén -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Almacén destino <span class="text-red-500">*</span>
          </label>
          <SearchSelect
            v-model="form.almacen_id"
            :options="almacenesOpts"
            label-key="nombre"
            secondary-key="codigo"
            placeholder="Buscar almacén..."
            :error="form.errors.almacen_id"
          />
        </div>

        <!-- Fecha recepción -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Fecha recepción <span class="text-red-500">*</span>
          </label>
          <input
            v-model="form.fecha_recepcion"
            type="date"
            required
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Nro factura -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nro. Factura</label>
          <input
            v-model="form.nro_factura"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Fecha factura -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Fecha factura</label>
          <input
            v-model="form.fecha_factura"
            type="date"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Cód autorización -->
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1">Código de autorización</label>
          <input
            v-model="form.cod_autorizacion"
            type="text"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Observación -->
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-gray-700 mb-1">Observación</label>
          <input
            v-model="form.observacion"
            type="text"
            placeholder="Opcional..."
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>
      </div>
    </section>

    <!-- ─────────── SECCIÓN 2: PAGO Y CONTABILIDAD ─────────── -->
    <section class="bg-gray-50 rounded-lg border border-gray-200 p-4">
      <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
        💰 Pago y contabilidad
      </h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Forma de pago -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Forma de pago</label>
          <select
            v-model="form.forma_pago"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          >
            <option value="contado">Contado</option>
            <option value="credito">Crédito</option>
            <option value="mixto">Mixto</option>
          </select>
        </div>

        <!-- Días crédito -->
        <div v-if="form.forma_pago !== 'contado'">
          <label class="block text-sm font-medium text-gray-700 mb-1">Días de crédito</label>
          <input
            v-model.number="form.dias_credito"
            type="number"
            min="0"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
          />
        </div>

        <!-- Cuenta de pago -->
        <div :class="form.forma_pago === 'contado' ? 'md:col-span-2' : ''">
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Cuenta de pago
            <span class="text-xs text-gray-400">(Caja / Banco / CxP)</span>
          </label>
          <SearchSelect
            v-model="form.cuenta_pago_id"
            :options="cuentasPagoOpts"
            label-key="nombre"
            secondary-key="codigo"
            placeholder="Buscar cuenta de pago..."
            :error="form.errors.cuenta_pago_id"
          />
        </div>

        <!-- Cuenta IVA CF -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">
            Cuenta IVA CF
            <span class="text-xs text-gray-400">(Crédito Fiscal)</span>
          </label>
          <SearchSelect
            v-model="form.cuenta_iva_cf_id"
            :options="cuentasIvaCfOpts"
            label-key="nombre"
            secondary-key="codigo"
            placeholder="Buscar cuenta IVA..."
            :error="form.errors.cuenta_iva_cf_id"
          />
        </div>
      </div>
    </section>

    <!-- ─────────── SECCIÓN 3: DETALLE DE PRODUCTOS ─────────── -->
    <section class="bg-gray-50 rounded-lg border border-gray-200 p-4">
      <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">
        📦 Detalle de productos
        <span class="text-xs font-normal text-gray-500 normal-case tracking-normal ml-2">
          (costos con IVA incluido)
        </span>
      </h3>

      <!-- Buscador de productos -->
      <div class="mb-4 relative">
        <label class="block text-sm font-medium text-gray-700 mb-1">Agregar producto</label>
        <input
          v-model="busqueda"
          type="text"
          placeholder="Escriba código o descripción para buscar..."
          class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
        />
        <div
          v-if="busqueda"
          class="absolute z-30 mt-1 w-full border rounded-md divide-y max-h-72 overflow-y-auto bg-white shadow-2xl"
        >
          <button
            v-for="p in productosFiltrados"
            :key="p.id"
            type="button"
            :disabled="form.detalles.some(d => d.producto_id === p.id)"
            @click="agregarLinea(p)"
            class="w-full text-left px-4 py-2 text-sm hover:bg-indigo-50 disabled:opacity-40 disabled:cursor-not-allowed flex justify-between items-center"
          >
            <span class="truncate">
              <span class="font-mono text-xs text-gray-500 mr-2">{{ p.codigo }}</span>
              {{ p.descripcion }}
            </span>
            <span class="text-gray-500 font-mono text-xs shrink-0">
              Bs. {{ Number(p.precio_costo).toFixed(2) }}
            </span>
          </button>
          <div v-if="!productosFiltrados.length" class="px-4 py-3 text-sm text-gray-500 text-center">
            Sin resultados
          </div>
        </div>
      </div>

      <!-- Tabla -->
      <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 uppercase">Producto</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase w-28">Cantidad</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase w-32">
                Costo Unit. <span class="text-gray-400 normal-case">(IVA incl.)</span>
              </th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase w-28">Descuento</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 uppercase w-32">Total</th>
              <th class="px-4 py-2 w-10"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(d, i) in form.detalles" :key="i" class="hover:bg-gray-50">
              <td class="px-4 py-2 text-sm text-gray-900">{{ nombreProducto(d.producto_id) }}</td>
              <td class="px-4 py-2">
                <input
                  v-model.number="d.cantidad"
                  type="number" step="0.0001" min="0.0001" required
                  class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </td>
              <td class="px-4 py-2">
                <input
                  v-model.number="d.costo_unitario"
                  type="number" step="0.0001" min="0" required
                  class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </td>
              <td class="px-4 py-2">
                <input
                  v-model.number="d.descuento"
                  type="number" step="0.0001" min="0"
                  class="w-full text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
              </td>
              <td class="px-4 py-2 text-sm text-right font-mono font-medium text-gray-900">
                {{ fmt(totalLinea(d)) }}
              </td>
              <td class="px-4 py-2 text-center">
                <button
                  type="button"
                  @click="quitarLinea(i)"
                  class="text-red-500 hover:text-red-700 text-xl font-bold leading-none"
                  title="Eliminar"
                >×</button>
              </td>
            </tr>
            <tr v-if="!form.detalles.length">
              <td colspan="6" class="px-4 py-8 text-center text-sm text-gray-500">
                Agregue al menos un producto.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="form.errors.detalles" class="text-sm text-red-600 mt-2">{{ form.errors.detalles }}</div>
    </section>

    <!-- ─────────── SECCIÓN 4: TOTALES (IVA INCLUIDO) ─────────── -->
    <section class="flex justify-end">
      <div class="w-full md:w-96 bg-white rounded-lg border border-gray-200 p-4 space-y-2 text-sm">
        <div class="flex justify-between">
          <span class="text-gray-600">Subtotal (bruto con IVA):</span>
          <span class="font-mono text-gray-900">Bs. {{ fmt(subtotal) }}</span>
        </div>
        <div class="flex justify-between items-center">
          <span class="text-gray-600">Descuento global:</span>
          <input
            v-model.number="form.descuento"
            type="number" step="0.01" min="0"
            class="w-28 text-right rounded-md border-gray-300 shadow-sm sm:text-sm"
          />
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">Sub total (sin IVA):</span>
          <span class="font-mono text-gray-900">Bs. {{ fmt(neto) }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-gray-600">IVA CF (13% incluido):</span>
          <span class="font-mono text-gray-900">Bs. {{ fmt(iva) }}</span>
        </div>
        <div class="flex justify-between border-t pt-2 text-base font-bold">
          <span class="text-gray-800">TOTAL (IVA incl.):</span>
          <span class="font-mono text-indigo-700">Bs. {{ fmt(total) }}</span>
        </div>
        <p class="text-xs text-gray-500 pt-1">
          El IVA se extrae del total: Base = Total / 1.13 · IVA = Total − Base
        </p>
      </div>
    </section>

    <!-- ─────────── BOTONES ─────────── -->
    <div class="flex justify-end gap-3 pt-2">
      <button
        type="submit"
        :disabled="form.processing || !form.detalles.length"
        class="px-6 py-2 bg-indigo-600 rounded-md text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        {{ form.processing ? 'Guardando...' : (compra ? 'Actualizar' : 'Guardar borrador') }}
      </button>
    </div>
  </form>
</template>
