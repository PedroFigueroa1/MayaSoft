<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ isEditMode ? 'Editar Cuenta' : 'Nueva Cuenta' }}
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <div>
                <InputLabel for="codigo" value="Código" />
                <TextInput
                  id="codigo"
                  v-model="form.codigo"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError class="mt-2" :message="form.errors.codigo" />
              </div>

              <div>
                <InputLabel for="nombre" value="Nombre" />
                <TextInput
                  id="nombre"
                  v-model="form.nombre"
                  type="text"
                  class="mt-1 block w-full"
                  required
                />
                <InputError class="mt-2" :message="form.errors.nombre" />
              </div>

              <div>
                <InputLabel for="tipo" value="Tipo de Cuenta" />
                <select
                  id="tipo"
                  v-model="form.tipo"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                  required
                >
                  <option v-for="(value, key) in tipos" :key="key" :value="key">
                    {{ value }}
                  </option>
                </select>
                <InputError class="mt-2" :message="form.errors.tipo" />
              </div>

              <div>
                <InputLabel for="nivel" value="Nivel Jerárquico" />
                <select
                  id="nivel"
                  v-model="form.nivel"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                  required
                >
                  <option v-for="n in niveles" :key="n" :value="n">{{ n }}</option>
                </select>
                <InputError class="mt-2" :message="form.errors.nivel" />
              </div>

              <div>
                <InputLabel for="cuenta_padre_id" value="Cuenta Padre (opcional)" />
                <select ref="cuentaPadreSelect" id="cuenta_padre_id" class="mt-1 block w-full">
                  <option value="">Ninguna</option>
                  <option
                    v-for="cp in filteredCuentasPadre"
                    :key="cp.id"
                    :value="cp.id"
                  >
                    {{ cp.codigo }} - {{ cp.nombre }}
                  </option>
                </select>
                <div class="mt-1 text-xs text-gray-400">
                  Incluye cuentas base y tus cuentas propias
                </div>
                <InputError class="mt-2" :message="form.errors.cuenta_padre_id" />
              </div>

              <div class="flex items-center mt-6">
                <Checkbox id="permite_movimiento" v-model:checked="form.permite_movimiento" />
                <InputLabel for="permite_movimiento" value="Permite Movimiento" class="ml-2" />
                <InputError class="mt-2" :message="form.errors.permite_movimiento" />
              </div>

              <div class="md:col-span-2">
                <InputLabel for="descripcion" value="Descripción (opcional)" />
                <TextArea
                  id="descripcion"
                  v-model="form.descripcion"
                  class="mt-1 block w-full"
                  rows="3"
                />
                <InputError class="mt-2" :message="form.errors.descripcion" />
              </div>

            </div>

            <div class="flex items-center justify-end mt-6">
              <Link :href="route('cuentas.index')" class="btn-secondary mr-4">
                Cancelar
              </Link>
              <button
                type="submit"
                class="btn-primary"
                :disabled="form.processing"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
              >
                <span v-if="form.processing">
                  <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Procesando...
                </span>
                <span v-else>{{ isEditMode ? 'Actualizar' : 'Crear' }}</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import TextArea from '@/Components/TextArea.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { computed, onMounted, ref, nextTick, watch } from 'vue';
import TomSelect from 'tom-select';

const props = defineProps({
  cuenta: {
    type: [Object, null],
    default: null,
  },
  tipos: Object,
  cuentasPadre: Array,
  niveles: Array,
});

// ==========================================
// MODO EDICIÓN
// ==========================================

const isEditMode = computed(() =>
  props.cuenta !== null &&
  props.cuenta !== undefined &&
  props.cuenta.id !== undefined
);

// Filtrar: en edición excluir la cuenta actual
// Las cuentas padre ya vienen filtradas desde el controller
// (visiblePara = base + propias del usuario)
const filteredCuentasPadre = computed(() => {
  if (!isEditMode.value || !props.cuenta) {
    return props.cuentasPadre;
  }
  return props.cuentasPadre.filter(cp => cp.id !== props.cuenta.id);
});

// ==========================================
// FORMULARIO
// ==========================================

const form = useForm({
  codigo:             '',
  nombre:             '',
  tipo:               'A',
  nivel:              1,
  permite_movimiento: true,
  cuenta_padre_id:    null,
  descripcion:        '',
});

// ==========================================
// TOM SELECT
// ==========================================

const cuentaPadreSelect = ref(null);
let tomSelectInstance = null;

onMounted(() => {
  if (isEditMode.value && props.cuenta) {
    form.codigo             = props.cuenta.codigo             || '';
    form.nombre             = props.cuenta.nombre             || '';
    form.tipo               = props.cuenta.tipo               || 'A';
    form.nivel              = props.cuenta.nivel              || 1;
    form.permite_movimiento = Boolean(props.cuenta.permite_movimiento);
    form.cuenta_padre_id    = props.cuenta.cuenta_padre_id    || null;
    form.descripcion        = props.cuenta.descripcion        || '';
  }

  nextTick(() => initTomSelect());
});

const initTomSelect = () => {
  if (!cuentaPadreSelect.value) return;
  if (tomSelectInstance) tomSelectInstance.destroy();

  tomSelectInstance = new TomSelect(cuentaPadreSelect.value, {
    valueField:       'value',
    labelField:       'text',
    searchField:      'text',
    placeholder:      'Escribe para buscar una cuenta...',
    create:           false,
    maxItems:         1,
    allowEmptyOption: true,
    closeAfterSelect: true,
    hideSelected:     true,
    highlight:        true,
    plugins:          ['clear_button'],

    options: filteredCuentasPadre.value.map(cp => ({
      value:   cp.id,
      text:    `${cp.codigo} - ${cp.nombre}`,
      codigo:  cp.codigo,
      nombre:  cp.nombre,
      nivel:   cp.nivel,
      es_base: cp.es_base,
    })),

    items: form.cuenta_padre_id ? [form.cuenta_padre_id] : [],

    onChange: (value) => {
      form.cuenta_padre_id = value || null;
    },

    // ← Muestra badge "Base" o "Propia" en el dropdown
    render: {
      option: function(data, escape) {
        const badge = data.es_base
          ? `<span style="font-size:10px;background:#f3f4f6;color:#6b7280;padding:1px 6px;border-radius:9999px;margin-left:4px;">Base</span>`
          : `<span style="font-size:10px;background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:9999px;margin-left:4px;">Propia</span>`;

        return `
          <div class="p-2 hover:bg-gray-100 cursor-pointer">
            <div class="font-medium">
              ${escape(data.codigo)} ${badge}
            </div>
            <div class="text-sm text-gray-600">${escape(data.nombre)}</div>
            <div class="text-xs text-gray-400">Nivel: ${escape(String(data.nivel))}</div>
          </div>
        `;
      },
      item: function(data, escape) {
        return `<div>${escape(data.codigo)} - ${escape(data.nombre)}</div>`;
      },
    },
  });
};

// Actualizar TomSelect si cambian las opciones
watch(filteredCuentasPadre, (newCuentas) => {
  if (!tomSelectInstance) return;

  tomSelectInstance.clearOptions();
  newCuentas.forEach(cp => {
    tomSelectInstance.addOption({
      value:   cp.id,
      text:    `${cp.codigo} - ${cp.nombre}`,
      codigo:  cp.codigo,
      nombre:  cp.nombre,
      nivel:   cp.nivel,
      es_base: cp.es_base,
    });
  });
  tomSelectInstance.refreshOptions();
});

watch(() => form.cuenta_padre_id, (newValue) => {
  if (tomSelectInstance && newValue !== tomSelectInstance.getValue()) {
    tomSelectInstance.setValue(newValue);
  }
});

// ==========================================
// SUBMIT
// ==========================================

const submit = () => {
  if (isEditMode.value) {
    form.put(route('cuentas.update', props.cuenta.id), {
      preserveScroll: true,
      onError: (errors) => console.error('Error al actualizar:', errors),
    });
  } else {
    form.post(route('cuentas.store'), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
      onError: (errors) => console.error('Error al crear:', errors),
    });
  }
};
</script>

<style>
@import 'tom-select/dist/css/tom-select.css';

.ts-control {
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  padding: 0.5rem 0.75rem !important;
  background-color: white !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
  min-height: 42px !important;
}
.ts-control.focus {
  border-color: #6366f1 !important;
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1) !important;
  outline: none !important;
}
.ts-wrapper.single .ts-control {
  background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e") !important;
  background-position: right 0.5rem center !important;
  background-repeat: no-repeat !important;
  background-size: 1.5em 1.5em !important;
  padding-right: 2.5rem !important;
}
.ts-dropdown {
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  margin-top: 0.25rem !important;
  box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important;
  background-color: white !important;
}
.ts-dropdown .option {
  padding: 0.5rem 0.75rem !important;
  border-bottom: 1px solid #f3f4f6 !important;
}
.ts-dropdown .option:last-child { border-bottom: none !important; }
.ts-dropdown .active { background-color: #f9fafb !important; color: #374151 !important; }
.ts-dropdown .selected { background-color: #e0e7ff !important; color: #3730a3 !important; }
.ts-control .clear-button { color: #9ca3af !important; padding: 4px !important; }
.ts-control .clear-button:hover { color: #6b7280 !important; }
.ts-control input { color: #111827 !important; font-size: 0.875rem !important; }
.ts-control input::placeholder { color: #9ca3af !important; }
</style>
