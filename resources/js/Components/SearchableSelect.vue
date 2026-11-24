<!-- resources/js/Components/SearchableSelect.vue -->
<template>
  <div class="relative">
    <input
      type="text"
      :value="searchTerm"
      @input="updateSearchTerm"
      @focus="showDropdown = true"
      @blur="handleBlur"
      :placeholder="placeholder"
      :disabled="disabled"
      class="border rounded px-2 py-1 text-xs w-full"
      :class="{ 'bg-gray-100 cursor-not-allowed': disabled }"
    />
    <div v-if="!disabled && showDropdown && filteredOptions.length" class="absolute z-10 bg-white border rounded shadow max-h-40 overflow-y-auto w-full">
      <div
        v-for="opt in filteredOptions"
        :key="opt.id"
        class="px-2 py-1 hover:bg-gray-100 cursor-pointer text-xs"
        @mousedown.prevent="selectOption(opt)"
      >
        {{ opt.codigo }} - {{ opt.nombre }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  options: Array,
  value: [Number, String],
  placeholder: String,
  disabled: Boolean,
})

const emit = defineEmits(['update'])

const searchTerm = ref('')
const showDropdown = ref(false)

watch(() => props.value, (newVal) => {
  if (props.disabled) return // No actualizar si está deshabilitado
  const selected = props.options.find(opt => opt.id == newVal)
  if (selected) {
    searchTerm.value = `${selected.codigo} - ${selected.nombre}`
  } else if (!newVal) {
    searchTerm.value = ''
  }
}, { immediate: true })

const filteredOptions = computed(() => {
  if (!searchTerm.value.trim()) return props.options
  const term = searchTerm.value.toLowerCase()
  return props.options.filter(opt =>
    opt.codigo.toLowerCase().includes(term) ||
    opt.nombre.toLowerCase().includes(term)
  )
})

const updateSearchTerm = (e) => {
  if (props.disabled) return
  searchTerm.value = e.target.value
  showDropdown.value = true
}

const selectOption = (opt) => {
  if (props.disabled) return
  searchTerm.value = `${opt.codigo} - ${opt.nombre}`
  emit('update', opt.id)
  showDropdown.value = false
}

const handleBlur = () => {
  setTimeout(() => { showDropdown.value = false }, 200)
}
</script>
