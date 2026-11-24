<template>
  <AppLayoutAdmin>
    <Head title="Balance General - Reporte" />

    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-3 py-3">

        <!-- Header minimalista con empresa, período y filtros -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3">
          <div class="px-3 py-2 flex flex-wrap justify-between items-center gap-2 text-xs">
            <div class="flex items-center gap-3 flex-wrap">
              <div class="font-bold text-gray-800">{{ empresa?.nombre || 'Sistema Contable' }}</div>
              <div class="text-gray-500">NIT: {{ empresa?.nit || 'N/A' }}</div>
              <div class="text-gray-500">Período: {{ periodo.nombre }} ({{ formatDate(periodo.fecha_inicio) }} - {{ formatDate(periodo.fecha_cierre) }})</div>
              <div v-if="tieneFiltro" class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded-full text-[10px] font-semibold">
                Filtrado: {{ mostrarFecha(props.desde) }} al {{ mostrarFecha(props.hasta) }}
              </div>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
              <!-- Filtros de fecha -->
              <div class="flex items-center gap-1">
                <input
                  type="date"
                  v-model="filtroDesde"
                  class="text-xs border rounded px-1.5 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
                <span class="text-gray-400">-</span>
                <input
                  type="date"
                  v-model="filtroHasta"
                  class="text-xs border rounded px-1.5 py-1 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
                <button @click="aplicarFiltro" class="text-xs bg-gray-800 text-white px-2 py-1 rounded hover:bg-gray-900">
                  Filtrar
                </button>
                <button v-if="tieneFiltro" @click="limpiarFiltro" class="text-xs border rounded px-2 py-1 hover:bg-gray-50">
                  Limpiar
                </button>
              </div>

              <select
                v-model="nivelMaximo"
                @change="cambiarNivel"
                class="text-xs border rounded px-2 py-1 bg-white"
              >
                <option :value="1">Nivel 1</option>
                <option :value="2">Nivel 2</option>
                <option :value="3">Nivel 3</option>
                <option :value="4">Nivel 4</option>
                <option :value="5">Nivel 5 (todos)</option>
              </select>
              <button @click="exportarExcel" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">
                Excel
              </button>
              <button @click="volver" class="text-xs border rounded px-2 py-1 hover:bg-gray-50">
                Volver
              </button>
            </div>
          </div>
        </div>

        <!-- Indicadores compactos (KPI pequeños) -->
        <div class="grid grid-cols-4 gap-2 mb-3">
          <div class="bg-blue-50 rounded px-2 py-1 text-center border border-blue-100">
            <div class="text-[11px] text-blue-700">Total Activos</div>
            <div class="text-sm font-bold text-blue-800">{{ formatCurrency(totalActivos) }}</div>
          </div>
          <div class="bg-orange-50 rounded px-2 py-1 text-center border border-orange-100">
            <div class="text-[11px] text-orange-700">Total Pasivos</div>
            <div class="text-sm font-bold text-orange-800">{{ formatCurrency(totalPasivos) }}</div>
          </div>
          <div class="bg-purple-50 rounded px-2 py-1 text-center border border-purple-100">
            <div class="text-[11px] text-purple-700">Patrimonio Neto</div>
            <div class="text-sm font-bold text-purple-800">{{ formatCurrency(totalPatrimonio) }}</div>
          </div>
          <div class="bg-green-50 rounded px-2 py-1 text-center border border-green-100">
            <div class="text-[11px] text-green-700">Utilidad Ejercicio</div>
            <div class="text-sm font-bold text-green-800">{{ formatCurrency(utilidadEjercicio) }}</div>
          </div>
        </div>

        <!-- Balance General - dos columnas compactas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 mb-3">
          <!-- Activos -->
          <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-100 px-3 py-1.5 border-b">
              <h2 class="text-sm font-semibold text-gray-700">ACTIVOS</h2>
            </div>
            <div class="p-2 max-h-[500px] overflow-y-auto text-xs">
              <div v-if="estructuraActivos.length === 0" class="text-gray-400 text-center py-4">Sin movimientos</div>
              <CuentaItem v-for="cuenta in estructuraActivos" :key="cuenta.id" :cuenta="cuenta" :nivel="0" />
            </div>
            <div class="bg-gray-50 px-3 py-1.5 text-right text-xs font-semibold border-t">
              Total Activos: {{ formatCurrency(totalActivos) }}
            </div>
          </div>

          <!-- Pasivos y Patrimonio -->
          <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-gray-100 px-3 py-1.5 border-b">
              <h2 class="text-sm font-semibold text-gray-700">PASIVOS Y PATRIMONIO</h2>
            </div>
            <div class="p-2 max-h-[500px] overflow-y-auto text-xs">
              <div class="mb-3">
                <h3 class="text-xs font-semibold text-orange-600 border-b pb-1 mb-1">PASIVOS</h3>
                <div v-if="estructuraPasivos.length === 0" class="text-gray-400 py-2">Sin movimientos</div>
                <CuentaItem v-for="cuenta in estructuraPasivos" :key="cuenta.id" :cuenta="cuenta" :nivel="0" />
                <div class="text-right text-xs font-semibold mt-1 pt-1 border-t">Total Pasivos: {{ formatCurrency(totalPasivos) }}</div>
              </div>
              <div class="mb-3">
                <h3 class="text-xs font-semibold text-purple-600 border-b pb-1 mb-1">PATRIMONIO</h3>
                <div v-if="estructuraPatrimonio.length === 0" class="text-gray-400 py-2">Sin movimientos</div>
                <CuentaItem v-for="cuenta in estructuraPatrimonio" :key="cuenta.id" :cuenta="cuenta" :nivel="0" />
                <div class="text-right text-xs font-semibold mt-1 pt-1 border-t">Total Patrimonio: {{ formatCurrency(totalPatrimonio) }}</div>
              </div>
              <div class="bg-green-50 p-2 rounded text-xs flex justify-between">
                <span>Utilidad del Ejercicio</span>
                <span class="font-bold">{{ formatCurrency(utilidadEjercicio) }}</span>
              </div>
              <div class="bg-gray-800 text-white p-2 rounded text-xs mt-2 flex justify-between font-bold">
                <span>TOTAL PASIVOS Y PATRIMONIO</span>
                <span>{{ formatCurrency(totalPasivosPatrimonio + utilidadEjercicio) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Cuadre de balance compacto -->
        <div class="bg-white rounded shadow-sm border border-gray-200 p-2 text-xs">
          <div class="flex justify-between items-center">
            <div class="flex gap-3">
              <span>Total Activos: <strong>{{ formatCurrency(totalActivos) }}</strong></span>
              <span>Total Pasivos + Patrimonio: <strong>{{ formatCurrency(totalPasivosPatrimonio + utilidadEjercicio) }}</strong></span>
            </div>
            <div :class="diferencia === 0 ? 'text-green-700' : 'text-red-700'" class="font-semibold">
              Diferencia: {{ formatCurrency(Math.abs(diferencia)) }} - {{ diferencia === 0 ? '✓ Cuadrado' : '✗ Descuadrado' }}
            </div>
          </div>
        </div>

        <div class="text-center text-[10px] text-gray-400 mt-3 border-t pt-2">
          {{ empresa?.nombre || '' }} | Generado {{ fechaGeneracion }}
          <span v-if="tieneFiltro"> | Rango: {{ mostrarFecha(props.desde) }} - {{ mostrarFecha(props.hasta) }}</span>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import CuentaItem from '@/Components/CuentaItem.vue'

const { props } = usePage()

const periodo = computed(() => props.periodo)
const empresa = computed(() => props.empresa || null)
const estructuraActivos = computed(() => props.estructuraActivos || [])
const estructuraPasivos = computed(() => props.estructuraPasivos || [])
const estructuraPatrimonio = computed(() => props.estructuraPatrimonio || [])
const totalActivos = computed(() => props.totalActivos || 0)
const totalPasivos = computed(() => props.totalPasivos || 0)
const totalPatrimonio = computed(() => props.totalPatrimonio || 0)
const totalPasivosPatrimonio = computed(() => props.totalPasivosPatrimonio || 0)
const utilidadEjercicio = computed(() => props.utilidadEjercicio || 0)

const nivelMaximo = ref(props.nivelMax || 5)
const fechaGeneracion = ref('')

const filtroDesde = ref(props.desde || '')
const filtroHasta = ref(props.hasta || '')

const tieneFiltro = computed(() => !!filtroDesde.value && !!filtroHasta.value)

const diferencia = computed(() => totalActivos.value - (totalPasivosPatrimonio.value + utilidadEjercicio.value))

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('es-ES')
}

function mostrarFecha(fecha) {
  if (!fecha) return '-'
  const [y, m, d] = fecha.split('-')
  return `${d}/${m}/${y}`
}

function formatCurrency(value) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value)
}

function volver() {
  router.visit(route('periodos.index', { empresa: periodo.value?.empresa_id }))
}

const aplicarFiltro = () => {
  if (!filtroDesde.value || !filtroHasta.value) {
    alert('Seleccione ambas fechas')
    return
  }
  if (filtroDesde.value > filtroHasta.value) {
    alert('La fecha "Desde" no puede ser mayor que "Hasta"')
    return
  }
  router.visit(route('balance-general.generar', {
    periodo: periodo.value.id,
    nivel_max: nivelMaximo.value,
    desde: filtroDesde.value,
    hasta: filtroHasta.value
  }), { preserveScroll: true })
}

const limpiarFiltro = () => {
  filtroDesde.value = ''
  filtroHasta.value = ''
  router.visit(route('balance-general.generar', {
    periodo: periodo.value.id,
    nivel_max: nivelMaximo.value
  }), { preserveScroll: true })
}

function cambiarNivel() {
  const params = {
    periodo: periodo.value.id,
    nivel_max: nivelMaximo.value
  }
  if (props.desde) params.desde = props.desde
  if (props.hasta) params.hasta = props.hasta
  router.visit(route('balance-general.generar', params), { preserveScroll: true })
}

async function exportarPdf() {
  try {
    const payload = {
      periodo_id: periodo.value.id,
      nivel_max: nivelMaximo.value
    }
    if (props.desde) payload.desde = props.desde
    if (props.hasta) payload.hasta = props.hasta

    const response = await axios.post(route('balance-general.exportar-pdf'), payload)
    if (response.data.download_url) window.open(response.data.download_url, '_blank')
  } catch (error) {
    alert('Error al exportar PDF')
  }
}

async function exportarExcel() {
  try {
    const payload = {
      periodo_id: periodo.value.id,
      nivel_max: nivelMaximo.value
    }
    if (props.desde) payload.desde = props.desde
    if (props.hasta) payload.hasta = props.hasta

    const response = await axios.post(route('balance-general.exportar-excel'), payload)
    if (response.data.download_url) window.open(response.data.download_url, '_blank')
  } catch (error) {
    console.error(error)
    alert('Error: ' + (error.response?.data?.message || error.response?.data?.exception || error.message))
  }
}

onMounted(() => {
  fechaGeneracion.value = new Date().toLocaleString('es-ES')
})
</script>
