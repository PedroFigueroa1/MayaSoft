<template>
    <AppLayoutAdmin>
        <Head title="Libro Diario" />

        <div id="documento" class="max-w-7xl mx-auto p-4">
            <!-- Encabezado compacto -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden mb-4">
                <!-- Fila superior con logo, título y resumen -->
                <div class="p-3 border-b border-gray-200 grid grid-cols-3 items-center">
                    <!-- Izquierda: Empresa -->
                    <div class="flex items-center">
                        <img
                            v-if="empresa.logo"
                            :src="`/imagenes/logo/${empresa.logo}`"
                            :alt="`Logo de ${empresa.nombre}`"
                            class="h-8 w-8 rounded-full object-cover border border-gray-200 mr-2"
                            @error="handleImageError"
                        />
                        <div>
                            <h1 class="text-sm font-bold text-gray-900">{{ empresa.nombre || 'Nombre de Empresa' }}</h1>
                            <p class="text-xs text-gray-600">{{ empresa.nit ? `NIT: ${empresa.nit}` : '' }}</p>
                        </div>
                    </div>

                    <!-- Centro: Título LIBRO DIARIO -->
                    <div class="text-center">
                        <h2 class="text-lg font-bold text-gray-800">LIBRO DIARIO</h2>
                        <p class="text-xs text-gray-600">{{ periodo?.nombre || 'Período no disponible' }}</p>
                        <p class="text-xs text-gray-500">
                            {{ periodo?.fecha_inicio ? formatDate(periodo.fecha_inicio) : '--/--/----' }} -
                            {{ periodo?.fecha_cierre ? formatDate(periodo.fecha_cierre) : '--/--/----' }}
                        </p>
                    </div>

                    <!-- Derecha: Resumen rápido -->
                    <div class="flex justify-end space-x-3 text-xs">
                        <div>
                            <span class="text-gray-500">Debe:</span>
                            <span class="ml-1 font-semibold text-blue-600">{{ formatNumber(totalDebe) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">Haber:</span>
                            <span class="ml-1 font-semibold text-green-600">{{ formatNumber(totalHaber) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Segunda fila: Dirección y teléfono compactos -->
                <div class="px-3 py-1 bg-gray-50 border-b border-gray-200 flex justify-between text-xs text-gray-500">
                    <p>{{ empresa.direccion || 'Dirección no especificada' }}</p>
                    <p>{{ empresa.telefono ? `Tel: ${empresa.telefono}` : '' }}</p>
                    <p>{{ empresa.ciudad ? `${empresa.ciudad}, ${empresa.pais || ''}` : '' }}</p>
                </div>

                <!-- Controles compactos con validación -->
                <div class="px-3 py-2 bg-white border-b border-gray-200 flex justify-between items-center">
                    <span class="text-xs text-gray-600">
                        Mostrando {{ asientos?.length || 0 }} asientos contables
                    </span>
                    <div class="flex space-x-2">
                        <!-- Validación para mostrar los botones solo si hay ID -->
                        <template v-if="periodo && periodo.id">
                            <a
                                :href="`/libro-diario/${periodo.id}/pdf`"
                                class="inline-flex items-center px-3 py-1 bg-gray-600 text-xs text-white rounded hover:bg-gray-700"
                                @click="handleExportClick('PDF')"
                            >
                                📄 PDF
                        </a>
                            <a
                            :href="`/libro-diario/${periodo.id}/excel`"
                            target="_blank"
                            class="inline-flex items-center px-3 py-1 bg-green-600 text-xs text-white rounded hover:bg-green-700"
                            >
                            Excel
                            </a>
                        </template>
                        <div v-else class="text-red-500 text-xs">
                            ⚠️ ID del período no disponible
                        </div>
                    </div>
                </div>

                <!-- Tabla de asientos -->
                <div v-if="asientos && asientos.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asiento</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cuenta</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Centro Costo</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Deducible</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debe</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Haber</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <template v-for="asiento in asientos" :key="asiento.id">
                                <tr v-for="(detalle, index) in asiento.detalles" :key="detalle.id" class="hover:bg-gray-50">
                                    <!-- Columnas que se repiten por asiento -->
                                    <td v-if="index === 0" class="px-4 py-3 whitespace-nowrap border-l" :rowspan="asiento.detalles.length">
                                        {{ formatDate(asiento.fecha) }}
                                    </td>
                                    <td v-if="index === 0" class="px-4 py-3 whitespace-nowrap" :rowspan="asiento.detalles.length">
                                        {{ asiento.numero_asiento }}
                                    </td>
                                    <td v-if="index === 0" class="px-4 py-3 whitespace-nowrap" :rowspan="asiento.detalles.length">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full" :class="tipoClass(asiento.tipo)">
                                            {{ tipoText(asiento.tipo) }}
                                        </span>
                                    </td>

                                    <!-- Columnas por detalle -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <template v-if="detalle.cuenta">
                                            <span class="font-mono text-gray-600">{{ detalle.cuenta.codigo }}</span>
                                            <div class="text-sm">{{ detalle.cuenta.nombre }}</div>
                                        </template>
                                        <template v-else>
                                            <span class="text-red-500 text-sm">Cuenta no disponible</span>
                                        </template>
                                    </td>

                                    <!-- Centro de Costos -->
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <template v-if="detalle.centro_costo">
                                            <span class="font-mono text-xs text-gray-500">{{ detalle.centro_costo.codigo }}</span>
                                            <div class="text-xs text-gray-600">{{ detalle.centro_costo.nombre }}</div>
                                        </template>
                                        <template v-else>
                                            <span class="text-gray-400 text-xs">-</span>
                                        </template>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ detalle.descripcion ? detalle.descripcion : asiento.glosa }}
                                        </div>
                                    </td>

                                    <!-- Deducible -->
                                    <td class="px-4 py-3 whitespace-nowrap text-center">
                                        <span v-if="detalle.deducible" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                            No
                                        </span>
                                        <span v-else class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Si
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-sm">
                                        <span v-if="detalle.debe > 0" class="text-red-600">
                                            {{ formatNumber(detalle.debe) }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-right font-mono text-sm">
                                        <span v-if="detalle.haber > 0" class="text-green-600">
                                            {{ formatNumber(detalle.haber) }}
                                        </span>
                                        <span v-else class="text-gray-400">-</span>
                                    </td>
                                </tr>
                            </template>

                            <!-- Totales -->
                            <tr class="bg-gray-100 font-bold">
                                <td colspan="7" class="px-4 py-4 whitespace-nowrap text-right">TOTALES GENERALES</td>
                                <td class="px-4 py-4 whitespace-nowrap text-right font-mono text-blue-600">
                                    {{ formatNumber(totalDebe) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right font-mono text-green-600">
                                    {{ formatNumber(totalHaber) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Mensaje cuando no hay asientos -->
                <div v-else class="p-8 text-center text-gray-500">
                    No hay asientos contables para este período
                </div>

                <!-- Pie de página -->
                <div class="p-2 bg-gray-50 text-xs text-gray-500 text-center">
                    <p>Generado el {{ fechaGeneracion }} | Sistema Contable</p>
                </div>
            </div>

            <!-- Debug info (remover en producción) -->
            <div v-if="!periodo || !periodo.id" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg text-xs text-red-600">
                <strong>Error de depuración:</strong><br>
                Periodo: {{ JSON.stringify(periodo) }}<br>
                ID disponible: {{ periodo?.id ? 'Sí' : 'No' }}
            </div>
        </div>

    </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted } from 'vue';

const props = defineProps({
  asientos: {
    type: Array,
    default: () => []
  },
  periodo: {
    type: Object,
    default: () => null
  },
  totalDebe: {
    type: Number,
    default: 0
  },
  totalHaber: {
    type: Number,
    default: 0
  },
  empresa: {
    type: Object,
    default: () => ({
      nombre: '',
      nit: '',
      direccion: '',
      telefono: '',
      ciudad: '',
      pais: '',
      logo: ''
    })
  }
});

// Validación al montar el componente
onMounted(() => {
    console.log('=== LIBRO DIARIO DEBUG ===');
    console.log('Periodo recibido:', props.periodo);
    console.log('Periodo ID:', props.periodo?.id);
    console.log('Asientos count:', props.asientos?.length);
    console.log('========================');

    if (!props.periodo || !props.periodo.id) {
        console.error('ERROR: Período no disponible o sin ID', props.periodo);
    }
});

// Computed properties
const fechaGeneracion = computed(() => {
  return new Date().toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
});

// Funciones
const formatDate = (dateString) => {
  if (!dateString) return '--/--/----';
  try {
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '--/--/----';
    const dia = String(date.getDate()).padStart(2, '0');
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const anio = date.getFullYear();
    return `${dia}/${mes}/${anio}`;
  } catch (error) {
    console.error('Error formatting date:', error);
    return '--/--/----';
  }
};

const formatNumber = (value) => {
  if (value === undefined || value === null) return '0.00';
  try {
    return new Intl.NumberFormat('es-BO', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(value);
  } catch (error) {
    console.error('Error formatting number:', error);
    return '0.00';
  }
};

const tipoClass = (tipo) => {
  const classes = {
    ING: 'bg-green-100 text-green-800',
    EGR: 'bg-red-100 text-red-800',
    TRN: 'bg-blue-100 text-blue-800',
    AJT: 'bg-yellow-100 text-yellow-800',
    CIE: 'bg-purple-100 text-purple-800'
  };
  return classes[tipo] || 'bg-gray-100 text-gray-800';
};

const tipoText = (tipo) => {
  const texts = {
    ING: 'Ingreso',
    EGR: 'Egreso',
    TRN: 'Traspaso',
    AJT: 'Ajuste',
    CIE: 'Cierre'
  };
  return texts[tipo] || tipo;
};

// Función para obtener la URL correcta del logo
const getLogoUrl = (logoPath) => {
    if (!logoPath) return null;
    if (logoPath.startsWith('http')) return logoPath;
    if (logoPath.includes('storage/')) return `/${logoPath}`;
    if (logoPath.startsWith('logos/')) return `/storage/${logoPath}`;
    return `/storage/${logoPath}`;
};

// Manejo de error de imagen
const handleImageError = (e) => {
    console.warn('Error loading logo:', e.target.src);
    e.target.style.display = 'none';
    const parent = e.target.parentNode;
    const placeholder = document.createElement('div');
    placeholder.className = 'h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center mr-2';
    placeholder.innerHTML = '<span class="text-xs text-gray-500">🏢</span>';
    parent.insertBefore(placeholder, e.target);
    e.target.remove();
};

// Manejar clics en exportación
const handleExportClick = (type) => {
    console.log(`Exportando a ${type}...`);
    console.log(`URL: /libro-diario/${props.periodo?.id}/${type.toLowerCase()}`);
};

const exportTo = async (type) => {
    if (!props.periodo?.id) {
        console.error('No hay ID de período');
        alert('Error: ID de período no disponible');
        return;
    }

    const url = `/libro-diario/${props.periodo.id}/${type}`;
    console.log('Intentando exportar a:', url);

    try {
        // Probar si la URL es accesible
        const response = await fetch(url, { method: 'HEAD' });
        console.log('Respuesta del servidor:', response.status, response.statusText);

        if (response.ok) {
            if (type === 'preview') {
                window.open(url, '_blank');
            } else {
                window.location.href = url;
            }
        } else {
            console.error('Error en el servidor:', response.status);
            alert(`Error ${response.status}: No se pudo generar el archivo`);
        }
    } catch (error) {
        console.error('Error de red:', error);
        alert('Error de conexión. Revisa la consola para más detalles.');
    }
};
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }

  #documento, #documento * {
    visibility: visible;
  }

  #documento {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}

table {
  border-collapse: collapse;
}

th, td {
  border: 1px solid #e2e8f0;
}

.border-l {
  border-left: 1px solid #e2e8f0;
}
</style>
