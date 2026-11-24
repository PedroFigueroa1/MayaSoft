<template>
    <AppLayoutAdmin>
        <div class="container mx-auto p-4">
          <h1 class="text-2xl font-bold mb-4">Libro Mayor - Sistema Contable</h1>

          <!-- Tarjeta principal -->
          <div class="bg-white shadow-md rounded-lg p-6">
            <!-- Información compacta de empresa y período -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6 pb-4 border-b border-gray-200">
              <!-- Bloque empresa -->
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 text-blue-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Empresa</p>
                  <p class="font-bold text-gray-800">{{ empresa.nombre }}</p>
                  <p class="text-sm text-gray-600" v-if="empresa.ruc">RUC: {{ empresa.ruc }}</p>
                  <p class="text-sm text-gray-500" v-if="empresa.direccion">{{ empresa.direccion }}</p>
                </div>
              </div>

              <!-- Bloque período fijo -->
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 text-blue-600">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                </div>
                <div>
                  <p class="text-xs text-gray-500 uppercase tracking-wide">Período Contable</p>
                  <p class="font-bold text-gray-800">{{ periodoFijo.nombre }}</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(periodoFijo.fecha_inicio) }} al {{ formatDate(periodoFijo.fecha_cierre) }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Formulario de filtros -->
            <form @submit.prevent="submitForm">
              <!-- Buscador de cuenta (ocupa ancho completo) -->
              <div class="mb-6 relative">
                <label class="block text-gray-700 text-sm font-bold mb-2">
                  Cuenta *
                </label>
                <div class="relative">
                  <input
                    type="text"
                    v-model="search"
                    @focus="showDropdown = true"
                    @input="filterCuentas"
                    placeholder="Buscar por código o nombre de cuenta..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    :disabled="loading"
                  />
                  <div
                    v-if="showDropdown"
                    class="absolute z-50 w-full bg-white border rounded shadow max-h-60 overflow-y-auto mt-1"
                  >
                    <div
                      v-for="cuenta in cuentasFiltradas"
                      :key="cuenta.id"
                      @click="seleccionarCuenta(cuenta)"
                      class="px-3 py-2 hover:bg-gray-100 cursor-pointer"
                    >
                      <div class="font-medium">{{ cuenta.codigo }}</div>
                      <div class="text-sm text-gray-600">{{ cuenta.nombre }}</div>
                    </div>
                    <div v-if="cuentasFiltradas.length === 0" class="px-3 py-2 text-gray-500">
                      No se encontraron cuentas
                    </div>
                  </div>
                </div>
                <div v-if="errors.cuenta_id" class="text-red-500 text-xs mt-1">
                  {{ errors.cuenta_id }}
                </div>
              </div>

              <!-- Fechas (opcionales) en una fila -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                  <label for="fecha_inicio" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Inicio (opcional)
                  </label>
                  <input
                    type="date"
                    v-model="form.fecha_inicio"
                    id="fecha_inicio"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    :disabled="loading"
                    :min="periodoFijo.fecha_inicio"
                    :max="periodoFijo.fecha_cierre"
                  />
                  <div v-if="errors.fecha_inicio" class="text-red-500 text-xs mt-1">{{ errors.fecha_inicio }}</div>
                </div>
                <div>
                  <label for="fecha_fin" class="block text-sm font-medium text-gray-700 mb-1">
                    Fecha Fin (opcional)
                  </label>
                  <input
                    type="date"
                    v-model="form.fecha_fin"
                    id="fecha_fin"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    :disabled="loading"
                    :min="periodoFijo.fecha_inicio"
                    :max="periodoFijo.fecha_cierre"
                  />
                  <div v-if="errors.fecha_fin" class="text-red-500 text-xs mt-1">{{ errors.fecha_fin }}</div>
                </div>
              </div>

              <!-- Botones de acción -->
              <div class="flex flex-wrap gap-3">
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
                  :disabled="loading || !form.cuenta_id"
                >
                  <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ loading ? 'Generando...' : 'Generar Reporte' }}</span>
                </button>

                <button
                  type="button"
                  @click="exportarExcelPlaceholder"
                  class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
                  :disabled="loading"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  Exportar Excel
                </button>

                <button
                  type="button"
                  @click="exportarPdfPlaceholder"
                  class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 disabled:opacity-50"
                  :disabled="loading"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                  </svg>
                  Exportar PDF
                </button>
              </div>
            </form>

            <!-- Mensajes de respuesta -->
            <div v-if="message" class="mt-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
              <span class="block sm:inline">{{ message }}</span>
            </div>
            <div v-if="error" class="mt-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
              <span class="block sm:inline">{{ error }}</span>
            </div>
          </div>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { ref, reactive, onMounted, onBeforeUnmount, computed, watch } from "vue";
import { usePage, router } from "@inertiajs/vue3";

const { props } = usePage();

// Datos recibidos
const periodos = ref(props.periodos || []);
const cuentas = ref(props.cuentas || []);
const periodoSeleccionado = ref(props.periodoSeleccionado || props.periodo || null);
const empresa = ref(props.empresa || {});

// Estado local
const loading = ref(false);
const message = ref("");
const error = ref("");
const search = ref('');
const showDropdown = ref(false);
const errors = reactive({});

// Período fijo (el primero o el seleccionado)
const periodoFijo = computed(() => {
  if (periodoSeleccionado.value) return periodoSeleccionado.value;
  if (periodos.value.length > 0) return periodos.value[0];
  return { id: "", nombre: "No hay períodos", fecha_inicio: "", fecha_cierre: "" };
});

// Función para formatear fecha a YYYY-MM-DD (compatible con input type="date")
function formatDateForInput(dateString) {
  if (!dateString) return "";
  // Si ya es YYYY-MM-DD, devolverlo
  if (/^\d{4}-\d{2}-\d{2}$/.test(dateString)) return dateString;
  // Intentar convertir cualquier formato válido
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "";
  return date.toISOString().split('T')[0];
}

// Formulario
const form = reactive({
  periodo_id: periodoFijo.value.id,
  cuenta_id: props.filters?.cuenta_id || "",
  fecha_inicio: "",
  fecha_fin: "",
});

// Bandera para saber si el usuario ha modificado manualmente las fechas
// (para no sobreescribirlas automáticamente)
const userModifiedDates = ref(false);

// Watcher: cuando el período fijo esté completamente cargado y tenga fechas,
// asignar automáticamente las fechas si no vienen de filtros y el usuario no las ha modificado
watch(
  periodoFijo,
  (nuevoPeriodo) => {
    if (!nuevoPeriodo.fecha_inicio || !nuevoPeriodo.fecha_cierre) return;

    // Si existen filtros de fecha provenientes de una generación anterior, respetarlos
    if (props.filters?.fecha_inicio && props.filters?.fecha_fin) {
      form.fecha_inicio = props.filters.fecha_inicio;
      form.fecha_fin = props.filters.fecha_fin;
      return;
    }

    // Si el usuario ya modificó las fechas manualmente, no sobreescribir
    if (userModifiedDates.value) return;

    // Asignar las fechas del período
    form.fecha_inicio = formatDateForInput(nuevoPeriodo.fecha_inicio);
    form.fecha_fin = formatDateForInput(nuevoPeriodo.fecha_cierre);
  },
  { immediate: true, deep: true }
);

// Detectar cuando el usuario cambia las fechas para activar la bandera
function onFechaInicioChange() {
  userModifiedDates.value = true;
}
function onFechaFinChange() {
  userModifiedDates.value = true;
}

// Filtrado de cuentas
const cuentasFiltradas = computed(() => {
  if (!search.value) return cuentas.value;
  const term = search.value.toLowerCase();
  return cuentas.value.filter(cuenta =>
    cuenta.codigo.toLowerCase().includes(term) ||
    cuenta.nombre.toLowerCase().includes(term)
  );
});

// Métodos del dropdown
function filterCuentas() { showDropdown.value = true; }
function seleccionarCuenta(cuenta) {
  form.cuenta_id = cuenta.id;
  search.value = `${cuenta.codigo} - ${cuenta.nombre}`;
  showDropdown.value = false;
}
function cerrarDropdown(event) {
  const dropdownContainer = document.querySelector('.relative');
  if (dropdownContainer && !dropdownContainer.contains(event.target)) {
    showDropdown.value = false;
  }
}

// Formateo de fecha para mostrar en texto legible
function formatDate(dateString) {
  if (!dateString) return "";
  const date = new Date(dateString);
  if (isNaN(date.getTime())) return "";
  return date.toLocaleDateString("es-ES");
}

// Envío del formulario para generar reporte
function submitForm() {
  loading.value = true;
  error.value = "";
  message.value = "";
  form.periodo_id = periodoFijo.value.id;

  Object.keys(errors).forEach(key => delete errors[key]);

  router.post(route("libro-mayor.generar"), form, {
    preserveState: true,
    onSuccess: page => {
      loading.value = false;
      if (page.props.flash?.success) message.value = page.props.flash.success;
    },
    onError: err => {
      loading.value = false;
      if (err.errors) Object.assign(errors, err.errors);
      if (err.message) error.value = err.message;
      else if (page.props.flash?.error) error.value = page.props.flash.error;
    },
  });
}

// Placeholders para exportación
function exportarExcelPlaceholder() {
  alert("Funcionalidad de exportación a Excel en desarrollo.");
  console.log("[Placeholder] Exportar Excel - parámetros:", {
    periodo_id: periodoFijo.value.id,
    cuenta_id: form.cuenta_id,
    fecha_inicio: form.fecha_inicio,
    fecha_fin: form.fecha_fin
  });
}
function exportarPdfPlaceholder() {
  alert("Funcionalidad de exportación a PDF en desarrollo.");
  console.log("[Placeholder] Exportar PDF - parámetros:", {
    periodo_id: periodoFijo.value.id,
    cuenta_id: form.cuenta_id,
    fecha_inicio: form.fecha_inicio,
    fecha_fin: form.fecha_fin
  });
}

// Inicialización
onMounted(() => {
  form.periodo_id = periodoFijo.value.id;

  // Si ya hay una cuenta seleccionada en los filtros, mostrarla en el buscador
  if (props.filters?.cuenta_id) {
    const cuentaSeleccionada = cuentas.value.find(c => c.id == props.filters.cuenta_id);
    if (cuentaSeleccionada) {
      search.value = `${cuentaSeleccionada.codigo} - ${cuentaSeleccionada.nombre}`;
    }
  }

  document.addEventListener('click', cerrarDropdown);

  // Generar automáticamente si ya venía una cuenta seleccionada
  if (props.filters?.cuenta_id) {
    submitForm();
  }
});

onBeforeUnmount(() => {
  document.removeEventListener('click', cerrarDropdown);
});
</script>
