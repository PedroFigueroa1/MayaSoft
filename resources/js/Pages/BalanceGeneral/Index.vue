<template>
  <AppLayoutAdmin>
    <Head title="Balance General" />

    <div class="container mx-auto p-4">
      <h1 class="text-2xl font-bold mb-6">Balance General</h1>

      <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-xl font-semibold mb-4">Seleccionar parámetros</h2>

        <form @submit.prevent="submitForm">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

            <!-- Período -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">
                Período Contable *
              </label>
              <select
                v-model="form.periodo_id"
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                required
                :disabled="loading"
              >
                <option value="">Seleccionar período</option>
                <option v-for="periodo in periodos" :key="periodo.id" :value="periodo.id">
                  {{ periodo.nombre }} ({{ formatDate(periodo.fecha_inicio) }} al {{ formatDate(periodo.fecha_cierre) }})
                </option>
              </select>
              <div v-if="errors.periodo_id" class="text-red-500 text-xs mt-1">
                {{ errors.periodo_id }}
              </div>
            </div>

            <!-- Filtro por nivel -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Nivel de detalle
              </label>
              <div class="flex gap-2 flex-wrap">
                <button
                  v-for="n in niveles"
                  :key="n.value"
                  type="button"
                  @click="form.nivel_max = n.value"
                  :class="form.nivel_max === n.value
                    ? 'bg-blue-600 text-white border-blue-600'
                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                  class="px-3 py-1.5 rounded-lg text-sm font-medium border transition"
                >
                  {{ n.label }}
                </button>
              </div>
              <p class="text-xs text-gray-400 mt-1">
                {{ descripcionNivel }}
              </p>
            </div>

          </div>

          <!-- Botones -->
          <div class="flex gap-3 flex-wrap">
            <button
              type="submit"
              :disabled="loading"
              class="inline-flex items-center px-4 py-2 bg-blue-600 rounded-md font-semibold text-white hover:bg-blue-700 disabled:opacity-50 transition"
            >
              <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              {{ loading ? 'Generando...' : 'Generar Reporte' }}
            </button>

            <button
              type="button"
              @click="exportarPdf"
              :disabled="loading || !form.periodo_id"
              class="inline-flex items-center px-4 py-2 bg-green-600 rounded-md font-semibold text-white hover:bg-green-700 disabled:opacity-50 transition"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              Exportar PDF
            </button>
          </div>
        </form>
      </div>

      <!-- Mensajes -->
      <div v-if="message" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        {{ message }}
      </div>
      <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        {{ error }}
      </div>

    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const { props } = usePage()

const periodos = ref(props.periodos || [])
const loading  = ref(false)
const message  = ref('')
const error    = ref('')
const errors   = reactive({})

const form = reactive({
  periodo_id: props.filters?.periodo_id || '',
  nivel_max:  props.filters?.nivel_max  || 5,
})

// Opciones de nivel
const niveles = [
  { value: 1, label: 'Nivel 1 — Solo grandes grupos' },
  { value: 2, label: 'Nivel 2 — Grupos' },
  { value: 3, label: 'Nivel 3 — Subgrupos' },
  { value: 4, label: 'Nivel 4 — Cuentas' },
  { value: 5, label: 'Nivel 5 — Detalle completo' },
]

const descripcionNivel = computed(() => {
  const textos = {
    1: 'Muestra solo Activo, Pasivo y Patrimonio sin desglose',
    2: 'Muestra grupos principales como Activo Corriente, Activo No Corriente...',
    3: 'Muestra subgrupos de cada sección',
    4: 'Muestra cuentas individuales',
    5: 'Muestra el máximo detalle disponible',
  }
  return textos[form.nivel_max] || ''
})

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('es-ES')
}

function submitForm() {
  loading.value = true
  error.value   = ''
  message.value = ''
  Object.keys(errors).forEach(k => delete errors[k])

  router.get(route('balance-general.generar', { periodo: form.periodo_id }), {
    nivel_max: form.nivel_max,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      loading.value = false
      if (page.props.flash?.success) message.value = page.props.flash.success
    },
    onError: (err) => {
      loading.value = false
      if (err.errors) Object.assign(errors, err.errors)
      error.value = err.message || 'Error al generar el reporte'
    },
  })
}

const exportarPdf = async () => {
  if (!form.periodo_id) {
    error.value = 'Por favor seleccione un período primero'
    return
  }

  loading.value = true
  error.value   = ''
  message.value = ''

  try {
    const response = await fetch(route('balance-general.exportar-pdf'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: JSON.stringify({
        periodo_id: form.periodo_id,
        nivel_max:  form.nivel_max,
      }),
    })

    const data = await response.json()

    if (data.download_url) {
      window.open(data.download_url, '_blank')
      message.value = 'PDF generado correctamente'
    }
  } catch (err) {
    error.value = 'Error al exportar el PDF'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  if (props.filters?.periodo_id) submitForm()
})
</script>
