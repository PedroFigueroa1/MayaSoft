<!-- resources/js/Components/TextArea.vue -->
<template>
  <div>
    <label :for="id" class="block text-sm font-medium text-gray-700">{{ label }}</label>
    <textarea
      :id="id"
      :name="name"
      :value="modelValue"
      @input="$emit('update:modelValue', $event.target.value)"
      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"
      rows="4"
    ></textarea>
  </div>
</template>

<script setup>
defineProps({
  modelValue: String,
  label: String,
  name: String,
  id: String,
});
defineEmits(['update:modelValue']);
</script>
