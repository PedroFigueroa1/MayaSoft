<template>
    <AppLayoutAdmin>
        <div class="container mx-auto p-2">
            <!-- Barra superior ultracompacta (solo pantalla) -->
            <div class="bg-white shadow-sm rounded-lg p-2 mb-3 no-print">
            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                <!-- Empresa -->
                <div class="flex items-center space-x-1">
                <span class="font-semibold text-gray-600">Empresa:</span>
                <span class="text-gray-800">{{ empresa.nombre || 'N/A' }}</span>
                <span v-if="empresa.ruc" class="text-gray-500 text-xs">({{ empresa.ruc }})</span>
                </div>
                <!-- Período -->
                <div class="flex items-center space-x-1">
                <span class="font-semibold text-gray-600">Período:</span>
                <span class="text-gray-800">{{ periodo.nombre }}</span>
                <span class="text-gray-500 text-xs">{{ formatDate(periodo.fecha_inicio) }} → {{ formatDate(periodo.fecha_cierre) }}</span>
                </div>
                <!-- Cuenta -->
                <div class="flex items-center space-x-1">
                <span class="font-semibold text-gray-600">Cuenta:</span>
                <span class="text-blue-700 font-medium">{{ cuenta.codigo }} - {{ cuenta.nombre }}</span>
                </div>
                <!-- Saldo Inicial -->
                <div class="flex items-center space-x-1">
                <span class="font-semibold text-gray-600">Saldo inicial:</span>
                <span class="text-green-700 font-bold">{{ formatCurrency(saldoInicial) }}</span>
                </div>
                <!-- Botón volver (solo en pantalla) -->
                <button @click="volver" class="text-blue-600 hover:text-blue-800 text-xs underline">← Volver</button>
            </div>
            <!-- Filtros si existen (opcional) -->
            <div v-if="filters.fecha_inicio || filters.fecha_fin" class="text-xs text-yellow-700 mt-1">
                🔍 Filtro: {{ filters.fecha_inicio ? `desde ${formatDate(filters.fecha_inicio)}` : '' }} {{ filters.fecha_fin ? `hasta ${formatDate(filters.fecha_fin)}` : '' }}
            </div>
            </div>

            <!-- Encabezado para impresión (se muestra solo en print) -->
            <div class="print-header hidden print:block mb-4">
            <div class="flex justify-between border-b pb-2">
                <div>
                <h1 class="text-xl font-bold">{{ empresa.nombre }}</h1>
                <p class="text-sm">RUC: {{ empresa.ruc }} | {{ empresa.direccion }}</p>
                </div>
                <div class="text-right">
                <h2 class="text-lg font-bold">LIBRO MAYOR</h2>
                <p class="text-xs">{{ fechaActual }}</p>
                </div>
            </div>
            </div>

            <!-- Tabla de movimientos (ahora más protagonista) -->
            <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="flex justify-between items-center px-4 py-2 border-b no-print">
                <h2 class="text-md font-semibold">Movimientos de la cuenta</h2>
                <div class="flex space-x-2">
                <button @click="exportarPdf" class="px-3 py-1 bg-green-600 text-white text-sm rounded">PDF</button>
                <button @click="imprimir" class="px-3 py-1 bg-blue-600 text-white text-sm rounded">Imprimir</button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                    <th class="px-3 py-2 text-left">Fecha</th>
                    <th class="px-3 py-2 text-left">N° Asiento</th>
                    <th class="px-3 py-2 text-left">Glosa</th>
                    <th class="px-3 py-2 text-right">Debe</th>
                    <th class="px-3 py-2 text-right">Haber</th>
                    <th class="px-3 py-2 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-gray-50">
                    <td colspan="3" class="px-3 py-2 text-right font-medium">Saldo Inicial</td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2"></td>
                    <td class="px-3 py-2 text-right font-medium">{{ formatCurrency(saldoInicial) }}</td>
                    </tr>
                    <tr v-for="(mov, idx) in movimientos" :key="idx" class="border-b">
                    <td class="px-3 py-2">{{ formatDate(mov.fecha) }}</td>
                    <td class="px-3 py-2">{{ mov.numero_asiento }}</td>
                    <td class="px-3 py-2">{{ mov.glosa }}</td>
                    <td class="px-3 py-2 text-right">{{ mov.debe ? formatCurrency(mov.debe) : '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ mov.haber ? formatCurrency(mov.haber) : '-' }}</td>
                    <td class="px-3 py-2 text-right font-medium">{{ formatCurrency(calcularSaldoAcumulado(idx)) }}</td>
                    </tr>
                    <tr class="bg-blue-50 font-semibold">
                    <td colspan="3" class="px-3 py-2 text-right">TOTALES</td>
                    <td class="px-3 py-2 text-right">{{ formatCurrency(calcularTotalDebe()) }}</td>
                    <td class="px-3 py-2 text-right">{{ formatCurrency(calcularTotalHaber()) }}</td>
                    <td class="px-3 py-2 text-right">{{ formatCurrency(calcularSaldoFinal()) }}</td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>

            <!-- Botón volver inferior (opcional) -->
            <div class="mt-4 text-center no-print">
            <button @click="volver" class="text-sm text-gray-600 underline">← Volver al menú</button>
            </div>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import { computed } from 'vue'
import { usePage, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const { props } = usePage()
const periodo = props.periodo
const cuenta = props.cuenta
const movimientos = props.movimientos
const saldoInicial = props.saldoInicial
const empresa = props.empresa || {}
const filters = props.filters || {}

const fechaActual = computed(() => new Date().toLocaleDateString('es-ES'))

function formatDate(dateString) {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('es-ES')
}

// Cambio aquí: usar Bs.
function formatCurrency(value) {
  const num = value || 0;
  // Formato: Bs. 1.234,56 (punto miles, coma decimales)
  const partes = num.toFixed(2).split('.');
  const entero = partes[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  return 'Bs. ' + entero + ',' + partes[1];
}

// resto de funciones igual...
function obtenerTipoCuenta(tipo) {
  const tipos = { 'A': 'Activo', 'P': 'Pasivo', 'PN': 'Patrimonio', 'I': 'Ingreso', 'G': 'Gasto' }
  return tipos[tipo] || tipo
}

function calcularSaldoAcumulado(index) {
  let saldo = saldoInicial
  const esActivoGasto = (cuenta.tipo === 'A' || cuenta.tipo === 'G')
  for (let i = 0; i <= index; i++) {
    saldo += esActivoGasto ? (movimientos[i].debe || 0) - (movimientos[i].haber || 0) : (movimientos[i].haber || 0) - (movimientos[i].debe || 0)
  }
  return saldo
}

function calcularTotalDebe() {
  return movimientos.reduce((sum, m) => sum + (m.debe || 0), 0)
}

function calcularTotalHaber() {
  return movimientos.reduce((sum, m) => sum + (m.haber || 0), 0)
}

function calcularSaldoFinal() {
  let saldo = saldoInicial
  const esActivoGasto = (cuenta.tipo === 'A' || cuenta.tipo === 'G')
  movimientos.forEach(m => {
    saldo += esActivoGasto ? (m.debe || 0) - (m.haber || 0) : (m.haber || 0) - (m.debe || 0)
  })
  return saldo
}

function volver() {
  router.visit(route('periodos.index',  empresa.id))
}

function imprimir() { window.print() }

async function exportarPdf() {
  try {
    const response = await axios.post(route('libro-mayor.exportar-pdf'), {
      periodo_id: periodo.id,
      cuenta_id: cuenta.id,
      fecha_inicio: filters.fecha_inicio,
      fecha_fin: filters.fecha_fin
    })
    if (response.data.download_url) window.open(response.data.download_url, '_blank')
  } catch (error) {
    alert('Error al exportar PDF')
  }
}
</script>

<style scoped>
@media print {
  .no-print { display: none !important; }
  .print-header { display: block !important; }
  body { font-size: 12px; }
  table { font-size: 10px; }
  .bg-blue-50, .bg-gray-100 { -webkit-print-color-adjust: exact; }
}
</style>
