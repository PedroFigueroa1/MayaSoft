<script setup>
import { computed } from 'vue'
import { Link, useForm, usePage } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({ compra: Object })

const page = usePage()
const flash = computed(() => page.props.flash ?? {})
const errors = computed(() => page.props.errors ?? {})

const confirmar = useForm({})
const contabilizar = useForm({})

const estadoClases = {
  borrador: 'bg-gray-100 text-gray-800',
  confirmada: 'bg-blue-100 text-blue-800',
  contabilizada: 'bg-green-100 text-green-800',
  anulada: 'bg-red-100 text-red-800',
}

// --- Helpers de formato ---

// Convierte "2026-10-19T00:00:00.000000Z" -> "2026-10-19"
const formatFecha = (valor) => {
  if (!valor) return ''
  const s = String(valor)
  // Si ya viene como YYYY-MM-DD puro, devolver tal cual
  if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s
  // Cortar antes de la T (parte de fecha, sin hora ni zona)
  const parte = s.split('T')[0]
  // Revalidar
  return /^\d{4}-\d{2}-\d{2}$/.test(parte) ? parte : s
}

// Formato dd/mm/yyyy opcional (por si lo prefieres)
const formatFechaDMY = (valor) => {
  const iso = formatFecha(valor)
  if (!/^\d{4}-\d{2}-\d{2}$/.test(iso)) return iso
  const [y, m, d] = iso.split('-')
  return `${d}/${m}/${y}`
}

// Formato numérico monetario (2 decimales)
const money = (n, dec = 2) => Number(n ?? 0).toFixed(dec)
</script>

<template>
  <AppLayoutAdmin :title="'Compra ' + compra.nro_compra">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800">Compra {{ compra.nro_compra }}</h2>
        <div class="flex gap-2 items-center">
          <span class="px-3 py-1 rounded-full text-sm font-semibold" :class="estadoClases[compra.estado]">
            {{ compra.estado }}
          </span>
          <Link
            v-if="compra.estado === 'borrador'"
            :href="route('inventario.compras.edit', compra.id)"
            class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm hover:bg-yellow-600"
          >
            Editar
          </Link>
          <button
            v-if="compra.estado === 'borrador'"
            @click="confirmar.post(route('inventario.compras.confirmar', compra.id))"
            :disabled="confirmar.processing"
            class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 disabled:opacity-50"
          >
            Confirmar (ingresar stock)
          </button>
          <button
            v-if="compra.estado === 'confirmada'"
            @click="contabilizar.post(route('inventario.compras.contabilizar', compra.id))"
            :disabled="contabilizar.processing"
            class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 disabled:opacity-50"
          >
            Contabilizar
          </button>
          <Link
            :href="route('inventario.compras.index', { periodoId: compra.periodo_id })"
            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300"
          >
            Volver
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <!-- Flash messages -->
        <div v-if="flash?.success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
          {{ flash.success }}
        </div>
        <div v-if="flash?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ flash.error }}
        </div>

        <!-- Errores de validación / excepción desde el backend -->
        <div v-if="errors?.error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
          {{ errors.error }}
        </div>
        <div
          v-if="errors && Object.keys(errors).filter(k => k !== 'error').length"
          class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded"
        >
          <ul class="list-disc pl-5">
            <li v-for="(msg, key) in errors" :key="key" v-show="key !== 'error'">{{ msg }}</li>
          </ul>
        </div>

        <!-- Cabecera -->
        <div class="bg-white shadow-sm sm:rounded-lg p-6 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
          <div>
            <div class="text-xs text-gray-500 uppercase">Proveedor</div>
            <div class="font-medium">{{ compra.proveedor?.razon_social }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase">Almacén</div>
            <div class="font-medium">{{ compra.almacen?.nombre }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase">Recepción</div>
            <div class="font-medium">{{ formatFechaDMY(compra.fecha_recepcion) }}</div>
          </div>
          <div>
            <div class="text-xs text-gray-500 uppercase">Pago</div>
            <div class="font-medium capitalize">
              {{ compra.forma_pago }}{{ compra.dias_credito ? ` (${compra.dias_credito} días)` : '' }}
            </div>
          </div>
          <div v-if="compra.nro_factura">
            <div class="text-xs text-gray-500 uppercase">Factura</div>
            <div class="font-medium">
              {{ compra.nro_factura }}
              <span class="text-gray-400 text-xs">de {{ formatFechaDMY(compra.fecha_factura) }}</span>
            </div>
          </div>
          <div v-if="compra.observacion" class="col-span-2">
            <div class="text-xs text-gray-500 uppercase">Observación</div>
            <div>{{ compra.observacion }}</div>
          </div>
        </div>

        <!-- Detalle -->
        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Costo Unit.</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Descuento</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Subtotal</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="d in compra.detalles" :key="d.id">
                <td class="px-6 py-3 text-sm">
                  {{ d.producto?.codigo }} — {{ d.producto?.descripcion }}
                </td>
                <td class="px-6 py-3 text-sm text-right font-mono">
                  {{ money(d.cantidad) }} {{ d.producto?.unidad_medida?.nombre }}
                </td>
                <td class="px-6 py-3 text-sm text-right font-mono">{{ money(d.costo_unitario, 4) }}</td>
                <td class="px-6 py-3 text-sm text-right font-mono">{{ money(d.descuento) }}</td>
                <td class="px-6 py-3 text-sm text-right font-mono font-medium">{{ money(d.subtotal) }}</td>
              </tr>
            </tbody>
            <tfoot class="bg-gray-50">
              <tr>
                <td colspan="4" class="px-6 py-2 text-right text-sm text-gray-500">Subtotal</td>
                <td class="px-6 py-2 text-right text-sm font-mono">{{ money(compra.subtotal) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-6 py-2 text-right text-sm text-gray-500">Descuento</td>
                <td class="px-6 py-2 text-right text-sm font-mono">-{{ money(compra.descuento) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-6 py-2 text-right text-sm text-gray-500">IVA CF (13%)</td>
                <td class="px-6 py-2 text-right text-sm font-mono">{{ money(compra.iva_cf) }}</td>
              </tr>
              <tr>
                <td colspan="4" class="px-6 py-3 text-right text-base font-bold">TOTAL</td>
                <td class="px-6 py-3 text-right text-base font-mono font-bold text-indigo-700">
                  Bs. {{ money(compra.total) }}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>

        <!-- Asiento generado -->
        <div v-if="compra.asiento" class="bg-white shadow-sm sm:rounded-lg p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4">
            Asiento Contable {{ compra.asiento.numero_asiento }}
          </h3>
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cuenta</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debe</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Haber</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="l in compra.asiento.detalles" :key="l.id">
                <td class="px-4 py-2 text-sm font-mono">{{ l.cuenta?.codigo }} {{ l.cuenta?.nombre }}</td>
                <td class="px-4 py-2 text-sm text-gray-500">{{ l.descripcion }}</td>
                <td class="px-4 py-2 text-sm text-right font-mono text-green-700">
                  {{ Number(l.debe) > 0 ? money(l.debe) : '' }}
                </td>
                <td class="px-4 py-2 text-sm text-right font-mono text-red-700">
                  {{ Number(l.haber) > 0 ? money(l.haber) : '' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </AppLayoutAdmin>
</template>