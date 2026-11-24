<!-- SeccionFlujo.vue completo actualizado -->
<template>
  <div class="mb-0">
    <div :class="color" class="px-3 py-1.5 text-white font-bold text-[11px]">
      {{ titulo }}
    </div>

    <div v-if="actividades.length === 0" :class="bgItem" class="px-3 py-1.5 text-gray-400 italic">
      Sin movimientos en el período
    </div>

    <template v-for="(act, i) in actividades" :key="i">
      <!-- Separador "Ajustes:" antes del primer ajuste -->
      <div v-if="act.es_ajuste && (i === 0 || !actividades[i-1].es_ajuste)"
           :class="bgItem" class="px-3 py-0.5 text-gray-500 italic border-b border-gray-100">
        Ajustes:
      </div>

      <div :class="bgItem" class="flex justify-between border-b border-gray-100"
           :style="act.es_ajuste ? 'padding: 3px 12px 3px 24px' : 'padding: 4px 12px'">
        <span class="text-gray-700">{{ act.concepto }}</span>
        <span class="font-mono"
              :class="act.tipo === 'salida' ? 'text-red-600' : 'text-gray-800'">
          {{ act.tipo === 'salida'
              ? '(' + formatNum(act.monto) + ')'
              : formatNum(act.monto) }}
        </span>
      </div>
    </template>

    <!-- Total -->
    <div class="flex justify-between px-3 py-1.5 bg-blue-50 border-t border-blue-200 font-semibold">
      <span>{{ totalLabel }}</span>
      <span :class="total < 0 ? 'text-red-600' : 'text-blue-800'">
        {{ total < 0 ? '(' + formatNum(Math.abs(total)) + ')' : formatNum(total) }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  titulo:      String,
  color:       String,
  actividades: Array,
  total:       Number,
  totalLabel:  String,
  bgItem:      { type: String, default: 'bg-white' },
})

function formatNum(v) {
  return new Intl.NumberFormat('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v)
}
</script>
