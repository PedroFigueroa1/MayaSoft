<template>
  <AppLayoutAdmin>
    <Head title="Evolución del Patrimonio" />
    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-3 py-3">

        <!-- Header -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3">
          <div class="px-3 py-2 flex flex-wrap justify-between items-center gap-2 text-xs">
            <div class="flex items-center gap-3">
              <div class="font-bold text-gray-800">{{ empresa?.nombre }}</div>
              <div class="text-gray-500">NIT: {{ empresa?.nit }}</div>
              <div class="text-gray-500">Al {{ formatDate(periodo.fecha_cierre) }}</div>
            </div>
            <div class="flex gap-2">
              <button @click="exportarExcel" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Excel</button>
              <button @click="volver" class="text-xs border rounded px-2 py-1 hover:bg-gray-50">Volver</button>
            </div>
          </div>
        </div>

        <!-- Tabla principal -->
        <div class="bg-white rounded shadow-sm border border-gray-200 overflow-x-auto">
          <table class="w-full text-xs border-collapse">
            <!-- Headers -->
            <thead>
              <tr>
                <th class="bg-blue-800 text-white px-2 py-2 text-left w-64 border border-blue-900">DESCRIPCIÓN</th>
                <th v-for="col in columnas" :key="col.key"
                    class="bg-blue-800 text-white px-2 py-2 text-center border border-blue-900 whitespace-pre-line" style="min-width:110px">
                  {{ col.label }}
                </th>
              </tr>
            </thead>
            <tbody>
              <!-- Saldo inicial -->
              <tr class="bg-green-50 font-semibold">
                <td class="px-2 py-1.5 border border-gray-200">Saldos al {{ formatDate(periodoAnteriorFecha) }}</td>
                <td v-for="col in columnas" :key="col.key" class="px-2 py-1.5 text-right border border-gray-200"
                    :class="getColorClass(saldoInicial[col.key])">
                  {{ formatVal(saldoInicial[col.key]) }}
                </td>
              </tr>

              <!-- Spacer -->
              <tr><td colspan="8" class="h-1 bg-gray-100"></td></tr>

              <!-- Movimientos -->
              <tr v-for="(mov, i) in movimientos" :key="i" class="bg-orange-50">
                <td class="px-2 py-1 border border-gray-200 pl-4">{{ mov.descripcion }}</td>
                <td v-for="col in columnas" :key="col.key" class="px-2 py-1 text-right border border-gray-200"
                    :class="getColorClass(mov[col.key])">
                  {{ formatVal(mov[col.key]) }}
                </td>
              </tr>

              <!-- Spacer -->
              <tr><td colspan="8" class="h-1 bg-gray-100"></td></tr>

              <!-- Saldo final -->
              <tr class="bg-blue-800 text-white font-bold text-[11px]">
                <td class="px-2 py-2 border border-blue-900">Saldo al {{ formatDate(periodo.fecha_cierre) }}</td>
                <td v-for="col in columnas" :key="col.key" class="px-2 py-2 text-right border border-blue-900"
                    :class="saldoFinal[col.key] < 0 ? 'text-red-300' : ''">
                  {{ formatVal(saldoFinal[col.key]) }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="text-center text-[10px] text-gray-400 mt-3 border-t pt-2">
          {{ empresa?.nombre }} | Generado {{ fechaGeneracion }}
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

const { props } = usePage()

const periodo      = props.periodo
const empresa      = props.empresa
const saldoInicial = props.saldoInicial
const movimientos  = props.movimientos
const saldoFinal   = props.saldoFinal

const fechaGeneracion = ref('')

const columnas = [
  { key: 'capital_social',        label: 'Capital\nSocial' },
  { key: 'ajuste_capital',        label: 'Ajuste de\nCapital' },
  { key: 'utilidad_gestion',      label: 'Utilidad/Pérdida\nde la Gestión' },
  { key: 'resultados_acumulados', label: 'Resultados\nAcumulados' },
  { key: 'ajuste_global',         label: 'Ajuste Global\nal Patrimonio' },
  { key: 'reserva_legal',         label: 'Reserva\nLegal' },
  { key: 'total',                 label: 'TOTAL' },
]

const periodoAnteriorFecha = computed(() => {
  const d = new Date(periodo.fecha_inicio)
  d.setDate(d.getDate() - 1)
  return d.toISOString().split('T')[0]
})

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('es-ES', { day:'2-digit', month:'2-digit', year:'numeric' })
}

function formatVal(val) {
  if (val === null || val === undefined) return ''
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(val)
}

function getColorClass(val) {
  if (val === null || val === undefined || val === 0) return 'text-gray-400'
  return val < 0 ? 'text-red-600' : 'text-gray-800'
}

function volver() {
  router.visit(route('periodos.index', { empresa: periodo?.empresa_id }))
}

async function exportarExcel() {
  try {
    const res = await axios.post(route('evolucion-patrimonio.exportar-excel'), { periodo_id: periodo.id })
    if (res.data.download_url) window.open(res.data.download_url, '_blank')
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message))
  }
}

onMounted(() => { fechaGeneracion.value = new Date().toLocaleString('es-ES') })
</script>
