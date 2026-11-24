<template>
  <div class="bg-white overflow-hidden shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0 rounded-md p-3" :class="`bg-${color}-100`">
          <component :is="icon" class="h-6 w-6" :class="`text-${color}-600`" />
        </div>
        <div class="ml-5 w-0 flex-1">
          <dt class="text-sm font-medium text-gray-500 truncate">
            {{ title }}
          </dt>
          <dd class="flex items-baseline">
            <div class="text-2xl font-semibold text-gray-900">
              {{ value }}
            </div>
          </dd>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  value: [String, Number],
  icon: Object,
  color: {
    type: String,
    default: 'indigo'
  }
});
</script>
