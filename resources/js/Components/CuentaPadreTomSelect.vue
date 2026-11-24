<template>
  <div>
    <TomSelect
      :id="id"
      :name="name"
      :modelValue="internalValue"
      @update:modelValue="handleChange"
      :placeholder="placeholder"
      :multiple="false"
      :disabled="disabled"
      :options="formattedOptions"
      valueField="id"
      labelField="text"
      searchField="text"
    >
      <!-- Slot para opciones estáticas si las necesitas -->
      <option v-if="showEmptyOption" value="">Ninguna</option>
    </TomSelect>

    <!-- Mostrar valor seleccionado si está en modo disabled -->
    <div v-if="disabled && selectedLabel" class="mt-1 p-2 bg-gray-50 rounded border border-gray-200 text-gray-700">
      {{ selectedLabel }}
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import TomSelect from './TomSelect.vue';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: null
  },
  id: {
    type: String,
    default: 'cuenta_padre_id'
  },
  name: {
    type: String,
    default: 'cuenta_padre_id'
  },
  placeholder: {
    type: String,
    default: 'Buscar cuenta padre...'
  },
  disabled: {
    type: Boolean,
    default: false
  },
  cuentas: {
    type: Array,
    default: () => []
  },
  excludeId: {
    type: Number,
    default: null
  },
  showEmptyOption: {
    type: Boolean,
    default: true
  }
});

const emit = defineEmits(['update:modelValue', 'change']);

const internalValue = ref(props.modelValue);

// Formatear opciones para TomSelect
const formattedOptions = computed(() => {
  // Filtrar cuenta actual si estamos en modo edición
  let filteredCuentas = props.cuentas;
  if (props.excludeId) {
    filteredCuentas = filteredCuentas.filter(c => c.id !== props.excludeId);
  }

  // Ordenar por código para mejor búsqueda
  filteredCuentas = [...filteredCuentas].sort((a, b) => {
    return a.codigo.localeCompare(b.codigo);
  });

  return filteredCuentas.map(cuenta => ({
    id: cuenta.id,
    text: `${cuenta.codigo} - ${cuenta.nombre} ${cuenta.nivel ? `(Nivel ${cuenta.nivel})` : ''}`,
    codigo: cuenta.codigo,
    nombre: cuenta.nombre,
    nivel: cuenta.nivel,
    tipo: cuenta.tipo
  }));
});

// Encontrar la etiqueta del item seleccionado
const selectedLabel = computed(() => {
  if (!props.modelValue) return '';
  const cuenta = props.cuentas.find(c => c.id == props.modelValue);
  return cuenta ? `${cuenta.codigo} - ${cuenta.nombre}` : '';
});

// Sincronizar con cambios externos
watch(() => props.modelValue, (newValue) => {
  internalValue.value = newValue;
});

const handleChange = (value) => {
  internalValue.value = value;
  emit('update:modelValue', value);
  emit('change', value);
};

// Métodos expuestos
defineExpose({
  clear: () => {
    internalValue.value = null;
    emit('update:modelValue', null);
  }
});
</script>

<style>
/* Importar estilos de Tom Select */
@import 'tom-select/dist/css/tom-select.css';

/* Personalizar estilos para que coincida con Tailwind */
.ts-control {
  @apply mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm bg-white text-gray-900;
  min-height: 42px;
  padding: 0.5rem 0.75rem;
}

.ts-dropdown {
  @apply mt-1 shadow-lg rounded-md border border-gray-300 bg-white z-50;
}

.ts-dropdown .option {
  @apply py-2 px-3 hover:bg-gray-100 cursor-pointer;
}

.ts-dropdown .active {
  @apply bg-indigo-50 text-indigo-700;
}

.ts-dropdown .selected {
  @apply bg-indigo-100 text-indigo-800;
}

.ts-dropdown .create {
  @apply text-green-600;
}

.ts-wrapper.multi .ts-control > div {
  @apply bg-indigo-100 text-indigo-800 rounded px-2 py-1 m-1;
}

.ts-wrapper.multi .ts-control > div.active {
  @apply bg-indigo-200;
}

.ts-wrapper.multi .ts-control input {
  @apply inline-block p-0 m-0;
}

/* Estilo para el botón clear */
.ts-control .clear-button {
  @apply text-gray-400 hover:text-gray-600 p-1;
}
</style>
