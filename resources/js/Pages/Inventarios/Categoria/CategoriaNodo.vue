<script setup>
defineProps({
  categoria: { type: Object, required: true },
  expandidas: { type: Set, required: true },
})
defineEmits(['toggle', 'crear-hijo', 'editar', 'eliminar'])
</script>

<template>
  <div class="ml-0">
    <div class="flex items-center gap-2 py-1.5 group hover:bg-gray-50 rounded px-2">
      <button
        v-if="categoria.hijos?.length"
        @click="$emit('toggle', categoria.id)"
        class="w-4 text-gray-400"
      >
        {{ expandidas.has(categoria.id) ? '▾' : '▸' }}
      </button>
      <span v-else class="w-4"></span>

      <span>{{ categoria.hijos?.length ? '📁' : '📄' }}</span>
      <span class="font-medium">{{ categoria.nombre }}</span>
      <span class="text-xs text-gray-400 font-mono">{{ categoria.codigo }}</span>

      <span class="ml-auto flex gap-3 opacity-0 group-hover:opacity-100 text-xs">
        <button class="text-indigo-600 hover:underline" @click="$emit('crear-hijo', categoria.id)">+ subcategoría</button>
        <button class="text-indigo-600 hover:underline" @click="$emit('editar', categoria)">editar</button>
        <button class="text-red-600 hover:underline" @click="$emit('eliminar', categoria)">eliminar</button>
      </span>
    </div>

    <div v-if="expandidas.has(categoria.id)" class="ml-6 border-l border-gray-200 pl-2">
      <CategoriaNodo
        v-for="hijo in categoria.hijos"
        :key="hijo.id"
        :categoria="hijo"
        :expandidas="expandidas"
        @toggle="$emit('toggle', $event)"
        @crear-hijo="$emit('crear-hijo', $event)"
        @editar="$emit('editar', $event)"
        @eliminar="$emit('eliminar', $event)"
      />
    </div>
  </div>
</template>
