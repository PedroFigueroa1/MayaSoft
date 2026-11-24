<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Editar Asiento #{{ form.numero_asiento }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <!-- Mostrar errores generales -->
            <div v-if="form.errors.balance" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
              {{ form.errors.balance }}
            </div>

            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-5">
                <!-- Campo de período fijo (solo lectura) -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="periodo_id">
                    Periodo *
                  </label>
                  <input
                    type="text"
                    :value="periodoNombre"
                    id="periodo_id"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    readonly
                    required
                  >
                  <input type="hidden" v-model="form.periodo_id">
                  <p v-if="form.errors.periodo_id" class="text-red-500 text-xs italic">{{ form.errors.periodo_id }}</p>
                </div>

                <div class="col-span-1">
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="fecha">
                    Fecha *
                  </label>
                  <input
                    type="date"
                    v-model="form.fecha"
                    id="fecha"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors.fecha }"
                    required
                  >
                  <p v-if="form.errors.fecha" class="text-red-500 text-xs italic">{{ form.errors.fecha }}</p>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="tipo">
                    Tipo *
                  </label>
                  <select
                    v-model="form.tipo"
                    id="tipo"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors.tipo }"
                    required
                  >
                    <option value="ING">Ingreso</option>
                    <option value="EGR">Egreso</option>
                    <option value="TRN">Traspaso</option>
                    <option value="AJT">Ajuste</option>
                    <option value="CIE">Cierre</option>
                  </select>
                  <p v-if="form.errors.tipo" class="text-red-500 text-xs italic">{{ form.errors.tipo }}</p>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="numero_asiento">
                    Número de Asiento *
                  </label>
                  <input
                    type="text"
                    v-model="form.numero_asiento"
                    id="numero_asiento"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors.numero_asiento }"
                    required
                    :title="'Formato: ' + tipoPreview + ' seguido de 4 dígitos (ej: ' + tipoPreview + '0001)'"
                  >
                  <p v-if="form.errors.numero_asiento" class="text-red-500 text-xs italic">{{ form.errors.numero_asiento }}</p>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="comprobante">
                    Comprobante
                  </label>
                  <input
                    type="text"
                    v-model="form.comprobante"
                    id="comprobante"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors.comprobante }"
                  >
                  <p v-if="form.errors.comprobante" class="text-red-500 text-xs italic">{{ form.errors.comprobante }}</p>
                </div>
              </div>

              <div class="mb-5">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="glosa">
                  Glosa *
                </label>
                <textarea
                  v-model="form.glosa"
                  id="glosa"
                  rows="1"
                  class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                  :class="{ 'border-red-500': form.errors.glosa }"
                  required
                ></textarea>
                <p v-if="form.errors.glosa" class="text-red-500 text-xs italic">{{ form.errors.glosa }}</p>
              </div>

              <h3 class="text-base font-medium text-gray-900 mb-3">Detalles del Asiento</h3>

              <div v-for="(detalle, index) in form.detalles" :key="index"
                   class="grid grid-cols-1 md:grid-cols-12 gap-2 mb-2 items-center"
                   :class="index > 0 ? 'pt-2 border-t border-gray-100' : ''">
                <!-- Cuenta con TomSelect -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Cuenta *
                  </label>
                  <select
                    :ref="el => setSelectRef(el, index)"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.cuenta_id`] }"
                  >
                    <option value="">Seleccionar Cuenta</option>
                  </select>
                  <p v-if="form.errors[`detalles.${index}.cuenta_id`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.cuenta_id`] }}</p>
                </div>

                <!-- Centro de Costos -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Centro Costos
                  </label>
                  <select
                    v-model="detalle.centro_costos_id"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.centro_costos_id`] }"
                  >
                    <option value="">Sin Centro</option>
                    <option
                      v-for="centro in centrosCostos"
                      :key="centro.id"
                      :value="centro.id"
                    >
                      {{ centro.codigo }} - {{ centro.nombre }}
                    </option>
                  </select>
                  <p v-if="form.errors[`detalles.${index}.centro_costos_id`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.centro_costos_id`] }}</p>
                </div>

                <!-- Descripción -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Descripción
                  </label>
                  <input
                    type="text"
                    v-model="detalle.descripcion"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.descripcion`] }"
                  >
                  <p v-if="form.errors[`detalles.${index}.descripcion`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.descripcion`] }}</p>
                </div>

                <!-- Debe -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Debe
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="detalle.debe"
                    @input="updateHaber(index)"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.debe`] }"
                  >
                  <p v-if="form.errors[`detalles.${index}.debe`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.debe`] }}</p>
                </div>

                <!-- Haber -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Haber
                  </label>
                  <input
                    type="number"
                    step="0.01"
                    v-model="detalle.haber"
                    @input="updateDebe(index)"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.haber`] }"
                  >
                  <p v-if="form.errors[`detalles.${index}.haber`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.haber`] }}</p>
                </div>

                <!-- Deducible -->
                <div class="col-span-1">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    D/ND
                  </label>
                  <div
                    class="shadow appearance-none border rounded w-full py-1 px-2 flex items-center justify-center h-[34px]"
                    :class="{ 'border-red-500': form.errors[`detalles.${index}.deducible`] }"
                  >
                    <input
                      type="checkbox"
                      v-model="detalle.deducible"
                      class="rounded border-gray-300 text-blue-600 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    >
                  </div>
                  <p v-if="form.errors[`detalles.${index}.deducible`]" class="text-red-500 text-xs italic">{{ form.errors[`detalles.${index}.deducible`] }}</p>
                </div>

                <!-- Eliminar - X pequeña -->
                <div class="col-span-1 text-center">
                  <button
                    type="button"
                    @click="removeDetalle(index)"
                    class="text-gray-400 hover:text-red-600 transition-colors"
                    v-if="form.detalles.length > 1"
                    title="Eliminar línea"
                  >
                    <span class="text-lg font-bold">✕</span>
                  </button>
                </div>
              </div>

              <!-- Totales de Debe y Haber -->
              <div class="flex flex-col md:flex-row justify-between items-center mt-4 mb-5 p-3 bg-gray-50 rounded-md">
                <div class="font-bold text-gray-700 mb-2 md:mb-0">
                  Totales:
                </div>

                <div class="flex gap-4 sm:gap-6 lg:gap-10">
                    <div class="text-gray-700 font-medium"
                        :class="{ 'text-green-600': isBalanced, 'text-red-600': !isBalanced }">
                    {{ formatCurrency(totalDebe) }}
                    </div>
                    <div class="text-gray-700 font-medium"
                        :class="{ 'text-green-600': isBalanced, 'text-red-600': !isBalanced }">
                    {{ formatCurrency(totalHaber) }}
                    </div>
                </div>
              </div>

              <div class="mb-5">
                <button
                  type="button"
                  @click="addDetalle"
                  class="inline-flex items-center px-3 py-1.5 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-300 focus:outline-none focus:border-gray-300 focus:ring focus:ring-gray-200 disabled:opacity-25 transition"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                  </svg>
                  Agregar Línea
                </button>
              </div>

              <div class="flex items-center justify-end mt-6">
                <Link
                  :href="route('asientos.index', form.periodo_id)"
                  class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition mr-4"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                  :disabled="form.processing"
                >
                  {{ form.processing ? 'Actualizando...' : 'Actualizar Asiento' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, nextTick, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import TomSelect from 'tom-select';

const props = defineProps({
  asiento: {
    type: Object,
    required: true
  },
  periodos: {
    type: Array,
    required: true
  },
  cuentas: {
    type: Array,
    required: true
  },
  centrosCostos: {
    type: Array,
    required: true
  }
});

// Referencias para TomSelect
const cuentaSelectRefs = ref([]);
const tomSelectInstances = ref([]);
// Función auxiliar para redondear a 2 decimales
const roundToTwo = (value) => {
  const num = parseFloat(value);
  if (isNaN(num)) return 0;
  return Math.round(num * 100) / 100;
};

// Computed para mostrar el nombre del período
const periodoNombre = computed(() => {
  const periodo = props.periodos.find(p => p.id === props.asiento.periodo_id);
  return periodo ? periodo.nombre : '';
});

// Computed para el prefijo del tipo (para el tooltip)
const tipoPreview = computed(() => {
  const prefijos = {
    'ING': 'CI-',
    'EGR': 'CE-',
    'TRN': 'CT-',
    'AJT': 'CA-',
    'CIE': 'CC-'
  };
  return prefijos[form.tipo] || 'XX-';
});

// Función para asignar referencias
const setSelectRef = (el, index) => {
  if (el) {
    cuentaSelectRefs.value[index] = el;
  }
};

// Prepara los detalles para el formulario (manteniendo IDs originales)
const prepareDetalles = () => {
  return props.asiento.detalles.map(detalle => ({
    id: detalle.id,
    cuenta_id: detalle.cuenta_id,
    centro_costos_id: detalle.centro_costos_id || '',
    debe: roundToTwo(detalle.debe),
    haber: roundToTwo(detalle.haber),
    descripcion: detalle.descripcion || '',
    deducible: detalle.deducible || false
  }));
};

// Inicializa el formulario con los datos del asiento
const form = useForm({
  periodo_id: props.asiento.periodo_id,
  fecha: props.asiento.fecha,
  numero_asiento: props.asiento.numero_asiento,
  tipo: props.asiento.tipo,
  glosa: props.asiento.glosa,
  comprobante: props.asiento.comprobante || '',
  detalles: prepareDetalles()
});

// Inicializar TomSelect para todos los detalles
const initAllTomSelects = () => {
  form.detalles.forEach((detalle, index) => {
    initTomSelect(index);
  });
};

// Inicializar TomSelect para un detalle específico
const initTomSelect = (index) => {
  const selectElement = cuentaSelectRefs.value[index];
  if (!selectElement) return;

  // Destruir instancia existente si la hay
  if (tomSelectInstances.value[index]) {
    tomSelectInstances.value[index].destroy();
  }

  const tomSelect = new TomSelect(selectElement, {
    valueField: 'value',
    labelField: 'text',
    searchField: ['text', 'codigo', 'nombre'],
    options: props.cuentas.map(cuenta => ({
      value: cuenta.id,
      text: `${cuenta.codigo} - ${cuenta.nombre}`,
      codigo: cuenta.codigo,
      nombre: cuenta.nombre,
      tipo: cuenta.tipo
    })),
    items: form.detalles[index].cuenta_id ? [form.detalles[index].cuenta_id] : [],
    placeholder: 'Buscar cuenta...',
    create: false,
    maxItems: 1,
    allowEmptyOption: true,
    closeAfterSelect: true,
    hideSelected: true,
    highlight: true,
    plugins: ['clear_button'],
    onChange: (value) => {
      form.detalles[index].cuenta_id = value || '';
    },
    render: {
      option: function(data, escape) {
        return `
          <div class="p-2 hover:bg-gray-100 cursor-pointer">
            <div class="font-medium">${escape(data.codigo)}</div>
            <div class="text-sm text-gray-600">${escape(data.nombre)}</div>
            <div class="text-xs text-gray-500 mt-1">Tipo: ${escape(data.tipo)}</div>
          </div>
        `;
      },
      item: function(data, escape) {
        return `<div>${escape(data.codigo)} - ${escape(data.nombre)}</div>`;
      },
      no_results: function() {
        return '<div class="no-results p-2">No se encontraron cuentas</div>';
      }
    }
  });

  tomSelectInstances.value[index] = tomSelect;
};



// Calcular totales con redondeo
const totalDebe = computed(() => {
  return form.detalles.reduce((total, detalle) => {
    return total + roundToTwo(detalle.debe);
  }, 0);
});

const totalHaber = computed(() => {
  return form.detalles.reduce((total, detalle) => {
    return total + roundToTwo(detalle.haber);
  }, 0);
});

// Función para comparar si los totales son iguales (tolerancia 0.01)
const isBalanced = computed(() => {
  return Math.abs(totalDebe.value - totalHaber.value) < 0.01;
});

// Funciones para manejar los detalles
const addDetalle = () => {
  form.detalles.push({
    cuenta_id: '',
    centro_costos_id: '',
    debe: 0,
    haber: 0,
    descripcion: '',
    deducible: false
  });

  nextTick(() => {
    initTomSelect(form.detalles.length - 1);
  });
};

const removeDetalle = (index) => {
  if (form.detalles.length > 1) {
    if (tomSelectInstances.value[index]) {
      tomSelectInstances.value[index].destroy();
      tomSelectInstances.value.splice(index, 1);
    }
    form.detalles.splice(index, 1);
    cuentaSelectRefs.value.splice(index, 1);
  }
};

// Modificar funciones de actualización para redondear
const updateDebe = (index) => {
  const val = roundToTwo(form.detalles[index].debe);
  form.detalles[index].debe = val;
  if (val > 0) {
    form.detalles[index].haber = 0;
  }
};

const updateHaber = (index) => {
  const val = roundToTwo(form.detalles[index].haber);
  form.detalles[index].haber = val;
  if (val > 0) {
    form.detalles[index].debe = 0;
  }
};

// Formatear montos como moneda
const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-CL', {
    style: 'currency',
    currency: 'CLP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(roundToTwo(value));
};


// Función para enviar el formulario
const submit = () => {
  if (!isBalanced.value) {
    form.setError('balance', 'El asiento no está balanceado (diferencia mayor a 0.01)');
    return;
  }

  // Opcional: redondear los detalles antes de enviar
  form.detalles.forEach(detalle => {
    detalle.debe = roundToTwo(detalle.debe);
    detalle.haber = roundToTwo(detalle.haber);
  });

  form.put(route('asientos.update', props.asiento.id), {
    preserveScroll: true,
    onSuccess: () => {},
    onError: (errors) => {
      console.log('Errores:', errors);
    }
  });
};

// Inicializar TomSelect cuando el componente se monte
onMounted(() => {
  nextTick(() => {
    initAllTomSelects();
  });
});

// Limpiar instancias al desmontar
onBeforeUnmount(() => {
  tomSelectInstances.value.forEach(instance => {
    if (instance && instance.destroy) {
      instance.destroy();
    }
  });
});
</script>

<style>
/* Importar estilos de Tom Select */
@import 'tom-select/dist/css/tom-select.css';

/* Personalizar estilos - Versión compacta */
.ts-control {
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  padding: 0.25rem 0.5rem !important;
  background-color: white !important;
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
  min-height: 34px !important;
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
  background-size: 1.2em 1.2em !important;
  padding-right: 2rem !important;
}

.ts-dropdown {
  border: 1px solid #d1d5db !important;
  border-radius: 0.375rem !important;
  margin-top: 0.25rem !important;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
  background-color: white !important;
  max-height: 300px !important;
  overflow-y: auto !important;
}

.ts-dropdown .option {
  padding: 0.5rem 0.75rem !important;
  border-bottom: 1px solid #f3f4f6 !important;
}

.ts-dropdown .option:last-child {
  border-bottom: none !important;
}

.ts-dropdown .active {
  background-color: #f9fafb !important;
  color: #374151 !important;
}

.ts-dropdown .selected {
  background-color: #e0e7ff !important;
  color: #3730a3 !important;
}

.ts-dropdown .create {
  color: #059669 !important;
}

/* Botón clear */
.ts-control .clear-button {
  color: #9ca3af !important;
  padding: 2px !important;
}

.ts-control .clear-button:hover {
  color: #6b7280 !important;
}

/* Input dentro de TomSelect */
.ts-control input {
  color: #111827 !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  margin: 0 !important;
  padding: 0 !important;
}

.ts-control input::placeholder {
  color: #9ca3af !important;
}

/* Asegurar que el select ocupe todo el ancho */
select[ref] {
  width: 100%;
}

/* Campo de solo lectura */
input[readonly] {
  background-color: #f9fafb !important;
  cursor: not-allowed;
}
</style>
