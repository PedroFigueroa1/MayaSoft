<template>
  <AppLayoutAdmin>
    <Head title="Estado de Resultados - Reporte" />

    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-3 py-3">

        <!-- Header compacto con empresa y período -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3">
          <div class="px-3 py-2 flex flex-wrap justify-between items-center gap-2 text-xs">
            <div class="flex items-center gap-3">
              <div class="font-bold text-gray-800">{{ empresa?.nombre || 'Sistema Contable' }}</div>
              <div class="text-gray-500">NIT: {{ empresa?.nit || 'N/A' }}</div>
              <div class="text-gray-500">Período: {{ periodo.nombre }} ({{ formatDate(periodo.fecha_inicio) }} - {{ formatDate(periodo.fecha_cierre) }})</div>
            </div>
            <div class="flex items-center gap-3">
              <button @click="exportarExcel" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">
                Excel
              </button>
              <button @click="volver" class="text-xs border rounded px-2 py-1 hover:bg-gray-50">
                Volver
              </button>
            </div>
          </div>
        </div>

        <!-- Indicadores compactos (Ingresos, Gastos, Resultado) -->
        <div class="grid grid-cols-3 gap-2 mb-3">
          <div class="bg-green-50 rounded px-2 py-1 text-center border border-green-100">
            <div class="text-[11px] text-green-700">Total Ingresos</div>
            <div class="text-sm font-bold text-green-800">{{ formatCurrency(totalIngresos) }}</div>
          </div>
          <div class="bg-red-50 rounded px-2 py-1 text-center border border-red-100">
            <div class="text-[11px] text-red-700">Total Gastos</div>
            <div class="text-sm font-bold text-red-800">{{ formatCurrency(totalGastos) }}</div>
          </div>
          <div class="bg-blue-50 rounded px-2 py-1 text-center border border-blue-100">
            <div class="text-[11px] text-blue-700">Resultado</div>
            <div class="text-sm font-bold" :class="resultadoEjercicio >= 0 ? 'text-green-700' : 'text-red-700'">
              {{ formatCurrency(resultadoEjercicio) }}
            </div>
          </div>
        </div>

        <!-- Estado de Resultados - dos secciones compactas -->
        <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden mb-3">
          <div class="bg-gray-100 px-3 py-1.5 border-b">
            <h2 class="text-sm font-semibold text-gray-700">ESTADO DE RESULTADOS</h2>
          </div>
          <div class="p-2">
            <!-- Ingresos -->
            <div class="mb-3">
              <h3 class="text-xs font-semibold text-green-700 border-b border-green-200 pb-1 mb-1">INGRESOS</h3>
              <div v-if="estructuraIngresos.length === 0" class="text-gray-400 text-center py-2 text-xs">
                Sin movimientos
              </div>
              <div v-else class="max-h-80 overflow-y-auto text-xs">
                <CuentaItemResultado
                  v-for="cuenta in estructuraIngresos"
                  :key="cuenta.id"
                  :cuenta="cuenta"
                  :nivel="0"
                  tipo="ingreso"
                />
              </div>
              <div class="border-t border-green-200 pt-1 mt-1 text-right text-xs font-semibold">
                TOTAL INGRESOS: {{ formatCurrency(totalIngresos) }}
              </div>
            </div>

            <!-- Gastos -->
            <div class="mb-3">
              <h3 class="text-xs font-semibold text-red-700 border-b border-red-200 pb-1 mb-1">GASTOS</h3>
              <div v-if="estructuraGastos.length === 0" class="text-gray-400 text-center py-2 text-xs">
                Sin movimientos
              </div>
              <div v-else class="max-h-80 overflow-y-auto text-xs">
                <CuentaItemResultado
                  v-for="cuenta in estructuraGastos"
                  :key="cuenta.id"
                  :cuenta="cuenta"
                  :nivel="0"
                  tipo="gasto"
                />
              </div>
              <div class="border-t border-red-200 pt-1 mt-1 text-right text-xs font-semibold">
                TOTAL GASTOS: {{ formatCurrency(totalGastos) }}
              </div>
            </div>

            <!-- Resultado final -->
            <div class="border-t-2 border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center text-sm font-bold" :class="resultadoEjercicio >= 0 ? 'text-green-700' : 'text-red-700'">
                <span>RESULTADO DEL EJERCICIO</span>
                <span>{{ formatCurrency(resultadoEjercicio) }}</span>
              </div>
              <div class="text-[10px] text-center mt-1" :class="resultadoEjercicio >= 0 ? 'text-green-600' : 'text-red-600'">
                {{ resultadoEjercicio > 0 ? 'UTILIDAD NETA' : resultadoEjercicio < 0 ? 'PÉRDIDA NETA' : 'PUNTO DE EQUILIBRIO' }}
                <span v-if="resultadoEjercicio !== 0"> (Margen: {{ margen.toFixed(2) }}%)</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Resumen adicional compacto (opcional) -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 text-xs">
          <div class="flex justify-between items-center">
            <div class="flex gap-3">
              <span>Margen: <strong :class="margen >= 0 ? 'text-green-600' : 'text-red-600'">{{ margen.toFixed(2) }}%</strong></span>
              <span>Resultado: <strong :class="resultadoEjercicio >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(resultadoEjercicio) }}</strong></span>
            </div>
            <div :class="resultadoEjercicio > 0 ? 'text-green-700' : resultadoEjercicio < 0 ? 'text-red-700' : 'text-gray-600'">
              {{ resultadoEjercicio > 0 ? '✓ Utilidad' : resultadoEjercicio < 0 ? '✗ Pérdida' : '⚖️ Equilibrio' }}
            </div>
          </div>
        </div>

        <div class="text-center text-[10px] text-gray-400 mt-3 border-t pt-2">
          {{ empresa?.nombre || '' }} | Generado {{ fechaGeneracion }}
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import CuentaItemResultado from '@/Components/CuentaItemResultado.vue'

const { props } = usePage()

// Datos reactivos desde props
const periodo = computed(() => props.periodo)
const empresa = computed(() => props.empresa || null)
const estructuraIngresos = computed(() => props.estructuraIngresos || [])
const estructuraGastos = computed(() => props.estructuraGastos || [])
const totalIngresos = computed(() => props.totalIngresos || 0)
const totalGastos = computed(() => props.totalGastos || 0)
const resultadoEjercicio = computed(() => props.resultadoEjercicio || 0)
const fechaInicio = computed(() => props.fechaInicio || periodo.value?.fecha_inicio)
const fechaFin = computed(() => props.fechaFin || periodo.value?.fecha_cierre)

const fechaGeneracion = ref('')

const margen = computed(() => {
  if (totalIngresos.value === 0) return 0
  return (resultadoEjercicio.value / totalIngresos.value) * 100
})

function formatDate(dateString) {
  if (!dateString) return '';
  // Separa la fecha de la hora (espacio o 'T' para ISO)
  const fechaParte = dateString.split(' ')[0].split('T')[0];
  const [year, month, day] = fechaParte.split('-');
  return `${day}/${month}/${year}`;
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 0,
    maximumFractionDigits: 0
  }).format(value)
}

function volver() {
  router.visit(route('estado-resultados.index'))
}



onMounted(() => {
  fechaGeneracion.value = new Date().toLocaleString('es-ES')
})
async function exportarPdf() {
  try {
    const response = await axios.post(route('estado-resultados.exportar-pdf'), {
      periodo_id: periodo.value.id,
      fecha_inicio: fechaInicio.value,
      fecha_fin: fechaFin.value
    })
    if (response.data.download_url) {
      window.open(response.data.download_url, '_blank')
    } else {
      alert('Error al generar el PDF')
    }
  } catch (error) {
    console.error('Error al exportar PDF:', error)
    alert('Error al exportar el PDF.')
  }
}

// Función exportar Excel
async function exportarExcel() {
  try {
    const response = await axios.post(
      route('estado-resultados.exportar-excel', { periodo: periodo.value.id }),
      {
        fecha_inicio: fechaInicio.value,
        fecha_fin: fechaFin.value
      }
    )
    if (response.data.download_url) {
      window.open(response.data.download_url, '_blank')
    }
  } catch (error) {
    // FIX: Ahora el servidor devuelve JSON con 'detalle' y 'linea'
    const msg = error.response?.data?.detalle
             || error.response?.data?.error
             || error.response?.data?.message
             || error.message
    console.error('Error Excel:', error.response?.data)
    alert('Error Excel:\n' + msg)
  }
}
</script>
