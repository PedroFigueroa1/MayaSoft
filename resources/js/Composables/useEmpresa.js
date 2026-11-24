import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'

// Estado global fuera del composable para que persista
const selectedEmpresa = ref(null)

export function useEmpresa() {
  // Cargar empresa desde localStorage al iniciar
  const loadFromStorage = () => {
    if (!selectedEmpresa.value) {
      const saved = localStorage.getItem('selectedEmpresa')
      if (saved) {
        selectedEmpresa.value = JSON.parse(saved)
      }
    }
  }

  // Ejecutar carga inicial
  loadFromStorage()

  const setSelectedEmpresa = (empresa) => {
    selectedEmpresa.value = empresa
    localStorage.setItem('selectedEmpresa', JSON.stringify(empresa))
  }

  const clearSelectedEmpresa = () => {
    selectedEmpresa.value = null
    localStorage.removeItem('selectedEmpresa')
  }

  const hasSelectedEmpresa = computed(() => selectedEmpresa.value !== null)
  const currentEmpresaId = computed(() => selectedEmpresa.value?.id)
  const currentEmpresaNombre = computed(() => selectedEmpresa.value?.nombre)
  const currentEmpresa = computed(() => selectedEmpresa.value)

  return {
    selectedEmpresa: computed(() => selectedEmpresa.value),
    setSelectedEmpresa,
    clearSelectedEmpresa,
    hasSelectedEmpresa,
    currentEmpresaId,
    currentEmpresaNombre,
    currentEmpresa
  }
}
