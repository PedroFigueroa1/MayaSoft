<template>
  <div class="relative">
    <select
      ref="selectElement"
      :id="id"
      :name="name"
      :multiple="multiple"
      class="hidden"
    >
      <slot />
    </select>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue';
import TomSelect from 'tom-select';

const props = defineProps({
  modelValue: {
    type: [String, Number, Array],
    default: null
  },
  id: {
    type: String,
    default: ''
  },
  name: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Seleccionar...'
  },
  multiple: {
    type: Boolean,
    default: false
  },
  create: {
    type: Boolean,
    default: false
  },
  maxItems: {
    type: Number,
    default: null
  },
  disabled: {
    type: Boolean,
    default: false
  },
  options: {
    type: Array,
    default: () => []
  },
  valueField: {
    type: String,
    default: 'id'
  },
  labelField: {
    type: String,
    default: 'text'
  },
  searchField: {
    type: String,
    default: 'text'
  }
});

const emit = defineEmits(['update:modelValue', 'change', 'focus', 'blur']);

const selectElement = ref(null);
let tomSelectInstance = null;

const initTomSelect = () => {
  if (!selectElement.value) return;

  // Destruir instancia existente
  if (tomSelectInstance) {
    tomSelectInstance.destroy();
  }

  // Configuración de TomSelect
  tomSelectInstance = new TomSelect(selectElement.value, {
    valueField: props.valueField,
    labelField: props.labelField,
    searchField: props.searchField,
    options: props.options,
    items: props.modelValue
      ? (Array.isArray(props.modelValue) ? props.modelValue : [props.modelValue])
      : [],
    placeholder: props.placeholder,
    create: props.create,
    maxItems: props.maxItems,
    maxOptions: null,
    allowEmptyOption: true,
    closeAfterSelect: true,
    hideSelected: true,
    highlight: true,
    plugins: ['clear_button'],
    onInitialize: () => {
      // Sincronizar valor inicial después de inicializar
      nextTick(() => {
        if (props.modelValue !== null && props.modelValue !== undefined) {
          tomSelectInstance.setValue(props.modelValue);
        }
      });
    },
    onChange: (value) => {
      const newValue = props.multiple ? value : (value || null);
      emit('update:modelValue', newValue);
      emit('change', newValue);
    },
    onFocus: () => {
      emit('focus');
    },
    onBlur: () => {
      emit('blur');
    }
  });

  // Aplicar estado disabled
  if (props.disabled) {
    tomSelectInstance.disable();
  }
};

onMounted(() => {
  initTomSelect();
});

// Observar cambios en las props
watch(() => props.disabled, (disabled) => {
  if (tomSelectInstance) {
    if (disabled) {
      tomSelectInstance.disable();
    } else {
      tomSelectInstance.enable();
    }
  }
});

watch(() => props.options, (newOptions) => {
  if (tomSelectInstance) {
    tomSelectInstance.clearOptions();
    newOptions.forEach(option => {
      tomSelectInstance.addOption(option);
    });
    tomSelectInstance.refreshOptions();
  }
}, { deep: true });

watch(() => props.modelValue, (newValue) => {
  if (tomSelectInstance) {
    if (newValue === null || newValue === '' || newValue === undefined) {
      tomSelectInstance.clear();
    } else {
      tomSelectInstance.setValue(newValue);
    }
  }
});

onBeforeUnmount(() => {
  if (tomSelectInstance) {
    tomSelectInstance.destroy();
  }
});

defineExpose({
  getInstance: () => tomSelectInstance,
  focus: () => {
    if (tomSelectInstance) tomSelectInstance.focus();
  }
});
</script>

<style scoped>
.hidden {
  display: none;
}
</style>
