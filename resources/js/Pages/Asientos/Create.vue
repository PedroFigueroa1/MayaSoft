<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Crear Nuevo Asiento
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <!-- Mostrar errores generales -->
            <div v-if="errors.balance" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
              {{ errors.balance }}
            </div>

            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-6 gap-4 mb-6">
                <!-- Campo de período fijo -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="periodo_id">
                    Periodo *
                  </label>
                    <input
                    type="text"
                    :value="periodo.nombre"
                    id="periodo_id"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    readonly
                    required
                    >
                    <input type="hidden" v-model="form.periodo_id">
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
                    :class="{ 'border-red-500': errors.fecha }"
                    required
                  >
                  <p v-if="errors.fecha" class="text-red-500 text-xs italic">{{ errors.fecha }}</p>
                </div>

                <div>
                  <label class="block text-gray-700 text-sm font-bold mb-1" for="tipo">
                    Tipo *
                  </label>
                  <select
                    v-model="form.tipo"
                    id="tipo"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': errors.tipo }"
                    required
                  >
                    <option value="">Seleccionar Tipo</option>
                    <option value="ING">Ingreso (CI-XXXX)</option>
                    <option value="EGR">Egreso (CE-XXXX)</option>
                    <option value="TRN">Traspaso (CT-XXXX)</option>
                    <option value="AJT">Ajuste (CA-XXXX)</option>
                    <option value="CIE">Cierre (CC-XXXX)</option>
                  </select>
                  <p v-if="errors.tipo" class="text-red-500 text-xs italic">{{ errors.tipo }}</p>
                </div>

                <!-- Campo de número de asiento - Ahora es informativo -->
                <div>
                <label class="block text-gray-700 text-sm font-bold mb-1" for="numero_asiento">
                    Número de Asiento
                </label>
                <input
                    type="text"
                    :value="numeroAsientoPreview"
                    id="numero_asiento"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 bg-gray-100 leading-tight focus:outline-none focus:shadow-outline font-mono text-sm"
                    readonly
                    placeholder="Se generará automáticamente al guardar"
                    :title="'Se asigna al guardar | Formato: ' + tipoPreview + ' seguido de 4 dígitos (ej: ' + tipoPreview + '0001)'"
                >
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
                    :class="{ 'border-red-500': errors.comprobante }"
                  >
                  <p v-if="errors.comprobante" class="text-red-500 text-xs italic">{{ errors.comprobante }}</p>
                </div>
              </div>

              <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-1" for="glosa">
                  Glosa *
                </label>
                <textarea
                  v-model="form.glosa"
                  id="glosa"
                  rows="1"
                  class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                  :class="{ 'border-red-500': errors.glosa }"
                  required
                ></textarea>
                <p v-if="errors.glosa" class="text-red-500 text-xs italic">{{ errors.glosa }}</p>
              </div>

              <h3 class="text-base font-medium text-gray-900 mb-3">Detalles del Asiento</h3>

              <div v-for="(detalle, index) in form.detalles" :key="index" class="grid grid-cols-1 md:grid-cols-12 gap-2 mb-2 items-center" :class="index > 0 ? 'pt-2 border-t border-gray-100' : ''">
                <!-- Cuenta con TomSelect -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Cuenta *
                  </label>
                  <select
                    :ref="el => { cuentaSelectRefs[index] = el }"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': errors[`detalles.${index}.cuenta_id`] }"
                  >
                    <option value="">Seleccionar Cuenta</option>
                  </select>
                  <p v-if="errors[`detalles.${index}.cuenta_id`]" class="text-red-500 text-xs italic">{{ errors[`detalles.${index}.cuenta_id`] }}</p>
                </div>

                <!-- Centro de Costos -->
                <div class="col-span-2">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    Centro Costos
                  </label>
                  <select
                    v-model="detalle.centro_costos_id"
                    class="shadow appearance-none border rounded w-full py-1 px-2 text-gray-700 leading-tight focus:outline-none focus:shadow-outline text-sm"
                    :class="{ 'border-red-500': errors[`detalles.${index}.centro_costos_id`] }"
                  >
                    <option value="">Sin Centro</option>
                    <option v-for="centro in centrosCostos" :key="centro.id" :value="centro.id">
                      {{ centro.codigo }} - {{ centro.nombre }}
                    </option>
                  </select>
                  <p v-if="errors[`detalles.${index}.centro_costos_id`]" class="text-red-500 text-xs italic">{{ errors[`detalles.${index}.centro_costos_id`] }}</p>
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
                    :class="{ 'border-red-500': errors[`detalles.${index}.descripcion`] }"
                  >
                  <p v-if="errors[`detalles.${index}.descripcion`]" class="text-red-500 text-xs italic">{{ errors[`detalles.${index}.descripcion`] }}</p>
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
                    :class="{ 'border-red-500': errors[`detalles.${index}.debe`] }"
                  >
                  <p v-if="errors[`detalles.${index}.debe`]" class="text-red-500 text-xs italic">{{ errors[`detalles.${index}.debe`] }}</p>
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
                    :class="{ 'border-red-500': errors[`detalles.${index}.haber`] }"
                  >
                  <p v-if="errors[`detalles.${index}.haber`]" class="text-red-500 text-xs italic">{{ errors[`detalles.${index}.haber`] }}</p>
                </div>

                <!-- Deducible -->
                <div class="col-span-1">
                  <label class="block text-gray-700 text-xs font-medium mb-0.5">
                    D/ND
                  </label>
                  <div
                    class="shadow appearance-none border rounded w-full py-1 px-2 flex items-center justify-center h-[34px]"
                    :class="{ 'border-red-500': errors[`detalles.${index}.deducible`] }"
                  >
                    <input
                      type="checkbox"
                      v-model="detalle.deducible"
                      class="rounded border-gray-300 text-blue-600 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    >
                  </div>
                  <p v-if="errors[`detalles.${index}.deducible`]" class="text-red-500 text-xs italic">
                    {{ errors[`detalles.${index}.deducible`] }}
                  </p>
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
              <div class="flex flex-col md:flex-row justify-between items-center mt-4 mb-6 p-3 bg-gray-50 rounded-md">
                <div class="font-bold text-gray-700 mb-2 md:mb-0">
                  Totales:
                </div>

                <div class="flex gap-4 sm:gap-6 lg:gap-10">
                  <div class="text-gray-700 font-medium"
                       :class="{ 'text-green-600': totalDebe === totalHaber, 'text-red-600': totalDebe !== totalHaber }">
                    {{ formatCurrency(totalDebe) }}
                  </div>
                  <div class="text-gray-700 font-medium"
                       :class="{ 'text-green-600': totalDebe === totalHaber, 'text-red-600': totalDebe !== totalHaber }">
                    {{ formatCurrency(totalHaber) }}
                  </div>
                </div>
              </div>

              <div class="mb-6">
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
                  :disabled="isSubmitting || !form.tipo"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition"
                >
                  <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ isSubmitting ? 'Guardando...' : 'Guardar Asiento' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link } from '@inertiajs/vue3';
import TomSelect from 'tom-select';

export default {
  components: {
    AppLayoutAdmin,
    Link
  },
  props: {
    periodo: {
      type: Object,
      required: true
    },
    cuentas: Array,
    centrosCostos: Array,
    errors: Object
  },

  data() {
    const today = new Date();
    const formattedDate = today.toISOString().split('T')[0];

    return {
      form: {
        periodo_id: this.periodo.id ?? '',
        fecha: formattedDate,
        tipo: '',
        glosa: '',
        comprobante: '',
        detalles: [
          {
            cuenta_id: '',
            centro_costos_id: '',
            debe: 0,
            haber: 0,
            descripcion: '',
            deducible: false
          },
          {
            cuenta_id: '',
            centro_costos_id: '',
            debe: 0,
            haber: 0,
            descripcion: '',
            deducible: false
          }
        ]
      },
      cuentaSelectRefs: [],
      tomSelectInstances: [],
      isSubmitting: false
    }
  },

  computed: {
    totalDebe() {
      return this.form.detalles.reduce((total, detalle) => {
        return total + (parseFloat(detalle.debe) || 0);
      }, 0);
    },
    totalHaber() {
      return this.form.detalles.reduce((total, detalle) => {
        return total + (parseFloat(detalle.haber) || 0);
      }, 0);
    },
    tipoPreview() {
      const prefijos = {
        'ING': 'CI-',
        'EGR': 'CE-',
        'TRN': 'CT-',
        'AJT': 'CA-',
        'CIE': 'CC-'
      };
      return prefijos[this.form.tipo] || 'XX-';
    },
    numeroAsientoPreview() {
      if (!this.form.tipo) {
        return '';
      }
      return this.tipoPreview + 'XXXX';
    }
  },

  mounted() {
    this.$nextTick(() => {
      this.initAllTomSelects();
    });
  },

  methods: {
    initAllTomSelects() {
      this.form.detalles.forEach((detalle, index) => {
        this.initTomSelect(index);
      });
    },

    initTomSelect(index) {
      const selectElement = this.cuentaSelectRefs[index];
      if (!selectElement) return;

      if (this.tomSelectInstances[index]) {
        this.tomSelectInstances[index].destroy();
      }

      const tomSelect = new TomSelect(selectElement, {
        valueField: 'value',
        labelField: 'text',
        searchField: ['text', 'codigo', 'nombre'],
        options: this.cuentas.map(cuenta => ({
          value: cuenta.id,
          text: `${cuenta.codigo} - ${cuenta.nombre}`,
          codigo: cuenta.codigo,
          nombre: cuenta.nombre,
          tipo: cuenta.tipo
        })),
        items: this.form.detalles[index].cuenta_id ? [this.form.detalles[index].cuenta_id] : [],
        placeholder: 'Escribe código o nombre...',
        create: false,
        maxItems: 1,
        allowEmptyOption: true,
        closeAfterSelect: true,
        hideSelected: true,
        highlight: true,
        plugins: ['clear_button'],
        onChange: (value) => {
          this.form.detalles[index].cuenta_id = value || '';
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

      this.tomSelectInstances[index] = tomSelect;
    },

    addDetalle() {
      this.form.detalles.push({
        cuenta_id: '',
        centro_costos_id: '',
        debe: 0,
        haber: 0,
        descripcion: '',
        deducible: false
      });

      this.$nextTick(() => {
        this.initTomSelect(this.form.detalles.length - 1);
      });
    },

    removeDetalle(index) {
      if (this.form.detalles.length > 2) {
        if (this.tomSelectInstances[index]) {
          this.tomSelectInstances[index].destroy();
          this.tomSelectInstances.splice(index, 1);
        }

        this.form.detalles.splice(index, 1);
        this.cuentaSelectRefs.splice(index, 1);
      }
    },

    updateDebe(index) {
      if (parseFloat(this.form.detalles[index].haber) > 0) {
        this.form.detalles[index].debe = 0;
      }
    },

    updateHaber(index) {
      if (parseFloat(this.form.detalles[index].debe) > 0) {
        this.form.detalles[index].haber = 0;
      }
    },

    submit() {
      if (!this.form.tipo) {
        alert('Por favor, seleccione un tipo de asiento');
        return;
      }

      this.isSubmitting = true;

      this.$inertia.post(route('asientos.store'), this.form, {
        onFinish: () => {
          this.isSubmitting = false;
        },
        onError: (errors) => {
          if (errors.balance) {
            // El error se mostrará en la sección que agregamos arriba
          }
        }
      });
    },

    formatCurrency(value) {
    if (value === null || value === undefined) value = 0;
    return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: 'CLP',
        minimumFractionDigits: 2,
        maximumFractionDigits: 4
    }).format(value);
    }
  },

  beforeUnmount() {
    this.tomSelectInstances.forEach(instance => {
      if (instance && instance.destroy) {
        instance.destroy();
      }
    });
  }
}
</script>

<style>
/* Importar estilos de Tom Select */
@import 'tom-select/dist/css/tom-select.css';

/* Personalizar estilos */
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

/* Spinner personalizado */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Campo de solo lectura */
input[readonly] {
  background-color: #f9fafb !important;
  cursor: not-allowed;
}

/* Estilo para números de asiento */
.font-mono {
  font-family: monospace;
}
</style>
