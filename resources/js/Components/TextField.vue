<template>
  <div class="mb-4">
    <label v-if="label" :for="id" class="block text-sm font-medium text-gray-700 mb-1">
      {{ label }}
    </label>
    <input
      :id="id"
      type="text"
      :name="name"
      :value="modelValue"
      :placeholder="placeholder"
      :class="inputClasses"
      @input="$emit('update:modelValue', $event.target.value)"
      @blur="$emit('blur')"
    />
    <p v-if="error" class="text-red-600 text-sm mt-1">{{ error }}</p>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  modelValue: String,
  label: String,
  name: String,
  id: String,
  placeholder: String,
  error: String,
});

const emit = defineEmits(['update:modelValue', 'blur']);

const inputClasses = computed(() => [
  "block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm",
  props.error ? "border-red-600" : "",
]);
</script>
