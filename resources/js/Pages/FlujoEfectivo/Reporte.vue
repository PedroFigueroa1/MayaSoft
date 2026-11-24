<template>
  <AppLayoutAdmin>
    <Head title="Flujo de Efectivo" />
    <div class="min-h-screen bg-gray-50">
      <div class="max-w-7xl mx-auto px-3 py-3">

        <!-- Header -->
        <div class="bg-white rounded shadow-sm border border-gray-200 mb-3">
          <div class="px-3 py-2 flex flex-wrap justify-between items-center gap-2 text-xs">
            <div class="flex items-center gap-3">
              <div class="font-bold text-gray-800">{{ empresa?.nombre }}</div>
              <div class="text-gray-500">NIT: {{ empresa?.nit }}</div>
              <div class="text-gray-500">{{ periodo.nombre }}</div>
            </div>
            <div class="flex gap-2">
              <button @click="exportarExcel" class="text-xs bg-green-600 text-white px-2 py-1 rounded hover:bg-green-700">Excel</button>
              <!-- <button @click="exportarPdf"   class="text-xs bg-red-600   text-white px-2 py-1 rounded hover:bg-red-700">PDF</button> -->
              <button @click="volver"         class="text-xs border rounded px-2 py-1 hover:bg-gray-50">Volver</button>
            </div>
          </div>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-3 gap-2 mb-3">
          <div class="bg-blue-50 rounded px-2 py-1 text-center border border-blue-100">
            <div class="text-[11px] text-blue-700">Flujo Operativo</div>
            <div class="text-sm font-bold" :class="totalOperativas >= 0 ? 'text-blue-800' : 'text-red-700'">{{ formatNum(totalOperativas) }}</div>
          </div>
          <div class="bg-purple-50 rounded px-2 py-1 text-center border border-purple-100">
            <div class="text-[11px] text-purple-700">Flujo Neto</div>
            <div class="text-sm font-bold" :class="flujoNeto >= 0 ? 'text-purple-800' : 'text-red-700'">{{ formatNum(flujoNeto) }}</div>
          </div>
          <div class="bg-green-50 rounded px-2 py-1 text-center border border-green-100">
            <div class="text-[11px] text-green-700">Efectivo Final</div>
            <div class="text-sm font-bold text-green-800">{{ formatNum(saldoFinal) }}</div>
          </div>
        </div>

        <!-- DOS TABLAS EN PARALELO -->
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-3 mb-3">

          <!-- TABLA 1: ORIGEN Y APLICACIÓN DE FONDOS -->
          <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden">
            <div class="bg-blue-800 text-white text-xs font-bold px-3 py-2 text-center">
              ESTADO DE ORIGEN Y APLICACIÓN DE FONDOS<br>
              <span class="font-normal text-blue-200">Al {{ formatDate(periodo.fecha_cierre) }} | (Expresado en Bolivianos)</span>
            </div>
            <div class="overflow-x-auto">
              <table class="w-full text-[11px] border-collapse">
                <thead>
                  <tr class="bg-blue-700 text-white">
                    <th class="px-2 py-1 text-left border border-blue-900">DESCRIPCIÓN</th>
                    <th class="px-2 py-1 text-right border border-blue-900 w-20">SALDO INI.</th>
                    <th class="px-2 py-1 text-right border border-blue-900 w-20">SALDO FIN.</th>
                    <th class="px-2 py-1 text-right border border-blue-900 w-20">ORIGEN</th>
                    <th class="px-2 py-1 text-right border border-blue-900 w-20">APLICACIÓN</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Activos -->
                  <tr class="bg-blue-50">
                    <td colspan="5" class="px-2 py-1 font-bold text-blue-800 border border-gray-200">ACTIVO</td>
                  </tr>
                  <tr class="bg-blue-50">
                    <td colspan="5" class="px-2 py-0.5 text-blue-700 border border-gray-200 pl-3">CORRIENTE</td>
                  </tr>
                  <template v-for="item in origenAplicacion.filter(r => r.tipo === 'A' && r.codigo.startsWith('1.1'))" :key="item.cuenta_id">
                    <tr class="hover:bg-gray-50">
                      <td class="px-2 py-0.5 border border-gray-100 pl-4">{{ item.nombre }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_ini) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_fin) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-green-700">{{ item.origen ? formatVal(item.origen) : '' }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-red-600">{{ item.aplicacion ? formatVal(item.aplicacion) : '' }}</td>
                    </tr>
                  </template>
                  <tr class="bg-blue-50">
                    <td colspan="5" class="px-2 py-0.5 text-blue-700 border border-gray-200 pl-3">NO CORRIENTE</td>
                  </tr>
                  <template v-for="item in origenAplicacion.filter(r => r.tipo === 'A' && r.codigo.startsWith('1.2'))" :key="item.cuenta_id">
                    <tr class="hover:bg-gray-50">
                      <td class="px-2 py-0.5 border border-gray-100 pl-4">{{ item.nombre }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_ini) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_fin) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-green-700">{{ item.origen ? formatVal(item.origen) : '' }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-red-600">{{ item.aplicacion ? formatVal(item.aplicacion) : '' }}</td>
                    </tr>
                  </template>

                  <!-- Total activo -->
                  <tr class="bg-blue-100 font-semibold text-[11px]">
                    <td class="px-2 py-1 border border-gray-200">TOTAL ACTIVO</td>
                    <td class="px-2 py-1 text-right border border-gray-200 font-mono">{{ formatVal(totalActivoIni) }}</td>
                    <td class="px-2 py-1 text-right border border-gray-200 font-mono">{{ formatVal(totalActivoFin) }}</td>
                    <td class="px-2 py-1 border border-gray-200"></td>
                    <td class="px-2 py-1 border border-gray-200"></td>
                  </tr>

                  <!-- Pasivos -->
                  <tr class="bg-orange-50">
                    <td colspan="5" class="px-2 py-1 font-bold text-orange-800 border border-gray-200">PASIVO CORRIENTE</td>
                  </tr>
                  <template v-for="item in origenAplicacion.filter(r => r.tipo === 'P' && r.codigo.startsWith('2.1'))" :key="item.cuenta_id">
                    <tr class="hover:bg-gray-50">
                      <td class="px-2 py-0.5 border border-gray-100 pl-4">{{ item.nombre }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_ini) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_fin) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-green-700">{{ item.origen ? formatVal(item.origen) : '' }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-red-600">{{ item.aplicacion ? formatVal(item.aplicacion) : '' }}</td>
                    </tr>
                  </template>

                  <!-- Total pasivo -->
                  <tr class="bg-orange-100 font-semibold">
                    <td class="px-2 py-1 border border-gray-200">TOTAL PASIVO</td>
                    <td class="px-2 py-1 text-right border border-gray-200 font-mono">{{ formatVal(totalPasivoIni) }}</td>
                    <td class="px-2 py-1 text-right border border-gray-200 font-mono">{{ formatVal(totalPasivoFin) }}</td>
                    <td class="px-2 py-1 border border-gray-200"></td>
                    <td class="px-2 py-1 border border-gray-200"></td>
                  </tr>

                  <!-- Patrimonio -->
                  <tr class="bg-purple-50">
                    <td colspan="5" class="px-2 py-1 font-bold text-purple-800 border border-gray-200">PATRIMONIO</td>
                  </tr>
                  <template v-for="item in origenAplicacion.filter(r => r.tipo === 'PN')" :key="item.cuenta_id">
                    <tr class="hover:bg-gray-50">
                      <td class="px-2 py-0.5 border border-gray-100 pl-4">{{ item.nombre }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_ini) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono">{{ formatVal(item.saldo_fin) }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-green-700">{{ item.origen ? formatVal(item.origen) : '' }}</td>
                      <td class="px-2 py-0.5 text-right border border-gray-100 font-mono text-red-600">{{ item.aplicacion ? formatVal(item.aplicacion) : '' }}</td>
                    </tr>
                  </template>

                  <!-- Total general -->
                  <tr class="bg-blue-800 text-white font-bold">
                    <td class="px-2 py-1.5 border border-blue-900">TOTAL PASIVO Y PATRIMONIO</td>
                    <td class="px-2 py-1.5 text-right border border-blue-900 font-mono">{{ formatVal(totalPasivoIni + totalPNIni) }}</td>
                    <td class="px-2 py-1.5 text-right border border-blue-900 font-mono">{{ formatVal(totalPasivoFin + totalPNFin) }}</td>
                    <td class="px-2 py-1.5 text-right border border-blue-900 font-mono">{{ formatVal(totalOrigenGeneral) }}</td>
                    <td class="px-2 py-1.5 text-right border border-blue-900 font-mono">{{ formatVal(totalAplicacionGeneral) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- TABLA 2: FLUJO DE EFECTIVO -->
          <div class="bg-white rounded shadow-sm border border-gray-200 overflow-hidden text-xs">
            <div class="bg-blue-800 text-white text-xs font-bold px-3 py-2 text-center">
              ESTADO DE FLUJO DE EFECTIVO<br>
              <span class="font-normal text-blue-200">Por el Ejercicio Terminado al {{ formatDate(periodo.fecha_cierre) }}</span>
            </div>

            <!-- Operativas -->
            <div class="bg-blue-700 text-white font-bold px-3 py-1.5">
              FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES OPERATIVAS
            </div>
            <template v-for="(act, i) in actividadesOperativas" :key="i">
              <div v-if="act.es_ajuste && (i === 0 || !actividadesOperativas[i-1].es_ajuste)"
                   class="px-3 py-0.5 bg-green-50 text-gray-500 italic border-b border-gray-100">
                Ajustes:
              </div>
              <div class="flex justify-between border-b border-gray-100 bg-green-50"
                   :style="act.es_ajuste ? 'padding:3px 12px 3px 28px' : 'padding:4px 12px'">
                <span class="text-gray-700">{{ act.concepto }}</span>
                <span class="font-mono" :class="act.tipo === 'salida' ? 'text-red-600' : 'text-gray-800'">
                  {{ act.tipo === 'salida' ? '(' + formatVal(act.monto) + ')' : formatVal(act.monto) }}
                </span>
              </div>
            </template>
            <div class="flex justify-between px-3 py-1.5 bg-blue-100 font-semibold border-b-2 border-blue-300">
              <span>TOTAL EFECTIVO POR OPERACIONES</span>
              <span :class="totalOperativas < 0 ? 'text-red-600' : 'text-blue-800'">{{ formatVal(totalOperativas) }}</span>
            </div>

            <!-- Inversión -->
            <div class="bg-blue-900 text-white font-bold px-3 py-1.5">
              FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES DE INVERSIÓN
            </div>
            <div v-if="actividadesInversion.length === 0" class="px-3 py-1 bg-blue-50 text-gray-400 italic border-b border-gray-100">
              Sin movimientos
            </div>
            <template v-for="(act, i) in actividadesInversion" :key="i">
              <div class="flex justify-between px-3 py-0.5 bg-blue-50 border-b border-gray-100">
                <span class="text-gray-700">{{ act.concepto }}</span>
                <span class="font-mono" :class="act.tipo === 'salida' ? 'text-red-600' : 'text-gray-800'">
                  {{ act.tipo === 'salida' ? '(' + formatVal(act.monto) + ')' : formatVal(act.monto) }}
                </span>
              </div>
            </template>
            <div class="flex justify-between px-3 py-1.5 bg-blue-100 font-semibold border-b-2 border-blue-300">
              <span>TOTAL EFECTIVO POR INVERSIÓN</span>
              <span :class="totalInversion < 0 ? 'text-red-600' : 'text-blue-800'">{{ formatVal(totalInversion) }}</span>
            </div>

            <!-- Financiamiento -->
            <div class="bg-purple-800 text-white font-bold px-3 py-1.5">
              FLUJO DEL EFECTIVO GENERADO POR ACTIVIDADES DE FINANCIAMIENTO
            </div>
            <div v-if="actividadesFinanciamiento.length === 0" class="px-3 py-1 bg-orange-50 text-gray-400 italic border-b border-gray-100">
              Sin movimientos
            </div>
            <template v-for="(act, i) in actividadesFinanciamiento" :key="i">
              <div class="flex justify-between px-3 py-0.5 bg-orange-50 border-b border-gray-100">
                <span class="text-gray-700">{{ act.concepto }}</span>
                <span class="font-mono" :class="act.tipo === 'salida' ? 'text-red-600' : 'text-gray-800'">
                  {{ act.tipo === 'salida' ? '(' + formatVal(act.monto) + ')' : formatVal(act.monto) }}
                </span>
              </div>
            </template>
            <div class="flex justify-between px-3 py-1.5 bg-purple-100 font-semibold border-b-2 border-purple-300">
              <span>TOTAL EFECTIVO POR FINANCIAMIENTO</span>
              <span :class="totalFinanciamiento < 0 ? 'text-red-600' : 'text-purple-800'">{{ formatVal(totalFinanciamiento) }}</span>
            </div>

            <!-- Resumen final -->
            <div class="flex justify-between px-3 py-2 bg-gray-800 text-white font-bold">
              <span>DIFERENCIA EFECTIVO GENERADA EN LA GESTIÓN</span>
              <span :class="flujoNeto < 0 ? 'text-red-300' : ''">{{ formatVal(flujoNeto) }}</span>
            </div>
            <div class="flex justify-between px-3 py-1.5 bg-indigo-50 text-indigo-800">
              <span>Saldo Inicial de Efectivo</span>
              <span class="font-semibold font-mono">{{ formatVal(saldoInicial) }}</span>
            </div>
            <div class="flex justify-between px-3 py-2 bg-green-800 text-white font-bold">
              <span>EFECTIVO AL FINAL DE LA GESTIÓN</span>
              <span class="font-mono">{{ formatVal(saldoFinal) }}</span>
            </div>
          </div>
        </div>

        <div class="text-center text-[10px] text-gray-400 mt-2 border-t pt-2">
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

const periodo                   = props.periodo
const empresa                   = props.empresa
const origenAplicacion          = props.origenAplicacion          || []
const actividadesOperativas     = props.actividadesOperativas     || []
const actividadesInversion      = props.actividadesInversion      || []
const actividadesFinanciamiento = props.actividadesFinanciamiento || []
const totalOperativas           = props.totalOperativas           || 0
const totalInversion            = props.totalInversion            || 0
const totalFinanciamiento       = props.totalFinanciamiento       || 0
const flujoNeto                 = props.flujoNeto                 || 0
const saldoInicial              = props.saldoInicial              || 0
const saldoFinal                = props.saldoFinal                || 0

const fechaGeneracion = ref('')

// Totales para la tabla origen/aplicación
const totalActivoIni  = computed(() => origenAplicacion.filter(r => r.tipo === 'A').reduce((s, r) => s + r.saldo_ini, 0))
const totalActivoFin  = computed(() => origenAplicacion.filter(r => r.tipo === 'A').reduce((s, r) => s + r.saldo_fin, 0))
const totalPasivoIni  = computed(() => origenAplicacion.filter(r => r.tipo === 'P').reduce((s, r) => s + r.saldo_ini, 0))
const totalPasivoFin  = computed(() => origenAplicacion.filter(r => r.tipo === 'P').reduce((s, r) => s + r.saldo_fin, 0))
const totalPNIni      = computed(() => origenAplicacion.filter(r => r.tipo === 'PN').reduce((s, r) => s + r.saldo_ini, 0))
const totalPNFin      = computed(() => origenAplicacion.filter(r => r.tipo === 'PN').reduce((s, r) => s + r.saldo_fin, 0))
const totalOrigenGeneral     = computed(() => origenAplicacion.reduce((s, r) => s + r.origen, 0))
const totalAplicacionGeneral = computed(() => origenAplicacion.reduce((s, r) => s + r.aplicacion, 0))

function formatVal(v) {
  if (v === null || v === undefined) return ''
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v)
}

function formatNum(v) {
  return 'Bs. ' + formatVal(v)
}

function formatDate(d) {
  if (!d) return ''
  return new Date(d).toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

function volver() {
  router.visit(route('periodos.index', { empresa: periodo?.empresa_id }))
}

async function exportarExcel() {
  try {
    const res = await axios.post(route('flujo-efectivo.exportar-excel'), { periodo_id: periodo.id })
    if (res.data.download_url) window.open(res.data.download_url, '_blank')
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message))
  }
}

async function exportarPdf() {
  try {
    const res = await axios.post(route('flujo-efectivo.exportar-pdf'), { periodo_id: periodo.id }, { responseType: 'blob' })
    const blob = new Blob([res.data], { type: 'application/pdf' })
    window.open(URL.createObjectURL(blob), '_blank')
  } catch (e) {
    alert('Error: ' + (e.response?.data?.message || e.message))
  }
}

onMounted(() => { fechaGeneracion.value = new Date().toLocaleString('es-ES') })
</script>
