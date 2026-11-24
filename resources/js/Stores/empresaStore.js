import { defineStore } from 'pinia'

export const useEmpresaStore = defineStore('empresa', {
  state: () => ({
    selectedEmpresa: null
  }),

  getters: {
    hasSelectedEmpresa: (state) => !!state.selectedEmpresa,
    currentEmpresaId: (state) => state.selectedEmpresa?.id,
    currentEmpresaNombre: (state) => state.selectedEmpresa?.nombre,
    currentEmpresa: (state) => state.selectedEmpresa
  },

  actions: {
    setSelectedEmpresa(empresa) {
      this.selectedEmpresa = empresa
      // Guardar en localStorage para persistencia
      localStorage.setItem('selectedEmpresa', JSON.stringify(empresa))
    },

    loadSelectedEmpresa() {
      const saved = localStorage.getItem('selectedEmpresa')
      if (saved) {
        this.selectedEmpresa = JSON.parse(saved)
      }
    },

    clearSelectedEmpresa() {
      this.selectedEmpresa = null
      localStorage.removeItem('selectedEmpresa')
    }
  }
})
