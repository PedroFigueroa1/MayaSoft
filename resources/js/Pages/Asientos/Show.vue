<template>
  <AppLayoutAdmin>
    <div class="py-4">
      <div class="max-w-6xl mx-auto bg-white shadow-lg rounded-lg overflow-hidden print:shadow-none">
        <!-- Encabezado compacto -->
        <div class="px-6 py-4 border-b border-gray-200 print:border-b">
          <div class="flex flex-col md:flex-row justify-between items-start md:items-center">
            <!-- Logo y datos básicos de empresa -->
            <div class="flex items-center mb-4 md:mb-0">
              <div v-if="empresa.logo" class="mr-4">
                <img
                    v-if="empresa.logo"
                    :src="`/imagenes/logo/${empresa.logo}`"
                    :alt="`Logo de ${empresa.nombre}`"
                    class="h-10 w-10 rounded-full object-cover border border-gray-200"
                    />
              </div>
              <div>
                <h1 class="text-xl font-bold text-gray-900 print:text-lg">{{ empresa.nombre || 'Empresa' }}</h1>
                <div class="flex flex-wrap gap-x-4 gap-y-1 mt-1">
                  <span class="text-xs text-gray-600">{{ empresa.nit ? `NIT: ${empresa.nit}` : '' }}</span>
                  <span v-if="empresa.telefono" class="text-xs text-gray-600">Tel: {{ empresa.telefono }}</span>
                </div>
              </div>
            </div>

            <!-- Título principal centrado -->
            <div class="text-center mb-4 md:mb-0 md:flex-1 md:mx-8">
              <h2 class="text-lg font-semibold text-gray-800 print:text-base">ASIENTO CONTABLE</h2>
              <div class="mt-1">
                <span class="text-sm font-medium text-gray-700">N° {{ asiento.numero_asiento }}</span>
                <span class="mx-2 text-gray-400">•</span>
                <span class="text-sm text-gray-600">{{ formatDate(asiento.fecha) }}</span>
              </div>
            </div>

            <!-- Información del asiento -->
            <div class="text-right">
              <div class="inline-flex flex-col items-end">
                <div class="mb-2">
                  <span class="text-xs font-medium text-gray-500 block">Periodo:</span>
                  <span class="text-xs text-gray-700">{{ asiento.periodo.nombre }}</span>
                </div>
                <div>
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold" :class="tipoClass(asiento.tipo)">
                    {{ tipoText(asiento.tipo) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Información secundaria compacta -->
        <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 print:bg-gray-50">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between">
            <div class="mb-2 md:mb-0">
              <div class="text-xs text-gray-500">Dirección:</div>
              <div class="text-xs text-gray-700">{{ empresa.direccion || 'No especificada' }}</div>
              <div v-if="empresa.ciudad || empresa.pais" class="text-xs text-gray-600">
                {{ [empresa.ciudad, empresa.pais].filter(Boolean).join(', ') }}
              </div>
            </div>

            <div class="text-right">
              <div class="text-xs text-gray-500">Comprobante:</div>
              <div class="text-xs text-gray-700">{{ asiento.comprobante || 'N/A' }}</div>
            </div>
          </div>

          <!-- Glosa compacta -->
          <div class="mt-3 pt-3 border-t border-gray-200 md:border-t-0 md:pt-0">
            <div class="text-xs text-gray-500">Glosa:</div>
            <div class="text-xs text-gray-700 line-clamp-2">{{ asiento.glosa }}</div>
          </div>
        </div>

        <!-- Detalles del asiento (sin cambios) -->
        <div class="p-6">
          <h3 class="text-lg font-medium text-gray-900 mb-4 print:text-base">Detalles del Asiento</h3>

          <div class="overflow-x-auto print:overflow-visible">
            <table class="min-w-full divide-y divide-gray-200 print:table-fixed">
              <thead class="bg-gray-50 print:bg-gray-100">
                <tr>
                  <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                    Código
                  </th>
                  <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Cuenta
                  </th>
                  <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                    Centro Costo
                  </th>
                  <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Descripción
                  </th>
                  <th scope="col" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                    Deducible
                  </th>
                  <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                    Debe
                  </th>
                  <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                    Haber
                  </th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="(detalle, index) in (asiento?.detalles ?? [])" :key="detalle?.id ?? index" class="print:break-inside-avoid">
                  <!-- Código de cuenta -->
                  <td class="px-4 py-2 whitespace-nowrap text-xs font-mono text-gray-500">
                    {{ detalle?.cuenta?.codigo ?? '—' }}
                  </td>

                  <!-- Nombre de cuenta -->
                  <td class="px-4 py-2 text-xs text-gray-600">
                    {{ detalle?.cuenta?.nombre ?? '—' }}
                  </td>

                  <!-- Centro de Costos -->
                  <td class="px-4 py-2 text-xs text-gray-600">
                    <template v-if="detalle?.centro_costo">
                      <div>{{ detalle.centro_costo.nombre }}</div>
                      <div class="text-gray-400">({{ detalle.centro_costo.codigo }})</div>
                    </template>
                    <template v-else>
                      <span class="text-gray-400">—</span>
                    </template>
                  </td>

                  <!-- Descripción -->
                  <td class="px-4 py-2 text-xs text-gray-600">
                    {{ detalle?.descripcion || '—' }}
                  </td>

                  <!-- Deducible -->
                  <td class="px-4 py-2 whitespace-nowrap text-center">
                    <span v-if="detalle?.deducible" class="inline-flex items-center justify-center w-8 h-5 rounded text-xs font-medium bg-green-100 text-green-800 print:bg-green-50">
                      No
                    </span>
                    <span v-else class="inline-flex items-center justify-center w-8 h-5 rounded text-xs font-medium bg-gray-100 text-gray-800 print:bg-gray-50">
                      Sí
                    </span>
                  </td>

                  <!-- Debe -->
                  <td class="px-4 py-2 whitespace-nowrap text-xs text-right">
                    <span v-if="detalle.debe > 0" class="font-medium text-red-600">
                      {{ formatCurrency(detalle.debe) }}
                    </span>
                    <span v-else class="text-gray-400">—</span>
                  </td>

                  <!-- Haber -->
                  <td class="px-4 py-2 whitespace-nowrap text-xs text-right">
                    <span v-if="detalle.haber > 0" class="font-medium text-green-600">
                      {{ formatCurrency(detalle.haber) }}
                    </span>
                    <span v-else class="text-gray-400">—</span>
                  </td>
                </tr>

                <!-- Totales -->
                <tr class="bg-gray-50 font-bold print:bg-gray-100">
                  <td colspan="5" class="px-4 py-3 text-right text-sm text-gray-700">TOTALES:</td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right border-l border-gray-200">
                    {{ formatCurrency(totalDebe) }}
                  </td>
                  <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 text-right">
                    {{ formatCurrency(totalHaber) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Información de auditoría (más compacta) -->
          <div class="mt-6 pt-4 border-t border-gray-200 text-xs text-gray-500">
            <div class="flex justify-between">
              <div>
                <span class="font-medium">Creado por:</span> {{ asiento.creado_por?.name || 'N/A' }}
                <span class="mx-2">•</span>
                {{ formatDateTime(asiento.created_at) }}
              </div>
              <div v-if="asiento.updated_at !== asiento.created_at">
                <span class="font-medium">Modificado:</span> {{ formatDateTime(asiento.updated_at) }}
              </div>
            </div>
          </div>

          <!-- Resúmenes (solo se muestran si aplican) -->
          <div v-if="tieneCentrosCostos" class="mt-4 p-3 bg-blue-50 rounded-lg print:bg-blue-50">
            <h4 class="text-xs font-medium text-blue-900 mb-2">Distribución por Centros de Costos:</h4>
            <div class="flex flex-wrap gap-3">
              <div v-for="centro in centrosCostosUtilizados" :key="centro.id" class="text-xs">
                <span class="font-medium text-blue-700">{{ centro.codigo }}:</span>
                <span class="text-blue-600 ml-1">{{ formatCurrency(centro.total) }}</span>
              </div>
            </div>
          </div>

          <div v-if="tieneGastosDeducibles" class="mt-3 p-3 bg-green-50 rounded-lg print:bg-green-50">
            <div class="flex justify-between items-center">
              <span class="text-xs font-medium text-green-900">Total gastos deducibles:</span>
              <span class="text-sm font-medium text-green-800">{{ formatCurrency(totalDeducible) }}</span>
            </div>
          </div>
        </div>

        <!-- Botones de acción (se ocultan al imprimir) -->
  <div class="p-4 bg-gray-50 flex justify-between print:hidden">
    <Link
      :href="route('asientos.index', asiento.periodo.id)"
      class="inline-flex items-center px-4 py-2 bg-gray-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
    >
      ← Volver
    </Link>
    <div>
      <!-- Botón para generar PDF -->
      <a :href="route('asientos.pdf.download', asiento.id)"
         class="inline-flex items-center px-4 py-2 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring focus:ring-blue-300 disabled:opacity-25 transition mr-3">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        Descargar PDF
      </a>


      <Link
        :href="route('asientos.edit', asiento.id)"
        class="inline-flex items-center px-4 py-2 bg-yellow-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 active:bg-yellow-900 focus:outline-none focus:border-yellow-900 focus:ring focus:ring-yellow-300 disabled:opacity-25 transition"
      >
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Editar
      </Link>
    </div>
  </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const props = defineProps({
  asiento: Object,
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

// Computed properties (sin cambios)
const totalDebe = computed(() => {
  return props.asiento.detalles.reduce((sum, detalle) => sum + parseFloat(detalle.debe || 0), 0);
});

const totalHaber = computed(() => {
  return props.asiento.detalles.reduce((sum, detalle) => sum + parseFloat(detalle.haber || 0), 0);
});

const totalDeducible = computed(() => {
  return props.asiento.detalles
    .filter(detalle => detalle.deducible)
    .reduce((sum, detalle) => sum + parseFloat(detalle.debe || 0), 0);
});

const tieneGastosDeducibles = computed(() => {
  return totalDeducible.value > 0;
});

const centrosCostosUtilizados = computed(() => {
  const centrosMap = new Map();

  props.asiento.detalles.forEach(detalle => {
    if (detalle.centro_costo) {
      const centroId = detalle.centro_costo.id;
      const monto = parseFloat(detalle.debe || 0) + parseFloat(detalle.haber || 0);

      if (centrosMap.has(centroId)) {
        centrosMap.set(centroId, centrosMap.get(centroId) + monto);
      } else {
        centrosMap.set(centroId, monto);
      }
    }
  });

  return Array.from(centrosMap.entries()).map(([id, total]) => {
    const centro = props.asiento.detalles.find(d => d.centro_costo?.id === id)?.centro_costo;
    return {
      id,
      codigo: centro?.codigo,
      nombre: centro?.nombre,
      total
    };
  });
});

const tieneCentrosCostos = computed(() => {
  return centrosCostosUtilizados.value.length > 0;
});

// Funciones (sin cambios)
const formatDate = (dateString) => {
  if (!dateString) return 'N/D';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES');
};

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/D';
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit' });
};

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-BO', {
    style: 'currency',
    currency: 'BOB',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
  }).format(value);
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

const imprimirDocumento = () => {
  window.print();
};
</script>

<style>
@media print {
  @page {
    margin: 0.5cm;
  }

  body * {
    visibility: hidden;
  }

  .max-w-6xl, .max-w-6xl * {
    visibility: visible;
  }

  .max-w-6xl {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    box-shadow: none;
    margin: 0;
    padding: 0;
  }

  .print\:hidden {
    display: none !important;
  }

  .print\:bg-gray-50 {
    background-color: #f9fafb !important;
  }

  .print\:bg-gray-100 {
    background-color: #f3f4f6 !important;
  }

  .print\:bg-blue-50 {
    background-color: #eff6ff !important;
  }

  .print\:bg-green-50 {
    background-color: #f0fdf4 !important;
  }

  .print\:table-fixed {
    table-layout: fixed;
  }

  .print\:text-base {
    font-size: 14px !important;
  }

  .print\:text-lg {
    font-size: 16px !important;
  }

  .print\:shadow-none {
    box-shadow: none !important;
  }

  .print\:overflow-visible {
    overflow: visible !important;
  }

  .break-inside-avoid {
    break-inside: avoid;
  }

  /* Asegurar que las tablas no se dividan mal */
  table {
    break-inside: auto;
  }

  tr {
    break-inside: avoid;
    break-after: auto;
  }

  td, th {
    padding: 4px 8px !important;
  }
}
</style>
