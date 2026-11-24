<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Asientos Contables
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <div class="flex justify-between mb-6">
              <h3 class="text-lg font-medium">Lista de Asientos</h3>
              <div class="space-x-2">
                  <!-- Botón para importar -->

                <Link
                  :href="route('libro-compras.index', { periodoId: $page.props.periodoId })"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Importar Libro Compras
                </Link>
                <Link
                  :href="route('libro-ventas.index', { periodoId: $page.props.periodoId })"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    Importar Libro Ventas
                </Link>
                <Link
                  :href="route('asientos.create', { periodo: $page.props.periodoId })"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Nuevo Asiento
                </Link>
                </div>
            </div>

            <!-- Filtros de búsqueda -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
              <form @submit.prevent="applyFilters" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Número de Asiento</label>
                  <input
                    v-model="filters.numero_asiento"
                    type="text"
                    placeholder="Buscar por número..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Desde</label>
                  <input
                    v-model="filters.fecha_desde"
                    type="date"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Fecha Hasta</label>
                  <input
                    v-model="filters.fecha_hasta"
                    type="date"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Glosa</label>
                  <input
                    v-model="filters.glosa"
                    type="text"
                    placeholder="Buscar en glosa..."
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  />
                </div>

                <div class="flex items-end space-x-2">
                  <button
                    type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                  >
                    Buscar
                  </button>
                  <button
                    type="button"
                    @click="resetFilters"
                    class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
                  >
                    Limpiar
                  </button>
                </div>
              </form>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Número</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Glosa</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="asiento in asientos.data" :key="asiento.id">
                    <td class="px-6 py-4 whitespace-nowrap">{{ asiento.id }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ formatDate(asiento.fecha) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ asiento.numero_asiento }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="tipoClass(asiento.tipo)">
                        {{ tipoText(asiento.tipo) }}
                      </span>
                    </td>
                    <td class="px-6 py-4">{{ truncate(asiento.glosa, 50) }}</td>
                    <td class="px-6 py-4 whitespace-nowrap">{{ asiento.periodo.nombre }}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                      <Link :href="route('asientos.show', asiento.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                        Ver
                      </Link>
                      <Link :href="route('asientos.edit', asiento.id)" class="text-yellow-600 hover:text-yellow-900 mr-3">
                        Editar
                      </Link>
                      <button @click="confirmDelete(asiento.id)" class="text-red-600 hover:text-red-900">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                  <tr v-if="asientos.data.length === 0">
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                      No se encontraron asientos
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <pagination class="mt-6" :links="asientos.links" />
          </div>
        </div>
      </div>
    </div>



    <!-- Modal de importación -->
    <div v-if="showImportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-6 border-0 shadow-xl rounded-lg bg-white max-w-2xl">
        <!-- Cabecera -->
        <div class="flex justify-between items-center pb-3 border-b">
        <h3 class="text-xl font-semibold text-gray-800">Importar libro del SIN</h3>
        <button @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        </div>

        <!-- FORMULARIO (primero) -->
        <div class="mt-4 space-y-4">
        <!-- Tipo de libro -->
        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de libro</label>
            <select v-model="tipoImportacion" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="compras">📥 Libro de Compras</option>
            <option value="ventas">📤 Libro de Ventas</option>
            </select>
        </div>

        <!-- Archivo (drag & drop compacto) -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Archivo Excel (.xlsx, .xls, .csv)</label>
            <div
            class="relative border-2 border-dashed rounded-lg p-3 text-center cursor-pointer transition-colors"
            :class="dragActive ? 'border-indigo-500 bg-indigo-50' : 'border-gray-300 hover:border-indigo-400'"
            @dragover.prevent="dragActive = true"
            @dragleave.prevent="dragActive = false"
            @drop.prevent="handleDrop"
            @click="$refs.fileInput.click()"
            >
            <input
                ref="fileInput"
                type="file"
                accept=".xlsx, .xls, .csv"
                class="hidden"
                @change="handleFileChange"
            />
            <div class="flex items-center justify-center gap-2 text-sm text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <span v-if="!archivo">Haz clic o arrastra el archivo aquí</span>
                <span v-else class="font-medium text-indigo-600">{{ archivo.name }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Máximo 10 MB</p>
            </div>
        </div>
        </div>
        <!-- Mensajes de error -->
        <div v-if="importErrors" class="mt-4 p-3 bg-red-100 text-red-700 rounded-md text-sm">
        {{ importErrors }}
        </div>

        <!-- Botones de acción -->
        <div class="mt-6 flex justify-end gap-3">
        <button
            @click="showImportModal = false"
            class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 transition"
        >
            Cancelar
        </button>
        <button
            @click="importar"
            :disabled="!archivo || importing"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 transition flex items-center gap-2"
        >
            <span v-if="importing" class="inline-block w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></span>
            Importar
        </button>
        </div>
        <!-- INSTRUCCIONES (después del formulario) -->
        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200 text-sm text-gray-700">
        <div class="flex items-center gap-2 text-blue-800 font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Instrucciones para preparar el archivo
        </div>
        <ul class="list-disc pl-5 mt-2 space-y-1">
            <li>Descarga el <strong>Libro de Compras</strong> o <strong>Libro de Ventas</strong> desde el sistema del SIN (formato Excel).</li>
            <li>No modifiques los nombres de las columnas ni el orden. El archivo debe conservar la estructura original.</li>
            <li>Asegúrate de que el archivo contenga filas con estado <strong>"VALIDA"</strong> (las anuladas se ignoran automáticamente).</li>
            <li>Para <strong>Compras</strong>: el sistema usará las columnas: NIT, número factura, fecha, importe total, subtotal, crédito fiscal, cuenta debe, etc.</li>
            <li>Para <strong>Ventas</strong>: usará las columnas: fecha factura, número, importe total, subtotal, débito fiscal, cuenta debe/haber, etc.</li>
            <li>Una vez subido, se crearán asientos automáticos de tipo <strong>EGR</strong> (Compras) o <strong>ING</strong> (Ventas).</li>
            <li>Si alguna cuenta del Excel no existe en tu plan de cuentas, la importación fallará para esa fila (el error se mostrará al final).</li>
        </ul>
        </div>


    </div>
    </div>
  </AppLayoutAdmin>
</template>
<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();

const props = defineProps({
  asientos: Object,
  filters: Object
});

// Estado de los filtros
const filters = ref({
  numero_asiento: props.filters?.numero_asiento || '',
  fecha_desde: props.filters?.fecha_desde || '',
  fecha_hasta: props.filters?.fecha_hasta || '',
  glosa: props.filters?.glosa || ''
});

// Funciones para formatear datos
const formatDate = (dateString) => {
  const date = new Date(dateString);
  return date.toLocaleDateString('es-ES');
};

const truncate = (str, n) => {
  if (!str) return '';
  return (str.length > n) ? str.substr(0, n-1) + '...' : str;
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

// Función para aplicar filtros usando URL
const applyFilters = () => {
  // Construir la URL manualmente
  const periodoId = page.props.periodoId;
  let url = `/asientos/${periodoId}`;

  // Construir query string
  const params = new URLSearchParams();

  if (filters.value.numero_asiento) {
    params.append('numero_asiento', filters.value.numero_asiento);
  }
  if (filters.value.fecha_desde) {
    params.append('fecha_desde', filters.value.fecha_desde);
  }
  if (filters.value.fecha_hasta) {
    params.append('fecha_hasta', filters.value.fecha_hasta);
  }
  if (filters.value.glosa) {
    params.append('glosa', filters.value.glosa);
  }

  // Agregar query string si hay parámetros
  const queryString = params.toString();
  if (queryString) {
    url += '?' + queryString;
  }

  console.log('URL a navegar:', url);

  // Usar Inertia para navegar
  router.get(url, {}, {
    preserveState: true,
    replace: true
  });
};

// Función para resetear filtros
const resetFilters = () => {
  filters.value = {
    numero_asiento: '',
    fecha_desde: '',
    fecha_hasta: '',
    glosa: ''
  };

  // Navegar a la URL sin filtros
  const url = `/asientos/${page.props.periodoId}`;
  router.get(url, {}, {
    preserveState: true,
    replace: true
  });
};

// Función para eliminar
const confirmDelete = (id) => {
  if (confirm('¿Estás seguro de eliminar este asiento?')) {
    router.delete(`/asientos/${id}`);
  }
};

// --- Lógica de importación ---
const showImportModal = ref(false);
const tipoImportacion = ref('compras');
const archivo = ref(null);
const importing = ref(false);
const importErrors = ref(null);
const fileInput = ref(null);

const handleFileChange = (e) => {
  archivo.value = e.target.files[0];
  importErrors.value = null;
};

const importar = () => {
  if (!archivo.value) {
    importErrors.value = 'Selecciona un archivo antes de importar.';
    return;
  }

  importing.value = true;
  importErrors.value = null;

  const formData = new FormData();
  formData.append('archivo', archivo.value);
  formData.append('tipo', tipoImportacion.value);

  router.post(route('importar.libro', { periodoId: page.props.periodoId }), formData, {
    preserveScroll: true,
    preserveState: false,
    onSuccess: () => {
      showImportModal.value = false;
      archivo.value = null;
      if (fileInput.value) fileInput.value.value = '';
      // Recargar la lista (ya que se crearon nuevos asientos)
      router.reload({ only: ['asientos'] });
    },
    onError: (err) => {
      importErrors.value = err.error || 'Ocurrió un error al procesar el archivo.';
    },
    onFinish: () => {
      importing.value = false;
    }
  });
};

// Variables para drag & drop
const dragActive = ref(false);

// Métodos para drag & drop
const handleDrop = (e) => {
  dragActive.value = false;
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    archivo.value = files[0];
    importErrors.value = null;
  }
};

</script>
