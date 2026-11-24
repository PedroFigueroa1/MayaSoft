<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number, null], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Buscar...' },
  labelKey: { type: String, default: 'nombre' },
  valueKey: { type: String, default: 'id' },
  secondaryKey: { type: String, default: null },
  error: { type: String, default: '' },
  disabled: { type: Boolean, default: false },
  emptyText: { type: String, default: 'Sin resultados' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const open = ref(false)
const query = ref('')
const trigger = ref(null)
const inputRef = ref(null) // Referencia al input real
const dropdownStyle = ref({ top: '0px', left: '0px', width: '0px', maxHeight: '300px' })
const highlighted = ref(0)

const selected = computed(() =>
  props.options.find(o => String(o[props.valueKey]) === String(props.modelValue)) || null
)

const selectedLabel = computed(() => {
  if (!selected.value) return ''
  return props.secondaryKey
    ? `${selected.value[props.secondaryKey]} — ${selected.value[props.labelKey]}`
    : selected.value[props.labelKey]
})

const filtered = computed(() => {
  const q = query.value.trim().toLowerCase()
  if (!q) return props.options.slice(0, 100)
  return props.options
    .filter(o => {
      const text = `${o[props.labelKey] ?? ''} ${o[props.secondaryKey] ?? ''}`.toLowerCase()
      return text.includes(q)
    })
    .slice(0, 100)
})

// 🔥 Calcula posición FIJA y decide si abrir arriba o abajo
const updatePosition = () => {
  if (!trigger.value) return
  const rect = trigger.value.getBoundingClientRect()
  const spaceBelow = window.innerHeight - rect.bottom
  const spaceAbove = rect.top
  const dropdownHeight = Math.min(300, spaceBelow - 10) // Altura máxima estimada

  // Si hay poco espacio abajo y hay más arriba, abrimos hacia arriba
  if (spaceBelow < 200 && spaceAbove > spaceBelow) {
    dropdownStyle.value = {
      top: `${rect.top - Math.min(300, spaceAbove) - 8}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      maxHeight: `${Math.min(300, spaceAbove - 10)}px`,
    }
  } else {
    dropdownStyle.value = {
      top: `${rect.bottom + 4}px`,
      left: `${rect.left}px`,
      width: `${rect.width}px`,
      maxHeight: `${dropdownHeight}px`,
    }
  }
}

const openDropdown = async () => {
  if (props.disabled) return
  open.value = true
  query.value = ''
  highlighted.value = 0
  await nextTick()
  updatePosition()
  await nextTick()
  inputRef.value?.focus()
}

const closeDropdown = () => {
  open.value = false
  query.value = ''
}

const select = (opt) => {
  if (!opt) return
  emit('update:modelValue', opt[props.valueKey])
  emit('change', opt)
  closeDropdown()
}

const clear = (e) => {
  e.stopPropagation()
  e.preventDefault()
  emit('update:modelValue', '')
  emit('change', null)
}

const onKeyDown = (e) => {
  if (!open.value) {
    if (e.key === 'Enter' || e.key === 'ArrowDown' || e.key === ' ') {
      e.preventDefault()
      openDropdown()
    }
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    highlighted.value = Math.min(highlighted.value + 1, filtered.value.length - 1)
  } else if (e.key === 'ArrowUp') {
    e.preventDefault()
    highlighted.value = Math.max(highlighted.value - 1, 0)
  } else if (e.key === 'Enter') {
    e.preventDefault()
    select(filtered.value[highlighted.value])
  } else if (e.key === 'Escape') {
    closeDropdown()
  }
}

const onClickOutside = (e) => {
  if (!open.value) return
  const dd = document.querySelector('.searchselect-dropdown')
  if (trigger.value?.contains(e.target)) return
  if (dd?.contains(e.target)) return
  closeDropdown()
}

const onScrollOrResize = () => {
  if (open.value) updatePosition()
}

onMounted(() => {
  document.addEventListener('mousedown', onClickOutside)
  window.addEventListener('scroll', onScrollOrResize, true)
  window.addEventListener('resize', onScrollOrResize)
})

onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onClickOutside)
  window.removeEventListener('scroll', onScrollOrResize, true)
  window.removeEventListener('resize', onScrollOrResize)
})
</script>

<template>
  <!-- Contenedor principal -->
  <div ref="trigger" class="relative w-full" @keydown="onKeyDown">
    
    <!-- Visual: input falso que parece real -->
    <div
      @click="openDropdown"
      class="w-full text-left rounded-md border shadow-sm bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 flex items-center justify-between gap-2 min-h-[38px] cursor-pointer"
      :class="[
        error ? 'border-red-400' : 'border-gray-300',
        open ? 'ring-2 ring-indigo-500 border-indigo-500' : '',
        disabled ? 'bg-gray-100 cursor-not-allowed' : ''
      ]"
    >
      <span :class="selectedLabel ? 'text-gray-900 truncate' : 'text-gray-400 truncate'">
        {{ selectedLabel || placeholder }}
      </span>
      <div class="flex items-center gap-1 shrink-0">
        <svg
          v-if="selected && !disabled"
          @click="clear"
          class="w-4 h-4 text-gray-400 hover:text-red-500 cursor-pointer"
          fill="none" stroke="currentColor" viewBox="0 0 24 24"
        >
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
        </svg>
      </div>
    </div>

    <!-- 🔥 Dropdown teletransportado a <body> -->
    <Teleport to="body">
      <div
        v-if="open"
        class="searchselect-dropdown fixed z-[99999] bg-white border border-gray-300 rounded-md shadow-2xl overflow-hidden flex flex-col"
        :style="{
          top: dropdownStyle.top,
          left: dropdownStyle.left,
          width: dropdownStyle.width,
          maxHeight: dropdownStyle.maxHeight,
        }"
        @mousedown.stop
      >
        <!-- Input de búsqueda dentro del dropdown -->
        <div class="p-2 border-b bg-gray-50 shrink-0">
          <input
            ref="inputRef"
            v-model="query"
            type="text"
            :placeholder="placeholder"
            class="w-full rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            @keydown="onKeyDown"
          />
        </div>

        <!-- Lista de resultados con scroll -->
        <ul class="overflow-y-auto divide-y divide-gray-100 flex-1">
          <li v-if="!filtered.length" class="px-3 py-3 text-sm text-gray-500 text-center">
            {{ emptyText }}
          </li>
          <li
            v-for="(opt, idx) in filtered"
            :key="opt[valueKey]"
            @mouseenter="highlighted = idx"
            @click="select(opt)"
            class="px-3 py-2 text-sm cursor-pointer flex justify-between items-center gap-2"
            :class="[
              highlighted === idx ? 'bg-indigo-50' : '',
              String(opt[valueKey]) === String(modelValue) ? 'bg-indigo-100 font-medium' : '',
            ]"
          >
            <span class="truncate">
              <span v-if="secondaryKey" class="font-mono text-xs text-gray-500 mr-2">
                {{ opt[secondaryKey] }}
              </span>
              {{ opt[labelKey] }}
            </span>
            <svg
              v-if="String(opt[valueKey]) === String(modelValue)"
              class="w-4 h-4 text-indigo-600 shrink-0"
              fill="currentColor" viewBox="0 0 20 20"
            >
              <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
          </li>
        </ul>
      </div>
    </Teleport>

    <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
  </div>
</template>