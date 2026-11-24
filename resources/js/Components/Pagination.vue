<template>
  <div v-if="links.length > 3" class="pagination-wrapper flex justify-center mt-6">
    <nav>
      <ul class="flex items-center space-x-1">
        <li
          v-for="(link, index) in links"
          :key="index"
          :class="[
            'page-item',
            'rounded-md',
            'overflow-hidden',
            link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100',
            !link.url && 'cursor-not-allowed opacity-50',
          ]"
        >
          <Link
            v-if="link.url"
            :href="link.url"
            class="page-link px-3 py-1 text-sm border border-gray-300 block"
            v-html="link.label"
          />
          <span
            v-else
            class="page-link px-3 py-1 text-sm border border-gray-300 block"
            v-html="link.label"
          />
        </li>
      </ul>
    </nav>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
  links: Array,
});
</script>
