<template>
  <div>
    <div
      class="flex justify-between items-center py-0.5 hover:bg-gray-50 rounded text-xs"
      :style="{ paddingLeft: `${nivel * 12}px` }"
    >
      <div class="flex items-center gap-1 min-w-0">
        <button
          v-if="cuenta.hijos && cuenta.hijos.length > 0"
          @click="expandida = !expandida"
          class="text-gray-400 w-3 h-3 flex-shrink-0"
        >
          <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-90': expandida }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
        <span v-else class="w-3 flex-shrink-0"></span>
        <span class="font-mono text-[11px] text-gray-500">{{ cuenta.codigo }}</span>
        <span class="text-gray-700 truncate" :class="{ 'font-medium': !cuenta.permite_movimiento }">
          {{ cuenta.nombre }}
        </span>
        <span v-if="!cuenta.permite_movimiento" class="text-[10px] bg-gray-100 px-1 rounded">grupo</span>
      </div>
      <div class="font-mono text-right text-[11px]" :class="cuenta.saldo >= 0 ? 'text-green-700' : 'text-red-600'">
        {{ formatCurrency(Math.abs(cuenta.saldo)) }}
      </div>
    </div>

    <div v-if="expandida && cuenta.hijos && cuenta.hijos.length > 0">
      <CuentaItem
        v-for="hijo in cuenta.hijos"
        :key="hijo.id"
        :cuenta="hijo"
        :nivel="nivel + 1"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'

defineProps(['cuenta', 'nivel'])
const expandida = ref(true)

function formatCurrency(value) {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,   // ← antes 0
    maximumFractionDigits: 2    // ← antes 0
  }).format(value)
}
</script>
