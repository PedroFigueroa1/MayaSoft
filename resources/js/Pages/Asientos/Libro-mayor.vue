<template>
    <AppLayoutAdmin>
        <div class="container mx-auto p-4">
            <h1 class="text-2xl font-bold mb-6">Libro Mayor - Período: {{ periodo.nombre }}</h1>

            <!-- Mostrar errores -->
            <div v-if="errores.length" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <h3 class="font-bold mb-2">Error al generar reporte:</h3>
                <ul class="list-disc list-inside">
                    <li v-for="(error, index) in errores" :key="index">{{ error }}</li>
                </ul>
                <button @click="verLogs" class="text-blue-500 text-sm mt-2">
                    Ver detalles técnicos
                </button>
            </div>

            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <!-- Selección de cuenta -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="cuenta">
                            Cuenta
                        </label>
                        <select
                            v-model="filtros.cuenta_id"
                            id="cuenta"
                            class="w-full border rounded p-2"
                            :class="{'border-red-500': erroresCuenta}"
                        >
                            <option value="">Seleccione una cuenta</option>
                            <option v-for="cuenta in cuentas" :key="cuenta.id" :value="cuenta.id">
                                {{ cuenta.codigo }} - {{ cuenta.nombre }}
                            </option>
                        </select>
                        <p v-if="erroresCuenta" class="text-red-500 text-xs mt-1">{{ erroresCuenta }}</p>
                    </div>

                    <!-- Fecha inicio -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_inicio">
                            Fecha Inicio
                        </label>
                        <input
                            v-model="filtros.fecha_inicio"
                            type="date"
                            class="w-full border rounded p-2"
                            :class="{'border-red-500': erroresFechaInicio}"
                        >
                        <p v-if="erroresFechaInicio" class="text-red-500 text-xs mt-1">{{ erroresFechaInicio }}</p>
                    </div>

                    <!-- Fecha fin -->
                    <div>
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="fecha_cierre">
                            Fecha Cierre
                        </label>
                        <input
                            v-model="filtros.fecha_cierre"
                            type="date"
                            class="w-full border rounded p-2"
                            :class="{'border-red-500': erroresFechaCierre}"
                        >
                        <p v-if="erroresFechaCierre" class="text-red-500 text-xs mt-1">{{ erroresFechaCierre }}</p>
                    </div>
                </div>

                <!-- Información del período -->
                <div class="bg-blue-50 p-3 rounded mb-4">
                    <p class="text-sm text-blue-800">
                        <strong>Período:</strong> {{ periodo.nombre }} |
                        <strong>Del:</strong> {{ formatDate(periodo.fecha_inicio) }} |
                        <strong>Al:</strong> {{ formatDate(periodo.fecha_cierre) }}
                    </p>
                </div>

                <!-- Botón generar -->
                <div class="flex justify-end">
                    <button
                        @click="generarReporte"
                        :disabled="loading || !filtros.cuenta_id"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                        :class="{'opacity-50 cursor-not-allowed': loading || !filtros.cuenta_id}"
                    >
                        <span v-if="loading">Generando...</span>
                        <span v-else>Generar Reportee</span>
                    </button>
                </div>
            </div>
            <Link href="/libro-mayor/prueva" class="text-blue-500 underline">
            Ir a Prueva
            </Link>


            <!-- Loading -->
            <div v-if="loading" class="flex justify-center items-center h-64">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-blue-500"></div>
            </div>

            <!-- Reporte -->


        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { ref, reactive, computed, onMounted, } from 'vue';
import { Link } from '@inertiajs/vue3';

import axios from 'axios';
import Reporte from './Reporte.vue';

const props = defineProps({
  cuentas: { type: Array, default: () => [] },
  periodo: { type: Object, required: true } // ← Ahora recibimos el objeto periodo completo
});

// Filtros reactivos
const filtros = reactive({
  cuenta_id: '',
  periodo_id: props.periodo.id,
  fecha_inicio: props.periodo.fecha_inicio,
  fecha_cierre: props.periodo.fecha_cierre // ← Cambiado a fecha_cierre
});

// Estados
const reporte = ref(null);
const loading = ref(false);
const errores = ref([]);

// Errores específicos por campo
const erroresCuenta = computed(() => {
  return getErrorForField('cuenta_id');
});

const erroresFechaInicio = computed(() => {
  return getErrorForField('fecha_inicio');
});


const erroresFechaCierre = computed(() => {
  return getErrorForField('fecha_cierre');
});

// Función para formatear fechas
const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleDateString('es-ES');
};

// Función para obtener errores por campo
const getErrorForField = (field) => {
  const error = errores.value.find(err => err.includes(field));
  return error ? error.replace(`${field}: `, '') : '';
};

const generarReporte = async () => {
  if (!filtros.cuenta_id) {
    errores.value = ['Debe seleccionar una cuenta'];
    return;
  }

  loading.value = true;
  errores.value = [];
  reporte.value = null;

  try {
        console.log('Enviando solicitud con:', {
        cuenta_id: filtros.cuenta_id,
        periodo_id: filtros.periodo_id,
        fecha_inicio: filtros.fecha_inicio,
        fecha_cierre: filtros.fecha_cierre
        });

        const response = await axios.get('/libro-mayor/generar', {
        params: {
                cuenta_id: filtros.cuenta_id,
                periodo_id: filtros.periodo_id,
                fecha_inicio: filtros.fecha_inicio,
                fecha_cierre: filtros.fecha_cierre
            }
        });

    if (response.data.success) {
      reporte.value = response.data;
      console.log(response.data);
    } else {
      errores.value = [response.data.message || 'Error al generar el reporte'];
      if (response.data.error) {
        errores.value.push('Detalle: ' + response.data.error);
      }
    }

  } catch (error) {
    console.error('Error completo:', error);

    let errorMessage = 'Error de conexión';
    if (error.response?.data?.message) {
      errorMessage = error.response.data.message;
    } else if (error.response?.data?.error) {
      errorMessage = error.response.data.error;
    } else if (error.message) {
      errorMessage = error.message;
    }

    errores.value = [errorMessage];

    // Mostrar más detalles en consola
    console.error('Error response:', error.response?.data);
  } finally {
    loading.value = false;
  }
};


// Inicializar con los valores del período
onMounted(() => {
  filtros.fecha_inicio = props.periodo.fecha_inicio;
  filtros.fecha_cierre = props.periodo.fecha_cierre;
  filtros.periodo_id = props.periodo.id;
});

console.log(filtros.periodo_id);
console.log(props.periodo.fecha_cierre);
console.log(props.periodo.fecha_inicio);


const formatInputDate = (dateStr) => {
  if (!dateStr) return '';
  const d = new Date(dateStr);
  const yyyy = d.getFullYear();
  const mm = String(d.getMonth() + 1).padStart(2, '0');
  const dd = String(d.getDate()).padStart(2, '0');
  return `${yyyy}-${mm}-${dd}`;
};

onMounted(() => {
  filtros.fecha_inicio = formatInputDate(props.periodo.fecha_inicio);
  filtros.fecha_cierre = formatInputDate(props.periodo.fecha_cierre);
});

</script>
