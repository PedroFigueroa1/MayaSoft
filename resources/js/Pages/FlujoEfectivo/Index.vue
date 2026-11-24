<template>
  <AppLayoutAdmin>
    <Head title="Estado de Flujo de Efectivo" />

    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-6">Estado de Flujo de Efectivo - Sistema Contable</h1>

      <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Seleccionar parámetros</h2>

        <form @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="periodo_id" class="block text-sm font-medium text-gray-700 mb-1">Período Contable *</label>
              <select
                v-model="form.periodo_id"
                id="periodo_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
                :disabled="loading"
              >
                <option value="">Seleccionar período</option>
                <option v-for="periodo in periodos" :key="periodo.id" :value="periodo.id">
                  {{ periodo.nombre }} ({{ formatDate(periodo.fecha_inicio) }} al {{ formatDate(periodo.fecha_cierre) }})
                </option>
              </select>
              <div v-if="errors.periodo_id" class="text-red-500 text-xs mt-1">{{ errors.periodo_id }}</div>
            </div>

            <div>
              <label for="informacion" class="block text-sm font-medium text-gray-700 mb-1">Información</label>
              <div class="text-sm text-gray-600 p-2 bg-gray-50 rounded">
                <p>El Estado de Flujo de Efectivo muestra los movimientos de efectivo clasificados en:</p>
                <ul class="list-disc list-inside mt-1">
                  <li>Actividades Operativas</li>
                  <li>Actividades de Inversión</li>
                  <li>Actividades de Financiamiento</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="flex space-x-4">
            <button
              type="submit"
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
              :disabled="loading"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span>{{ loading ? 'Generando...' : 'Generar Reporte' }}</span>
            </button>

            <button
              type="button"
              @click="exportarPdf"
              class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50"
              :disabled="loading || !form.periodo_id"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Exportar PDF
            </button>
          </div>
        </form>
      </div>

      <!-- Mostrar mensaje de éxito o error -->
      <div v-if="message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
        <span class="block sm:inline">{{ message }}</span>
      </div>

      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
        <span class="block sm:inline">{{ error }}</span>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

// Obtener datos de la página a través de las props de Inertia
const { props } = usePage()

// Datos que llegan del controlador PHP
const periodos = ref(props.periodos || [])

// Estado del componente
const loading = ref(false)
const message = ref('')
const error = ref('')

// Formulario reactivo
const form = reactive({
  periodo_id: props.filters?.periodo_id || ''
})

// Errores de validación
const errors = reactive({})

// Función para formatear fechas
function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('es-ES')
}

// Función para enviar el formulario
function submitForm() {
  loading.value = true
  error.value = ''
  message.value = ''

  // Limpiar errores anteriores
  Object.keys(errors).forEach(key => delete errors[key])

  // Usar Inertia para enviar el formulario
  router.post(route('flujo-efectivo.generar'), form, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      loading.value = false
      // Si hay mensajes en la respuesta, mostrarlos
      if (page.props.flash?.success) {
        message.value = page.props.flash.success
      }
    },
    onError: (err) => {
      loading.value = false
      // Capturar errores de validación
      if (err.errors) {
        Object.assign(errors, err.errors)
      }
      // Mostrar mensaje de error general
      if (err.message) {
        error.value = err.message
      } else if (props.flash?.error) {
        error.value = props.flash.error
      }
    }
  })
}

// Función para exportar a PDF
const exportarPdf = async () => {
  if (!form.periodo_id) {
    error.value = 'Por favor seleccione un período primero'
    return
  }

  loading.value = true
  error.value = ''
  message.value = ''

  try {
    const response = await router.post(route('flujo-efectivo.exportar-pdf'), form)

    if (response.props.download_url) {
      // Abrir el PDF en una nueva pestaña
      window.open(response.props.download_url, '_blank')
      message.value = 'PDF generado correctamente'
    }
  } catch (err) {
    console.error('Error al exportar PDF:', err)
    error.value = 'Error al exportar el PDF. Por favor intente nuevamente.'
  } finally {
    loading.value = false
  }
}

// Si hay filtros en las props, enviar el formulario automáticamente
onMounted(() => {
  if (props.filters && props.filters.periodo_id) {
    submitForm()
  }
})
</script>
