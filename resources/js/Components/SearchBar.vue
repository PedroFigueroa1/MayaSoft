<!-- resources/js/Components/SearchBar.vue -->
<template>
  <div class="relative">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
      <SearchIcon class="h-5 w-5 text-gray-400" />
    </div>
    <input
      type="text"
      class="input-default pl-10"
      :placeholder="placeholder"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
    />
  </div>
</template>

<script setup>
// Versión v1 correcta:
import { SearchIcon } from '@heroicons/vue/outline';

defineProps({
  modelValue: String,
  placeholder: {
    type: String,
    default: 'Buscar...'
  }
});

defineEmits(['update:modelValue']);
</script>
