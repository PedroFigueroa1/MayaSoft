<template>
  <AppLayoutAdmin title="Importaciones">
    <div class="py-4 px-4 max-w-screen-2xl mx-auto">
      <h1 class="text-2xl font-bold mb-6 text-gray-800">📂 Importaciones – Libro de Compras</h1>

      <!-- Lista de importaciones (sin agrupar) -->
      <div v-if="importaciones.length === 0" class="text-gray-500">
        No hay importaciones para este período.
      </div>

      <div class="flex flex-wrap gap-4">
        <div
          v-for="imp in importaciones"
          :key="imp.id"
          @click="seleccionarImportacion(imp.id)"
          class="relative bg-white rounded-xl shadow-md border border-gray-100 p-3 w-80 cursor-pointer transition-all hover:shadow-lg"
          :class="selectedId === imp.id ? 'ring-2 ring-indigo-500' : ''"
        >
          <!-- Botón eliminar individual -->
          <button
            @click.stop="eliminarImportacion(imp.id)"
            class="absolute top-2 right-2 text-red-500 hover:text-red-700 text-sm font-bold"
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
              <span>Mes: {{ imp.mes || formatearMes(imp.created_at) }}</span>
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

      <!-- Tabla de detalle (se muestra al seleccionar una importación) -->
      <div v-if="libroCompras.length > 0" class="mt-10">
        <h2 class="text-xl font-bold text-gray-800 mb-3">📋 Libro de Compras – Detalle</h2>
        <div class="overflow-x-auto border rounded-lg shadow-sm" style="max-height: 70vh;">
          <table class="min-w-[1600px] w-full text-xs">
            <thead class="bg-gray-800 text-white sticky top-0 z-10">
              <tr>
                <th class="px-2 py-2 text-left">ID</th>
                <th class="px-2 py-2 text-left">Periodo</th>
                <th class="px-2 py-2 text-left">N° Factura</th>
                <th class="px-2 py-2 text-left">Fecha</th>
                <th class="px-2 py-2 text-left">NIT</th>
                <th class="px-2 py-2 text-left">Razón Social</th>
                <th class="px-2 py-2 text-right">Total</th>
                <th class="px-2 py-2 text-right">ICE</th>
                <th class="px-2 py-2 text-right">IEHD</th>
                <th class="px-2 py-2 text-right">IPJ</th>
                <th class="px-2 py-2 text-right">Tasas</th>
                <th class="px-2 py-2 text-right">Otro No Suj.</th>
                <th class="px-2 py-2 text-right">Exentos</th>
                <th class="px-2 py-2 text-right">Tasa Cero</th>
                <th class="px-2 py-2 text-right">Subtotal</th>
                <th class="px-2 py-2 text-right">Descuento</th>
                <th class="px-2 py-2 text-right">Gift Card</th>
                <th class="px-2 py-2 text-right">Base CF</th>
                <th class="px-2 py-2 text-right">Crédito Fiscal</th>
                <th class="px-2 py-2 text-right">CF Contable</th>
                <th class="px-2 py-2 text-left">Tipo Compra</th>
                <th class="px-2 py-2 text-left">Código Control</th>
                <th class="px-2 py-2 text-left">Concepto</th>
                <th class="px-2 py-2 text-right">Días Créd.</th>
                <th class="px-2 py-2 text-left">Cta Gasto</th>
                <th class="px-2 py-2 text-left">Cta IVA CF</th>
                <th class="px-2 py-2 text-left">Cta Descto.</th>
                <th class="px-2 py-2 text-left">Cta IVA Desc.</th>
                <th class="px-2 py-2 text-left">Cta Pago</th>
                <th class="px-2 py-2 text-right">Debe</th>
                <th class="px-2 py-2 text-right">Haber</th>
                <th class="px-2 py-2 text-left">Asiento</th>
                <th class="px-2 py-2 text-left">Estado</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr v-for="compra in libroCompras" :key="compra.id" class="hover:bg-gray-50">
                <td class="px-2 py-1.5">{{ compra.id }}</td>
                <td class="px-2 py-1.5">{{ compra.periodo_id }}</td>
                <td class="px-2 py-1.5 font-mono">{{ compra.nro_factura || '-' }}</td>
                <td class="px-2 py-1.5">{{ soloFecha(compra.fecha_factura) }}</td>
                <td class="px-2 py-1.5 font-mono">{{ compra.nit_proveedor || '-' }}</td>
                <td class="px-2 py-1.5 max-w-[180px] truncate" :title="compra.razon_social">{{ compra.razon_social || '-' }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_total) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_ice) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_iehd) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_ipj) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.tasas) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.otro_no_sujeto) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importes_exentos) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.compras_tasa_cero) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.subtotal) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.descuento) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_gift_card) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_base_cf) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.credito_fiscal) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.cf_iva_contable) }}</td>
                <td class="px-2 py-1.5">{{ compra.tipo_compra || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.cod_control || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.concepto || '-' }}</td>
                <td class="px-2 py-1.5 text-right">{{ compra.dias_credito }}</td>
                <td class="px-2 py-1.5">{{ compra.cuenta_gasto || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.cuenta_iva_cf || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.cuenta_descuento || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.cuenta_iva_descuento || '-' }}</td>
                <td class="px-2 py-1.5">{{ compra.cuenta_pago || '-' }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_debe) }}</td>
                <td class="px-2 py-1.5 text-right">{{ formatNumber(compra.importe_haber) }}</td>
                <td class="px-2 py-1.5">{{ compra.asiento?.numero_asiento || '-' }}</td>
                <td class="px-2 py-1.5">
                  <span class="px-1.5 py-0.5 rounded-full text-[10px] font-medium" :class="{
                    'bg-gray-200 text-gray-800': compra.estado === 'pendiente',
                    'bg-green-200 text-green-800': compra.estado === 'contabilizado',
                    'bg-red-200 text-red-800': compra.estado === 'anulado'
                  }">{{ compra.estado }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div v-else-if="selectedId && !cargando" class="mt-6 p-4 bg-blue-50 text-blue-800 rounded-lg">
        ⚠️ Esta importación no tiene registros en el libro de compras.
      </div>
      <div v-if="cargando" class="mt-8 text-center">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-indigo-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Cargando compras...</p>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { router } from '@inertiajs/vue3'

const props = defineProps({
  importaciones: Array,  // ← ahora es una lista plana
  periodoId: Number
})

const selectedId = ref(null)
const libroCompras = ref([])
const cargando = ref(false)

// Utilidades
const soloFecha = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('es-ES')
}

const formatNumber = (value) => {
  if (value === null || value === undefined) return '0.00'
  return parseFloat(value).toFixed(2)
}

const formatearMes = (fecha) => {
  return new Date(fecha).toLocaleString('default', { month: 'long', year: 'numeric' })
}

// Seleccionar importación para ver detalle
const seleccionarImportacion = async (id) => {
  if (selectedId.value === id) return
  selectedId.value = id
  cargando.value = true
  libroCompras.value = []

  try {
    const response = await axios.get(`/importaciones/${id}/libro-compras`)
    if (response.data.success) {
      libroCompras.value = response.data.data
    } else {
      libroCompras.value = []
    }
  } catch (error) {
    console.error(error)
    alert('Error al cargar el libro de compras')
  } finally {
    cargando.value = false
  }
}

// Eliminar una importación individual
const eliminarImportacion = async (id) => {
    if (!confirm('¿Eliminar esta importación y todos sus registros? Esta acción no se puede deshacer.')) {
        return;
    }

    try {
        const response = await axios.delete(`/importaciones/${id}`);

        alert(response.data.message);

        if (selectedId.value === id) {
            selectedId.value = null;
            libroCompras.value = [];
        }

        router.reload({ only: ['importaciones'] });

    } catch (error) {
        console.error(error);

        // Mostrar el error real que envía Laravel
        console.log(error.response);
        console.log(error.response?.data);

        alert(error.response?.data?.message ?? 'Error al eliminar la importación');
    }
};
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
