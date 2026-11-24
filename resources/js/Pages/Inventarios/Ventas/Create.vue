<template>
  <AppLayoutAdmin>
    <Head title="Nueva venta" />

    <template #header>
      <div class="flex items-center gap-3">
        <Link :href="route('inventario.ventas.index', periodo.id)"
          class="text-gray-400 hover:text-gray-600 text-lg leading-none">←</Link>
        <div>
          <h2 class="text-xl font-semibold text-gray-800">Nueva venta v</h2>
          <p class="text-xs text-gray-500">{{ periodo.nombre }}</p>
        </div>
      </div>
    </template>

    <div class="py-6 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="mensajesError.length"
        class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        <p v-for="(m, i) in mensajesError" :key="i" class="whitespace-pre-line">{{ m }}</p>
      </div>

      <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- ═════════ Izquierda ═════════ -->
        <div class="lg:col-span-2 space-y-4">

          <!-- Datos y cliente -->
          <div class="bg-white rounded-xl border shadow-sm p-5 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Almacén *</label>
                <select v-model="form.almacen_id" required @change="refrescarStocks"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                  <option value="">Seleccionar...</option>
                  <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Fecha *</label>
                <input v-model="form.fecha" type="date" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm"/>
              </div>
            </div>

            <div>
              <div class="flex items-center justify-between mb-1">
                <label class="text-xs font-medium text-gray-600">Cliente</label>
                <button type="button" @click="modalCliente = true"
                  class="text-xs text-blue-600 hover:underline">+ Nuevo cliente</button>
              </div>
              <SearchSelect
                v-model="form.proveedor_cliente_id"
                :options="opcionesClientes"
                label-key="razon_social"
                secondary-key="nit"
                placeholder="Buscar por nombre o NIT (opcional)..."
                @change="onClienteChange"
              />
            </div>

            <div class="grid grid-cols-3 gap-3">
              <div class="col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nombre en la venta *</label>
                <input v-model="form.razon_social_cliente" type="text" required maxlength="200"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm"/>
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NIT / CI</label>
                <input v-model="form.nit_cliente" type="text"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm"/>
              </div>
            </div>
          </div>

          <!-- Productos -->
          <div class="bg-white rounded-xl border shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
              <h3 class="text-sm font-semibold text-gray-700">Productos</h3>
              <button type="button" @click="agregarLinea"
                class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg">
                + Agregar línea
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead>
                  <tr class="bg-gray-50 border-b border-t text-xs text-gray-600">
                    <th class="px-3 py-2 text-left font-semibold">Producto</th>
                    <th class="px-3 py-2 text-right font-semibold w-20">Cant.</th>
                    <th class="px-3 py-2 text-right font-semibold w-28">Precio (c/IVA)</th>
                    <th class="px-3 py-2 text-right font-semibold w-20">Desc.</th>
                    <th class="px-3 py-2 text-right font-semibold w-24">Total</th>
                    <th class="px-3 py-2 text-center font-semibold w-16">Stock</th>
                    <th class="w-6"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(l, i) in form.detalles" :key="i"
                    class="border-b border-gray-100" :class="sinStock(l) ? 'bg-red-50' : ''">
                    <td class="px-3 py-2">
                      <select v-model="l.producto_id" required @change="onProductoChange(l)"
                        class="w-full border border-gray-200 rounded px-2 py-1 text-xs">
                        <option value="">Seleccionar...</option>
                        <option v-for="p in productos" :key="p.id" :value="p.id">{{ p.codigo }} — {{ p.descripcion }}</option>
                      </select>
                      <p v-if="productoMap[l.producto_id]" class="text-[11px] text-gray-400 mt-0.5">
                        {{ productoMap[l.producto_id].unidad_medida?.codigo }}
                        · {{ productoMap[l.producto_id].controla_stock ? productoMap[l.producto_id].metodo_costeo : 'servicio' }}
                        <span v-if="!productoMap[l.producto_id].aplica_iva"> · sin IVA</span>
                      </p>
                    </td>
                    <td class="px-3 py-2">
                      <input v-model.number="l.cantidad" type="number" step="0.0001" min="0.0001" required
                        class="w-full border rounded px-2 py-1 text-xs text-right"
                        :class="sinStock(l) ? 'border-red-400' : 'border-gray-200'"/>
                    </td>
                    <td class="px-3 py-2">
                      <div class="flex items-center gap-1">
                        <input v-model.number="l.precio_unitario" type="number" step="0.0001" min="0" required
                          class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right"/>
                        <button v-if="l._precios.length > 1" type="button" @click="ciclarPrecio(l)"
                          class="px-1.5 py-1 bg-blue-100 hover:bg-blue-200 text-blue-700 text-xs rounded">
                          P{{ l._lista }}
                        </button>
                      </div>
                    </td>
                    <td class="px-3 py-2">
                      <input v-model.number="l.descuento" type="number" step="0.01" min="0"
                        class="w-full border border-gray-200 rounded px-2 py-1 text-xs text-right"/>
                    </td>
                    <td class="px-3 py-2 text-right text-xs font-semibold">{{ fmt(bruto(l)) }}</td>
                    <td class="px-3 py-2 text-center">
                      <span v-if="l._stock !== null && productoMap[l.producto_id]?.controla_stock"
                        class="text-xs font-medium px-1.5 py-0.5 rounded-full"
                        :class="sinStock(l) || l._stock <= 0 ? 'bg-red-100 text-red-700'
                              : l._stock < 5 ? 'bg-amber-100 text-amber-700'
                              : 'bg-green-100 text-green-700'">
                        {{ fmtCant(l._stock) }}
                      </span>
                    </td>
                    <td class="px-2 py-2">
                      <button v-if="form.detalles.length > 1" type="button" @click="form.detalles.splice(i, 1)"
                        class="text-gray-300 hover:text-red-500">✕</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p v-if="hayErroresStock" class="mt-3 text-xs text-red-700">
              Hay productos con stock insuficiente en este almacén.
            </p>
          </div>
        </div>

        <!-- ═════════ Derecha ═════════ -->
        <div class="space-y-4">

          <div class="bg-blue-600 rounded-xl p-4 text-white">
            <div class="text-xs text-blue-200">Total a cobrar</div>
            <div class="text-3xl font-bold">{{ fmt(total) }}</div>
            <div v-if="iva > 0" class="text-xs text-blue-200 mt-1">IVA incluido (13%): {{ fmt(iva) }}</div>
          </div>

          <!-- Pago -->
          <div class="bg-white rounded-xl border shadow-sm p-4 space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Forma de pago</label>
              <select v-model="form.forma_pago"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <option value="contado">Contado</option>
                <option value="credito">Crédito</option>
                <option value="mixto">Mixto (varios medios)</option>
              </select>
            </div>

            <!-- Crédito puro -->
            <div v-if="form.forma_pago === 'credito'" class="text-xs text-gray-600">
              Todo el total queda en cuenta corriente del cliente.
            </div>

            <!-- Medios de pago -->
            <div v-else class="space-y-2">
              <div v-for="(p, i) in form.pagos" :key="i" class="grid grid-cols-12 gap-2 items-end">
                <div :class="esMixto ? 'col-span-5' : 'col-span-6'">
                  <label class="block text-[11px] text-gray-500 mb-0.5">Medio</label>
                  <select v-model="p.medio"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs">
                    <option v-for="m in medios" :key="m.value" :value="m.value">{{ m.label }}</option>
                  </select>
                </div>
                <div :class="esMixto ? 'col-span-4' : 'col-span-6'">
                  <label class="block text-[11px] text-gray-500 mb-0.5">{{ p.medio === 'efectivo' ? 'Recibido' : 'Monto' }}</label>
                  <input v-model.number="p.monto" type="number" step="0.01" min="0"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1.5 text-xs text-right"/>
                </div>
                <div v-if="esMixto" class="col-span-3 text-right">
                  <button v-if="form.pagos.length > 1" type="button" @click="form.pagos.splice(i, 1)"
                    class="text-xs text-red-400 hover:text-red-600">Quitar</button>
                </div>
                <div v-if="esMixto && p.medio !== 'credito'" class="col-span-12">
                  <select v-model="p.cuenta_id"
                    class="w-full border border-gray-200 rounded-lg px-2 py-1 text-[11px] text-gray-600">
                    <option :value="null">Cuenta: la de cobro general</option>
                    <option v-for="c in cuentas_cobro" :key="c.id" :value="c.id">{{ c.codigo }} {{ c.nombre }}</option>
                  </select>
                </div>
              </div>

              <button v-if="esMixto" type="button" @click="agregarPago"
                class="text-xs text-blue-600 hover:underline">+ Agregar medio</button>
            </div>

            <!-- Días de crédito -->
            <div v-if="hayCredito">
              <label class="block text-xs font-medium text-gray-600 mb-1">Días de crédito</label>
              <input v-model.number="form.dias_credito" type="number" min="0"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm"/>
              <p v-if="!clienteIdentificado" class="text-xs text-red-600 mt-1">
                Para vender a crédito elige un cliente o escribe su NIT/CI.
              </p>
            </div>

            <!-- Resumen -->
            <div class="pt-2 border-t text-xs space-y-1">
              <div class="flex justify-between">
                <span class="text-gray-500">Cubierto:</span>
                <span :class="cubierto ? 'text-green-700 font-semibold' : 'text-red-600 font-semibold'">{{ fmt(sumaPagos) }}</span>
              </div>
              <div v-if="!cubierto" class="flex justify-between">
                <span class="text-gray-500">Falta:</span>
                <span class="text-red-600 font-semibold">{{ fmt(total - sumaPagos) }}</span>
              </div>
              <div v-if="cambio > 0" class="flex justify-between">
                <span class="text-gray-500">Cambio:</span>
                <span class="text-green-700 font-semibold">{{ fmt(cambio) }}</span>
              </div>
              <p v-if="excesoSinEfectivo" class="text-red-600">El monto excede el total y no hay efectivo para dar cambio.</p>
            </div>
          </div>

          <!-- Opciones -->
          <div class="bg-white rounded-xl border shadow-sm p-4 space-y-3">
            <label class="flex items-center gap-2 text-sm text-gray-700">
              <input type="checkbox" v-model="form.con_factura" class="rounded border-gray-300"/>
              Con factura
            </label>

            <div v-if="hayCobroInmediato">
              <label class="block text-xs font-medium text-gray-600 mb-1">Cuenta de cobro (Caja/Banco)</label>
              <select v-model="form.cuenta_cobro_id"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm">
                <option :value="null">Sin asignar</option>
                <option v-for="c in cuentas_cobro" :key="c.id" :value="c.id">{{ c.codigo }} {{ c.nombre }}</option>
              </select>
            </div>

            <div class="pt-2 border-t">
              <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" v-model="form.contabilizar_ahora" class="mt-0.5 rounded border-gray-300"/>
                <span>
                  Contabilizar al guardar
                  <span class="block text-xs text-gray-500">
                    Si no, queda pendiente y se contabiliza desde el listado de ventas.
                  </span>
                </span>
              </label>

              <ul v-if="avisos.length" class="mt-2 text-xs text-amber-700 list-disc list-inside space-y-0.5">
                <li v-for="(a, i) in avisos" :key="i">{{ a }}</li>
              </ul>
            </div>
          </div>

          <button type="submit" :disabled="form.processing || !puedeGuardar"
            class="w-full py-3 bg-green-600 text-white text-sm font-semibold rounded-xl hover:bg-green-700 disabled:opacity-50">
            {{ form.processing ? 'Procesando...' : (form.contabilizar_ahora ? 'Confirmar y contabilizar' : 'Confirmar venta') }}
          </button>
        </div>
      </form>
    </div>

    <!-- Cliente rápido -->
    <div v-if="modalCliente" class="fixed inset-0 z-[100000] bg-black/50 flex items-center justify-center p-4">
      <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5 space-y-3">
        <div class="flex justify-between items-center border-b pb-2">
          <h3 class="font-bold text-gray-800">Nuevo cliente</h3>
          <button type="button" @click="modalCliente = false" class="text-gray-400 hover:text-red-500 text-xl">&times;</button>
        </div>
        <div>
          <label class="text-xs font-semibold text-gray-600">Nombre / Razón social *</label>
          <input v-model="nuevoCliente.razon_social" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1"/>
        </div>
        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="text-xs font-semibold text-gray-600">NIT</label>
            <input v-model="nuevoCliente.nit" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1"/>
          </div>
          <div>
            <label class="text-xs font-semibold text-gray-600">CI</label>
            <input v-model="nuevoCliente.ci" type="text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1"/>
          </div>
        </div>
        <div class="flex justify-end gap-2 pt-1">
          <button type="button" @click="modalCliente = false" class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancelar</button>
          <button type="button" @click="guardarCliente" class="px-4 py-1.5 bg-blue-600 text-white text-sm font-semibold rounded hover:bg-blue-700">Guardar y usar</button>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
  periodo:               { type: Object, required: true },
  almacenes:             { type: Array, default: () => [] },
  clientes:              { type: Array, default: () => [] },
  productos:             { type: Array, default: () => [] },
  cuentas_cobro:         { type: Array, default: () => [] },
  cuenta_iva_df_default: { type: Object, default: null },
  cuenta_cobro_default:  { type: Object, default: null },
  fecha:                 { type: String, default: '' },
})

const round2 = (n) => Math.round(n * 100) / 100
const round4 = (n) => Math.round(n * 10000) / 10000

const productoMap = Object.fromEntries(props.productos.map(p => [p.id, p]))
const listaClientes = ref([...props.clientes])
const clienteMap = computed(() => Object.fromEntries(listaClientes.value.map(c => [c.id, c])))
const opcionesClientes = computed(() =>
  listaClientes.value.map(c => ({ id: c.id, razon_social: c.razon_social, nit: c.nit || c.ci }))
)

const nuevaLinea = () => ({
  producto_id: '', cantidad: 1, precio_unitario: 0, descuento: 0,
  _stock: null, _precios: [], _lista: 1,
})

const form = useForm({
  periodo_id:           props.periodo.id,
  almacen_id:           '',
  fecha:                props.fecha || new Date().toISOString().split('T')[0],
  forma_pago:           'contado',
  dias_credito:         0,
  proveedor_cliente_id: null,
  razon_social_cliente: 'SIN NOMBRE',
  nit_cliente:          '',
  ci_cliente:           '',
  complemento_cliente:  '',
  con_factura:          true,
  cuenta_cobro_id:      props.cuenta_cobro_default?.id ?? null,
  cuenta_iva_df_id:     props.cuenta_iva_df_default?.id ?? null,
  contabilizar_ahora:   false,
  detalles:             [nuevaLinea()],
  pagos:                [{ medio: 'efectivo', monto: 0, cuenta_id: null }],
})

// ── Cálculos (mismo criterio que el backend: el precio incluye IVA, 13%) ──
const bruto = (l) => round4((Number(l.cantidad) || 0) * (Number(l.precio_unitario) || 0) - (Number(l.descuento) || 0))

const total = computed(() => round2(form.detalles.reduce((s, l) => s + bruto(l), 0)))
const iva = computed(() => round2(form.detalles.reduce((s, l) =>
  s + (productoMap[l.producto_id]?.aplica_iva ? bruto(l) * 0.13 : 0), 0)))

// ── Pagos ──
const esMixto = computed(() => form.forma_pago === 'mixto')
const medios = computed(() => {
  const base = [
    { value: 'efectivo', label: 'Efectivo' },
    { value: 'tarjeta_debito', label: 'T. Débito' },
    { value: 'tarjeta_credito', label: 'T. Crédito' },
    { value: 'qr', label: 'QR' },
    { value: 'transferencia', label: 'Transferencia' },
  ]
  return esMixto.value ? [...base, { value: 'credito', label: 'A crédito' }] : base
})

const pagosValidos = computed(() => form.pagos.filter(p => Number(p.monto) > 0))
const sumaPagos = computed(() => round2(pagosValidos.value.reduce((s, p) => s + Number(p.monto), 0)))
const hayCredito = computed(() => pagosValidos.value.some(p => p.medio === 'credito'))
const hayCobroInmediato = computed(() => pagosValidos.value.some(p => p.medio !== 'credito'))
const hayEfectivo = computed(() => pagosValidos.value.some(p => p.medio === 'efectivo'))

const cubierto = computed(() => total.value > 0 && sumaPagos.value >= total.value - 0.005)
const exceso = computed(() => round2(Math.max(0, sumaPagos.value - total.value)))
const cambio = computed(() => (hayEfectivo.value ? exceso.value : 0))
const excesoSinEfectivo = computed(() => exceso.value > 0 && !hayEfectivo.value)

const pagosPayload = () => {
  let resto = cambio.value
  return pagosValidos.value.map(p => {
    let c = 0
    if (p.medio === 'efectivo' && resto > 0) {
      c = Math.min(resto, Number(p.monto))
      resto = round2(resto - c)
    }
    return { medio: p.medio, monto: round2(Number(p.monto)), cambio: c, cuenta_id: p.cuenta_id ?? null }
  })
}

const agregarPago = () => form.pagos.push({ medio: 'efectivo', monto: 0, cuenta_id: null })

watch(() => form.forma_pago, (v) => {
  form.pagos = [{ medio: v === 'credito' ? 'credito' : 'efectivo', monto: total.value, cuenta_id: null }]
  if (v === 'mixto') form.pagos[0].monto = 0
  if (v !== 'credito' && v !== 'mixto') form.dias_credito = 0
})

// En contado y crédito el monto sigue al total
watch(total, (t) => {
  if (form.forma_pago !== 'mixto' && form.pagos[0]) form.pagos[0].monto = t
})

// ── Cliente ──
const clienteSel = computed(() => clienteMap.value[form.proveedor_cliente_id] ?? null)
const clienteIdentificado = computed(() =>
  !!form.proveedor_cliente_id || (!!form.nit_cliente && form.nit_cliente !== '0'))

const onClienteChange = (opt) => {
  const c = opt ? clienteMap.value[opt.id] : null
  if (!c) return
  form.razon_social_cliente = c.razon_social
  form.nit_cliente          = c.nit ?? ''
  form.ci_cliente           = c.ci ?? ''
  form.complemento_cliente  = c.complemento ?? ''
  if (c.dias_credito > 0) form.dias_credito = c.dias_credito
}

const modalCliente = ref(false)
const nuevoCliente = ref({ razon_social: '', nit: '', ci: '' })

const guardarCliente = () => {
  if (!nuevoCliente.value.razon_social) return alert('El nombre es obligatorio')
  window.axios.post(route('inventario.clientes.store-express'), nuevoCliente.value)
    .then(res => {
      const c = res.data.cliente
      listaClientes.value.push(c)
      form.proveedor_cliente_id = c.id
      onClienteChange({ id: c.id })
      modalCliente.value = false
      nuevoCliente.value = { razon_social: '', nit: '', ci: '' }
    })
    .catch(err => alert(err.response?.data?.message || 'Error al crear cliente'))
}

// ── Líneas, precios y stock ──
const agregarLinea = () => form.detalles.push(nuevaLinea())

const onProductoChange = async (l) => {
  const p = productoMap[l.producto_id]
  if (!p) return

  const falta = !p.cuenta_ingreso_id
    || (p.controla_stock && (!p.cuenta_activo_id || !p.cuenta_costo_venta_id))
  if (falta) {
    alert(`"${p.descripcion}" no tiene sus cuentas contables configuradas y no puede venderse.`)
    l.producto_id = ''
    return
  }

  l._precios = [p.precio_venta, p.precio_venta_2, p.precio_venta_3].filter(x => Number(x) > 0)
  l._lista = 1
  l.precio_unitario = Number(p.precio_venta) || 0
  l._stock = null
  await cargarStock(l)
}

const ciclarPrecio = (l) => {
  l._lista = (l._lista % l._precios.length) + 1
  l.precio_unitario = Number(l._precios[l._lista - 1])
}

const cargarStock = async (l) => {
  if (!l.producto_id || !form.almacen_id || !productoMap[l.producto_id]?.controla_stock) return
  try {
    const res = await fetch(route('movimientos.stock-producto') +
      `?producto_id=${l.producto_id}&almacen_id=${form.almacen_id}`)
    l._stock = (await res.json()).cantidad
  } catch { l._stock = null }
}

const refrescarStocks = async () => {
  for (const l of form.detalles) await cargarStock(l)
}

// Suma lo pedido del mismo producto en varias líneas
const pedidoDe = (id) => form.detalles
  .filter(x => x.producto_id === id).reduce((s, x) => s + (Number(x.cantidad) || 0), 0)
const sinStock = (l) =>
  !!productoMap[l.producto_id]?.controla_stock && l._stock !== null && pedidoDe(l.producto_id) > l._stock
const hayErroresStock = computed(() => form.detalles.some(sinStock))

// ── Avisos para contabilizar (misma prioridad que ContabilizarVentaService) ──
const avisos = computed(() => {
  const out = []
  if (iva.value > 0 && !form.cuenta_iva_df_id) out.push('Falta la cuenta de IVA Débito Fiscal')
  if (hayCobroInmediato.value) {
    const sinCuenta = pagosValidos.value.some(p => p.medio !== 'credito' && !(p.cuenta_id ?? form.cuenta_cobro_id))
    if (sinCuenta) out.push('Falta la cuenta de cobro')
  }
  if (hayCredito.value && !clienteSel.value?.cuenta_por_cobrar_id)
    out.push('El cliente no tiene cuenta por cobrar asignada')
  return out
})

const puedeGuardar = computed(() =>
  !!form.almacen_id &&
  total.value > 0 &&
  form.detalles.every(l => l.producto_id && Number(l.cantidad) > 0 && Number(l.precio_unitario) >= 0) &&
  !hayErroresStock.value &&
  cubierto.value &&
  !excesoSinEfectivo.value &&
  (!hayCredito.value || clienteIdentificado.value) &&
  (!form.contabilizar_ahora || avisos.value.length === 0)
)

// ── Envío ──
const mensajesError = computed(() => [...new Set(Object.values(form.errors))])

const submit = () => {
  if (!puedeGuardar.value) return
  form
    .transform((data) => ({
      ...data,
      dias_credito: hayCredito.value ? Math.max(0, Math.trunc(Number(data.dias_credito) || 0)) : 0,
      detalles: data.detalles.map(l => ({
        producto_id: l.producto_id,
        cantidad: l.cantidad,
        precio_unitario: l.precio_unitario,
        descuento: Number(l.descuento) || 0,
      })),
      pagos: pagosPayload(),
    }))
    .post(route('inventario.ventas.store', props.periodo.id))
}

const fmt = (v) => new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' }).format(v ?? 0)
const fmtCant = (v) => new Intl.NumberFormat('es-BO', { maximumFractionDigits: 4 }).format(v ?? 0)
</script>