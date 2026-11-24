<template>
  <AppLayoutAdmin title="Importaciones por mes">
    <div class="py-4 px-4 max-w-screen-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-6 text-gray-800">📂 Importaciones – Libro de Ventas</h1>

      <!-- Lista de importaciones sin agrupar -->
      <div class="flex flex-wrap gap-4">
        <div
          v-for="imp in importaciones"
          :key="imp.id"
          @click="seleccionarImportacion(imp.id)"
          class="relative bg-white rounded-xl shadow-md border border-gray-100 p-3 w-80 cursor-pointer transition-all hover:shadow-lg"
          :class="selectedId === imp.id ? 'ring-2 ring-emerald-500' : ''"
        >
          <!-- Botón eliminar -->
          <button
            @click.stop="eliminarImportacion(imp.id)"
            class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-sm"
            title="Eliminar esta importación"
          >
            ✕
          </button>

          <!-- Contenido de la tarjeta -->
          <div class="flex flex-col gap-1">
            <div class="font-semibold text-sm text-gray-800 truncate">
              {{ imp.nombre_archivo }}
            </div>
            <div class="flex justify-between text-xs text-gray-500">
              <span>Mes: {{ imp.mes || new Date(imp.created_at).toLocaleString('default', { month: 'long', year: 'numeric' }) }}</span>
              <span>Estado: {{ imp.estado }}</span>
            </div>
            <div class="flex justify-between text-xs text-gray-500">
              <span>Registros: {{ imp.registros_exitosos }}/{{ imp.total_registros }}</span>
              <span>Importado por: {{ imp.importado_por?.name || 'N/A' }}</span>
            </div>
            <div class="text-[10px] text-gray-400">
              {{ new Date(imp.created_at).toLocaleString() }}
            </div>
          </div>
        </div>
      </div>

      <!-- TABLA COMPLETA DEL LIBRO DE VENTAS -->
      <div v-if="libroVentas.length > 0" class="mt-10">
        <h2 class="text-xl font-bold text-gray-800 mb-3">📋 Libro de Ventas – Detalle completo</h2>
        <div class="overflow-x-auto border rounded-lg shadow-sm" style="max-height: 70vh;">
          <table class="min-w-[2000px] w-full text-xs">
            <thead class="bg-gray-800 text-white sticky top-0 z-10">
              <tr>
                <th class="px-2 py-2 text-left">ID</th>
                <th class="px-2 py-2 text-left">Periodo</th>
                <th class="px-2 py-2 text-left">N° Correlativo</th>
                <th class="px-2 py-2 text-left">Especificación</th>
                <th class="px-2 py-2 text-left">Fecha Factura</th>
                <th class="px-2 py-2 text-left">N° Factura</th>
                <th class="px-2 py-2 text-left">Cód. Autorización</th>
                <th class="px-2 py-2 text-left">NIT / CI Cliente</th>
                <th class="px-2 py-2 text-left">Complemento</th>
                <th class="px-2 py-2 text-left">Razón Social</th>
                <th class="px-2 py-2 text-right">Total Venta</th>
                <th class="px-2 py-2 text-right">ICE</th>
                <th class="px-2 py-2 text-right">IEHD</th>
                <th class="px-2 py-2 text-right">IPJ</th>
                <th class="px-2 py-2 text-right">Tasas</th>
                <th class="px-2 py-2 text-right">Otros no Suj.</th>
                <th class="px-2 py-2 text-right">Export. Exentas</th>
                <th class="px-2 py-2 text-right">Tasa Cero</th>
                <th class="px-2 py-2 text-right">Subtotal</th>
                <th class="px-2 py-2 text-right">Descuento</th>
                <th class="px-2 py-2 text-right">Gift Card</th>
                <th class="px-2 py-2 text-right">Base DF</th>
                <th class="px-2 py-2 text-right">Débito Fiscal</th>
                <th class="px-2 py-2 text-left">Estado Factura</th>
                <th class="px-2 py-2 text-left">Cód. Control</th>
                <th class="px-2 py-2 text-left">Tipo Venta</th>
                <th class="px-2 py-2 text-right">Debe</th>
                <th class="px-2 py-2 text-right">Haber</th>
                <th class="px-2 py-2 text-left">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="venta in libroVentas" :key="venta.id" class="hover:bg-gray-50">
                <td class="px-2 py-1.5">{{ venta.id }}</td>
                <td class="px-2 py-1.5">{{ venta.periodo_id }}</td>
                <td class="px-2 py-1.5">{{ venta.nro_correlativo || '-' }}</td>
                <td class="px-2 py-1.5 max-w-[120px] truncate" :title="venta.especificacion">{{ venta.especificacion || '-' }}</td>
                <td class="px-2 py-1.5">{{ soloFecha(venta.fecha_factura) }}</td>
                <td class="px-2 py-1.5 font-mono">{{ venta.nro_factura || '-' }}</td>
                <td class="px-2 py-1.5">{{ venta.cod_autorizacion || '-' }}</td>
                <td class="px-2 py-1.5 font-mono">{{ venta.nit_cliente || '-' }}</td>
                <td class="px-2 py-1.5">{{ venta.complemento || '-' }}</td>
                <td class="px-2 py-1.5 max-w-[180px] truncate" :title="venta.razon_social_cliente">{{ venta.razon_social_cliente || '-' }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_total) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_ice) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_iehd) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_ipj) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.tasas) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.otros_no_sujetos) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.exportaciones_exentas) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.ventas_tasa_cero) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.subtotal) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.descuento) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_gift_card) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_base_df) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.debito_fiscal) }}</td>
                <td class="px-2 py-1.5">{{ venta.estado_factura || '-' }}</td>
                <td class="px-2 py-1.5">{{ venta.cod_control || '-' }}</td>
                <td class="px-2 py-1.5">{{ venta.tipo_venta || '-' }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_debe) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(venta.importe_haber) }}</td>
                <td class="px-2 py-1.5">
                  <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium" :class="{
                    'bg-gray-200 text-gray-800': venta.estado === 'pendiente',
                    'bg-green-200 text-green-800': venta.estado === 'contabilizado',
                    'bg-red-200 text-red-800': venta.estado === 'anulado'
                  }">{{ venta.estado }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="selectedId && !cargando" class="mt-6 p-4 bg-blue-50 text-blue-800 rounded-lg">
        ⚠️ Esta importación no tiene registros en el libro de ventas.
      </div>
      <div v-if="cargando" class="mt-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-emerald-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Cargando ventas...</p>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { router } from '@inertiajs/vue3'

// La prop ahora es 'importaciones' (lista plana)
const props = defineProps({
  importaciones: Array,
  periodoId: Number
})

const selectedId = ref(null)
const libroVentas = ref([])
const cargando = ref(false)

const soloFecha = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('es-ES')
}

const formatNumber = (value) => {
  if (value === null || value === undefined) return '0.00'
  return parseFloat(value).toFixed(2)
}

const seleccionarImportacion = async (id) => {
  if (selectedId.value === id) return
  selectedId.value = id
  cargando.value = true
  libroVentas.value = []

  try {
    const response = await axios.get(`/importaciones/${id}/libro-ventas`)
    if (response.data.success) {
      libroVentas.value = response.data.data
    } else {
      libroVentas.value = []
    }
  } catch (error) {
    console.error(error)
    alert('Error al cargar el libro de ventas')
  } finally {
    cargando.value = false
  }
}

// Eliminar una importación individual
const eliminarImportacion = async (id) => {
  if (!confirm('¿Eliminar esta importación y todos sus registros? Esta acción no se puede deshacer.')) return
  try {
    await axios.delete(`/importaciones/${id}`)
    if (selectedId.value === id) {
      selectedId.value = null
      libroVentas.value = []
    }
    router.reload({ only: ['importaciones'] })
  } catch (error) {
    console.error(error)
    alert('Error al eliminar la importación')
  }
}
</script>

<style scoped>
.overflow-x-auto {
  overflow-x: auto;
  white-space: nowrap;
}
.sticky {
  position: sticky;
}
</style>
