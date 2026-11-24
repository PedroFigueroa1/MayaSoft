<template>
  <!-- Sin AppLayoutAdmin a propósito: ese layout puede necesitar una empresa activa -->
  <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
      <div class="text-center mb-6">
        <h1 class="text-xl font-semibold text-gray-800">¿En qué empresa vas a trabajar?</h1>
        <p class="text-sm text-gray-500 mt-1">Puedes cambiar de empresa en cualquier momento desde el menú superior.</p>
      </div>

      <div class="space-y-2">
        <button v-for="e in empresas" :key="e.id" type="button" @click="elegir(e)" :disabled="cargando !== null"
          class="w-full flex items-center gap-3 p-4 bg-white border rounded-xl shadow-sm text-left transition hover:border-blue-400 hover:shadow disabled:opacity-60"
          :class="e.id === actual ? 'border-blue-400 ring-1 ring-blue-200' : 'border-gray-200'">
          <div class="w-11 h-11 shrink-0 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center font-bold">
            {{ iniciales(e.nombre) }}
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-sm font-semibold text-gray-800 truncate">{{ e.nombre }}</div>
            <div class="text-xs text-gray-500">NIT {{ e.nit }}<span v-if="e.ciudad"> · {{ e.ciudad }}</span></div>
          </div>
          <span v-if="cargando === e.id" class="text-xs text-gray-400">Entrando...</span>
          <span v-else-if="e.id === actual" class="text-xs text-blue-600 font-medium">Actual</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

export default {
  props: {
    empresas: { type: Array, default: () => [] },
    actual:   { type: [Number, null], default: null },
  },
  data() { return { cargando: null } },
  methods: {
    elegir(e) {
      this.cargando = e.id
      router.post(route('empresas.cambiar'), { empresa_id: e.id }, {
        onFinish: () => { this.cargando = null },
      })
    },
    iniciales(n) {
      return (n || '?').split(/\s+/).slice(0, 2).map(p => p[0]).join('').toUpperCase()
    },
  },
}
</script>
