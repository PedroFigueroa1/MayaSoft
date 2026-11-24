<template>
  <div v-if="actual" class="relative" ref="raiz">
    <!-- Una sola empresa: solo el nombre, sin menú -->
    <div v-if="lista.length <= 1" class="px-3 py-1.5 text-sm text-gray-600">
      {{ actual.nombre }}
    </div>

    <template v-else>
      <button type="button" @click="abierto = !abierto"
        class="flex items-center gap-2 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100 rounded-lg transition">
        <span class="w-6 h-6 rounded bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">
          {{ actual.nombre?.[0]?.toUpperCase() }}
        </span>
        <span class="max-w-[160px] truncate">{{ actual.nombre }}</span>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
      </button>

      <div v-if="abierto"
        class="absolute right-0 mt-1 w-64 bg-white border border-gray-200 rounded-xl shadow-lg z-50 py-1">
        <div class="px-3 py-1.5 text-[11px] uppercase tracking-wide text-gray-400">Cambiar de empresa</div>
        <button v-for="e in lista" :key="e.id" type="button" @click="cambiar(e)"
          class="w-full text-left px-3 py-2 text-sm hover:bg-gray-50 flex items-center justify-between"
          :class="e.id === actual.id ? 'text-blue-700 font-medium' : 'text-gray-700'">
          <span class="truncate">{{ e.nombre }}</span>
          <span v-if="e.id === actual.id" class="text-xs">✓</span>
        </button>
      </div>
    </template>
  </div>
</template>

<script>
import { router } from '@inertiajs/vue3'

// Uso: dentro del header de AppLayoutAdmin →  <EmpresaSwitcher />
// Lee las props compartidas por HandleInertiaRequests (empresaActual, empresasUsuario).
export default {
  data() { return { abierto: false } },
  computed: {
    actual() { return this.$page.props.empresaActual },
    lista()  { return this.$page.props.empresasUsuario ?? [] },
  },
  mounted()       { document.addEventListener('click', this.fuera) },
  beforeUnmount() { document.removeEventListener('click', this.fuera) },
  methods: {
    fuera(e) { if (this.$refs.raiz && !this.$refs.raiz.contains(e.target)) this.abierto = false },
    cambiar(e) {
      if (e.id === this.actual.id) { this.abierto = false; return }
      if (!confirm(`¿Cambiar a «${e.nombre}»? Volverás al inicio.`)) return
      router.post(route('empresas.cambiar'), { empresa_id: e.id })
    },
  },
}
</script>
