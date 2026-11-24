<template>
  <AppLayoutAdmin>
    <Head title="Estado de Resultados - Formulario" />

    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-6">Estado de Resultados</h1>

      <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-xl font-semibold mb-4">Período: {{ periodoActual.nombre }}</h2>
        <p class="text-gray-600 mb-4">
          Rango: {{ formatDateDisplay(periodoActual.fecha_inicio) }} al {{ formatDateDisplay(periodoActual.fecha_cierre) }}
        </p>

        <form @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
              <label class="block text-sm font-medium text-gray-700">Fecha inicio (opcional)</label>
              <input
                type="date"
                v-model="form.fecha_inicio"
                :min="minMaxDates.inicio"
                :max="minMaxDates.fin"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
              <div v-if="errors.fecha_inicio" class="text-red-500 text-xs mt-1">{{ errors.fecha_inicio }}</div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Fecha fin (opcional)</label>
              <input
                type="date"
                v-model="form.fecha_fin"
                :min="minMaxDates.inicio"
                :max="minMaxDates.fin"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
              >
              <div v-if="errors.fecha_fin" class="text-red-500 text-xs mt-1">{{ errors.fecha_fin }}</div>
            </div>
          </div>

          <div class="flex justify-end">
            <button
              type="submit"
              :disabled="loading"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ loading ? 'Generando...' : 'Generar Reporte' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { Head, router, usePage } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const { props } = usePage()

const periodoActual = computed(() => props.periodoActual || {})

// CORRECCIÓN: extraer solo YYYY-MM-DD para los inputs min/max
const minMaxDates = computed(() => {
  const limpiar = (d) => {
    if (!d) return ''
    // Quita hora si viene: "2026-01-01 00:00:00" o "2026-01-01T00:00:00.000000Z"
    return String(d).split(' ')[0].split('T')[0]
  }
  return {
    inicio: limpiar(periodoActual.value.fecha_inicio),
    fin: limpiar(periodoActual.value.fecha_cierre)
  }
})

const form = reactive({
  fecha_inicio: '',
  fecha_fin: ''
})

const errors = reactive({})
const loading = ref(false)

// CORRECCIÓN: limpia la hora antes de formatear para pantalla
function formatDateDisplay(dateString) {
  if (!dateString) return ''
  const soloFecha = String(dateString).split(' ')[0].split('T')[0]
  const [year, month, day] = soloFecha.split('-')
  if (!day || !month || !year) return dateString
  return `${day}/${month}/${year}`
}

function submitForm() {
  loading.value = true
  Object.keys(errors).forEach(k => delete errors[k])

  router.post(route('estado-resultados.generar', { periodo: periodoActual.value.id }), form, {
    preserveState: true,
    onError: (err) => {
      if (err.errors) Object.assign(errors, err.errors)
    },
    // CORRECCIÓN CLAVE: onFinish se ejecuta SIEMPRE (éxito o error)
    // así el botón no se queda en "Generando..." para siempre
    onFinish: () => {
      loading.value = false
    }
  })
}
</script>
