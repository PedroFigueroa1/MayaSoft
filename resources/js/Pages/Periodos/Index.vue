<template>
  <AppLayoutAdmin>
    <!-- Selector de Periodo - Versión compacta -->
    <div class="bg-white rounded-lg shadow mb-6">
      <div class="p-4 border-b border-gray-200">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center space-x-3">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                  Periodo Contable Actual
                </label>
                <select
                  v-model="periodoSeleccionadoId"
                  @change="cambiarPeriodo"
                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                >
                  <option value="">Selecciona un periodo...</option>
                  <option
                    v-for="periodo in periodos.data"
                    :key="periodo.id"
                    :value="periodo.id"
                    :disabled="periodo.estado === 'C'"
                  >
                    {{ periodo.nombre }} - {{ periodo.empresa.nombre }}
                    ({{ periodo.fecha_inicio_formateada }} al {{ periodo.fecha_cierre_formateada }})
                    {{ periodo.estado === 'C' ? ' - CERRADO' : '' }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="(hasRole('SUPERADMIN') || hasRole('ADMIN'))" class="flex-shrink-0">
            <Link
                href="/centros-costo"
                class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-gray-600 text-xs font-medium rounded hover:bg-gray-100 hover:text-gray-800 transition"
            >
                <svg class="h-3 w-3 mr-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18"></path>
                </svg>
                Centro de costos
            </Link>
            <Link
              href="/cuentas"
              class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-semibold rounded-lg hover:bg-purple-700 transition"
            >
              <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
              </svg>
              Plan de Cuentas
            </Link>
            <Link
              href="/periodos/create"
              class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition"
            >
              <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
              </svg>
              Nuevo Periodo
            </Link>
          </div>
        </div>
      </div>

      <!-- Info del periodo actual (si está seleccionado) -->
      <div v-if="periodoActual" class="p-4 bg-gray-50">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center space-x-4">
            <span :class="`px-3 py-1 rounded-full text-sm font-medium ${periodoActual.estado === 'A' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`">
              {{ estados[periodoActual.estado] }}
            </span>
            <div class="text-sm text-gray-600">
              <span class="font-medium">{{ periodoActual.empresa.nombre }}</span>
              <span class="mx-2">•</span>
              <span>{{ periodoActual.fecha_inicio_formateada }} al {{ periodoActual.fecha_cierre_formateada }}</span>
            </div>
          </div>

          <div class="flex items-center space-x-2">
            <Link
              :href="`/periodos/${periodoActual.id}`"
              class="text-sm text-blue-600 hover:text-blue-800"
            >
              Ver detalles
            </Link>
            <span class="text-gray-300">|</span>
            <Link
              v-if="(hasRole('SUPERADMIN') || hasRole('ADMIN')) && periodoActual.estado === 'A'"
              :href="`/periodos/${periodoActual.id}/edit`"
              class="text-sm text-yellow-600 hover:text-yellow-800"
            >
              Editar
            </Link>
            <span v-if="(hasRole('SUPERADMIN') || hasRole('ADMIN')) && periodoActual.estado === 'A'" class="text-gray-300">|</span>
            <button
              v-if="(hasRole('SUPERADMIN') || hasRole('CONTADOR')) && periodoActual.estado === 'A'"
              @click="confirmCerrar(periodoActual)"
              class="text-sm text-green-600 hover:text-green-800"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Contenido Principal - SOLO si hay periodo seleccionado -->
    <div v-if="periodoActual" class="space-y-6">
      <!-- Sección 1: Libros Contables -->
      <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="h-6 w-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
          </svg>
          Libros Contables
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Asientos Contables -->
          <!-- Asientos Contables - CORREGIDO -->
<div class="dashboard-card group hover:border-blue-500 hover:shadow-lg transition-all duration-200">
    <Link :href="`/asientos/${periodoActual.id}`" class="block">
        <div class="dashboard-card-icon bg-blue-50">
            <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
        </div>
        <h4 class="dashboard-card-title">Asientos Contables</h4>
        <p class="dashboard-card-desc">Crear y gestionar asientos</p>
    </Link>

    <div class="mt-3 flex gap-2">
        <Link
            :href="`/asientos/create?periodo_id=${periodoActual.id}`"
            class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition"
        >
            <svg class="h-3 w-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Nuevo Asiento
        </Link>

    </div>
</div>
          <!-- Libro Diario -->
          <Link
            :href="`/libro-diario/${periodoActual.id}`"
            class="dashboard-card group hover:border-blue-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-blue-50">
              <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Libro Diario</h4>
            <p class="dashboard-card-desc">Registro cronológico de todas las transacciones</p>
            <div class="mt-3 text-xs text-blue-600 font-medium">
              Ver libro completo →
            </div>
          </Link>
        <!-- Libro Mayor -->
          <Link
            :href="`/libro-mayor/${periodoActual.id}`"
            class="dashboard-card group hover:border-blue-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-blue-50">
              <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Libro Mayor</h4>
            <p class="dashboard-card-desc">Cuentas individuales detalladas</p>
            <div class="mt-3 text-xs text-blue-600 font-medium">
              Ver mayor general →
            </div>
          </Link>

          <!-- Sumas y Saldos -->
          <Link
            :href="`/sumas-saldos/periodo/${periodoActual.id}`"
            class="dashboard-card group hover:border-blue-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-blue-50">
              <svg class="h-10 w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Sumas y Saldos</h4>
            <p class="dashboard-card-desc">Balance de comprobación</p>
            <div class="mt-3 text-xs text-blue-600 font-medium">
              Ver reporte →
            </div>
          </Link>

        </div>
      </div>

      <!-- Sección 2: Estados Financieros -->
      <div>
        <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
          <svg class="h-6 w-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
          </svg>
          Estados Financieros (en desarrollo)
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Balance General -->
          <Link
            :href="`/balance-general/generar/${periodoActual.id}`"
            class="dashboard-card group hover:border-green-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-green-50">
              <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Balance General</h4>
            <p class="dashboard-card-desc">Situación financiera de la empresa</p>
            <div class="mt-3 text-xs text-green-600 font-medium">
              Generar reporte →
            </div>
          </Link>

          <!-- Estado de Resultados -->
          <Link
            :href="route('estado-resultados.index', periodoActual.id)"
            class="dashboard-card group hover:border-green-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-green-50">
              <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Estado de Resultados</h4>
            <p class="dashboard-card-desc">Resultados del ejercicio económico</p>
            <div class="mt-3 text-xs text-green-600 font-medium">
              Ver resultados →
            </div>
          </Link>

          <!-- Flujo de Efectivo -->
          <Link
            :href="`/flujo-efectivo/${periodoActual.id}`"
            class="dashboard-card group hover:border-green-500 hover:shadow-lg transition-all duration-200"
          >
            <div class="dashboard-card-icon bg-green-50">
              <svg class="h-10 w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
            <h4 class="dashboard-card-title">Flujo de Efectivo</h4>
            <p class="dashboard-card-desc">Movimientos de efectivo del periodo</p>
            <div class="mt-3 text-xs text-green-600 font-medium">
              Ver análisis →
            </div>
          </Link>
          <Link
            :href="`/evolucion-patrimonio/${periodoActual.id}`"
            class="dashboard-card group hover:border-purple-500 hover:shadow-lg transition-all duration-200"
            >
            <div class="dashboard-card-icon bg-purple-50">
                <svg class="h-10 w-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M5 12h14M12 3v2m0 14v2" />
                </svg>
            </div>

            <h4 class="dashboard-card-title">Estado de Evolución del Patrimonio</h4>

            <p class="dashboard-card-desc">
                Cambios en el patrimonio durante el periodo
            </p>

            <div class="mt-3 text-xs text-purple-600 font-medium">
                Ver análisis →
            </div>
            </Link>
            <Link
    :href="`/compras-libro/importaciones/${periodoActual.id}`"
    class="dashboard-card group hover:border-indigo-500 hover:shadow-lg transition-all duration-200"
>
    <div class="dashboard-card-icon bg-indigo-50">
        <svg class="h-10 w-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
        </svg>
    </div>

    <h4 class="dashboard-card-title">Importaciones de Compras</h4>
    <p class="dashboard-card-desc">
        Administrar importaciones del libro de compras
    </p>
    <div class="mt-3 text-xs text-indigo-600 font-medium">
        Ir a importaciones →
    </div>
</Link>

<Link
    :href="`/ventas-libro/importaciones/${periodoActual.id}`"
    class="dashboard-card group hover:border-emerald-500 hover:shadow-lg transition-all duration-200"
>
    <div class="dashboard-card-icon bg-emerald-50">
        <svg class="h-10 w-10 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
        </svg>
    </div>

    <h4 class="dashboard-card-title">Importaciones de Ventas</h4>
    <p class="dashboard-card-desc">
        Administrar importaciones del libro de ventas
    </p>
    <div class="mt-3 text-xs text-emerald-600 font-medium">
        Ir a importaciones →
    </div>
</Link>
        </div>
      </div>

      <!-- Sección 3: Últimos Asientos (Opcional) -->
      <div v-if="periodoActual.ultimos_asientos && periodoActual.ultimos_asientos.length > 0"
           class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
          <h3 class="font-semibold text-gray-800 flex items-center">
            <svg class="h-5 w-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Últimos Asientos
          </h3>
          <Link
            :href="`/asientos/${periodoActual.id}`"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium"
          >
            Ver todos →
          </Link>
        </div>
        <div class="px-6 py-4">
          <div class="space-y-2">
            <div
              v-for="asiento in periodoActual.ultimos_asientos.slice(0, 5)"
              :key="asiento.id"
              class="p-3 hover:bg-gray-50 rounded-lg transition-colors"
            >
              <div class="flex justify-between items-center">
                <div>
                  <p class="font-medium text-gray-800">{{ asiento.descripcion || 'Sin descripción' }}</p>
                  <div class="flex items-center text-sm text-gray-500 mt-1">
                    <span class="bg-gray-100 px-2 py-0.5 rounded mr-3">{{ asiento.numero_asiento }}</span>
                    <span>{{ formatDate(asiento.fecha) }}</span>
                  </div>
                </div>
                <div class="text-right">
                  <div class="text-sm font-medium text-gray-800">
                    {{ asiento.debe_total }} / {{ asiento.haber_total }}
                  </div>
                  <span class="text-xs text-gray-400">{{ asiento.tipo }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Mensaje cuando no hay periodo seleccionado -->
    <div v-else class="bg-white rounded-lg shadow p-8">
      <div class="text-center">
        <svg class="h-20 w-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <h3 class="text-lg font-semibold text-gray-700 mb-2">Selecciona un Periodo</h3>
        <p class="text-gray-500 mb-4 max-w-md mx-auto">
          Para acceder a los libros contables y estados financieros, primero selecciona un periodo contable del menú superior.
        </p>

        <div v-if="periodos.data.length === 0 && (hasRole('SUPERADMIN') || hasRole('ADMIN'))" class="mt-6">
          <p class="text-gray-500 mb-4">No hay periodos creados aún.</p>
          <Link
            href="/periodos/create"
            class="inline-flex items-center px-5 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition"
          >
            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear Primer Periodo
          </Link>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from "@/Layouts/AppLayoutAdmin.vue";
import { Link, router } from "@inertiajs/vue3";
import { ref, computed, onMounted, watch } from "vue";
import { formatDate } from "@/Utils/format";
import useAuth from "@/Composables/useAuth";

const { hasRole } = useAuth();

const props = defineProps({
  periodos: Object,
  filters: Object,
  estados: Object,
});

// Estado del periodo seleccionado
const periodoSeleccionadoId = ref('');

// Buscar el periodo actual (usando el que viene en las props o del localStorage)
const periodoActual = computed(() => {
  if (!periodoSeleccionadoId.value) return null;
  const periodo = props.periodos.data.find(p => p.id == periodoSeleccionadoId.value);

  // Si no encontramos el periodo en la lista, limpiar la selección
  if (!periodo) {
    periodoSeleccionadoId.value = '';
    localStorage.removeItem('periodo_seleccionado');
    return null;
  }

  return periodo;
});

// Cargar periodo seleccionado al montar
onMounted(() => {
  // Intentar cargar del localStorage primero
  const periodoGuardado = localStorage.getItem('periodo_seleccionado');

  // Verificar si el periodo guardado existe en la lista
  if (periodoGuardado) {
    const periodoExiste = props.periodos.data.some(p => p.id == periodoGuardado);
    if (periodoExiste) {
      periodoSeleccionadoId.value = periodoGuardado;
    } else {
      // Limpiar si no existe
      localStorage.removeItem('periodo_seleccionado');
    }
  }

  // Si no hay periodo guardado pero hay periodos disponibles, seleccionar el primero abierto
  if (!periodoSeleccionadoId.value && props.periodos.data.length > 0) {
    const primerPeriodoAbierto = props.periodos.data.find(p => p.estado === 'A');
    if (primerPeriodoAbierto) {
      periodoSeleccionadoId.value = primerPeriodoAbierto.id;
      localStorage.setItem('periodo_seleccionado', primerPeriodoAbierto.id);
    }
  }
});

// Función para cambiar de periodo
const cambiarPeriodo = () => {
  if (periodoSeleccionadoId.value) {
    // Guardar en localStorage
    localStorage.setItem('periodo_seleccionado', periodoSeleccionadoId.value);

    // Si hay un error 404, probablemente necesitas recargar la página
    // o actualizar el estado del periodo seleccionado
    router.reload({
      preserveScroll: true,
      only: ['periodos', 'filters'],
      data: { periodo_id: periodoSeleccionadoId.value }
    });
  }
};

// Función de confirmación para cerrar periodo
const confirmCerrar = (periodo) => {
  if (!confirm(`¿Estás seguro de cerrar el periodo "${periodo.nombre}"? Una vez cerrado no podrá ser editado.`)) {
    return;
  }

  router.post(`/periodos/${periodo.id}/cerrar`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Recargar para actualizar el estado
      router.reload({ preserveScroll: true });
    }
  });
};

// Función para eliminar periodo
const confirmDelete = (periodo) => {
  if (!confirm(`¿Eliminar el periodo "${periodo.nombre}"? Esta acción no se puede deshacer.`)) {
    return;
  }

  router.delete(`/periodos/${periodo.id}`, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      // Limpiar la selección si eliminamos el periodo seleccionado
      if (periodoSeleccionadoId.value == periodo.id) {
        periodoSeleccionadoId.value = '';
        localStorage.removeItem('periodo_seleccionado');
      }
      router.reload({ preserveScroll: true });
    }
  });
};
</script>

<style scoped>
.dashboard-card {
  @apply bg-white rounded-xl border border-gray-200 p-5 flex flex-col hover:shadow-lg transition-all duration-300;
}

.dashboard-card:hover {
  transform: translateY(-2px);
}

.dashboard-card-icon {
  @apply p-3 rounded-lg mb-4 w-16 h-16 flex items-center justify-center;
}

.dashboard-card-title {
  @apply text-lg font-semibold text-gray-800 mb-2;
}

.dashboard-card-desc {
  @apply text-sm text-gray-500 mb-3 flex-grow;
}

/* Ajustes responsivos */
@media (max-width: 768px) {
  .dashboard-card {
    @apply p-4;
  }

  .dashboard-card-icon {
    @apply w-14 h-14 mb-3;
  }

  .dashboard-card-title {
    @apply text-base;
  }
}
</style>
