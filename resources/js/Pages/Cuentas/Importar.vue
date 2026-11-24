<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Importar Plan de Cuentas
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200">

            <!-- Mensajes de éxito/error -->
            <!-- Mensajes de éxito/error -->
            <!-- Modificado: Verificar si flash existe -->
            <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
              {{ $page.props.flash.success }}
            </div>

            <!-- Modificado: Verificar si flash existe -->
            <div v-if="$page.props.flash && $page.props.flash.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
              <div v-if="typeof $page.props.flash.error === 'string'">
                {{ $page.props.flash.error }}
              </div>
              <div v-else>
                <p>{{ $page.props.flash.error.message }}</p>
                <pre v-if="$page.props.flash.error.details" class="mt-2 text-xs overflow-auto">
                  {{ $page.props.flash.error.details }}
                </pre>
              </div>
            </div>

            <!-- Formulario de importación -->
            <form @submit.prevent="submitForm" class="space-y-6">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  Archivo Excel
                </label>
                <input
                  type="file"
                  ref="fileInput"
                  @change="handleFileChange"
                  accept=".xlsx,.xls,.csv"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
                <p class="mt-1 text-sm text-gray-500">
                  Formatos aceptados: .xlsx, .xls, .csv
                </p>
                <p v-if="form.errors.archivo" class="mt-1 text-sm text-red-600">
                  {{ form.errors.archivo }}
                </p>
              </div>

              <!-- Vista previa del archivo -->
              <div v-if="fileName" class="p-4 bg-gray-50 rounded">
                <p class="text-sm text-gray-600">
                  Archivo seleccionado: <span class="font-medium">{{ fileName }}</span>
                </p>
              </div>

              <!-- Instrucciones -->
              <div class="border-t border-gray-200 pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-3">Instrucciones</h3>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                  <div class="flex">
                    <div class="ml-3">
                      <p class="text-sm text-yellow-700">
                        El archivo Excel debe tener la siguiente estructura:
                      </p>
                      <ul class="mt-2 text-sm text-yellow-700 list-disc list-inside">
                        <li>6 columnas: Nivel1, Nivel2, Nivel3, Nivel4, Nivel5, Nombre</li>
                        <li>La primera fila debe contener encabezados</li>
                        <li>Ejemplo: 1, 1, 1, 001, 001, "Caja Central MN"</li>
                      </ul>
                      <p class="mt-2 text-sm text-yellow-700">
                        <strong>Nota:</strong> Se recomienda hacer backup de la base de datos antes de importar.
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Botones -->
              <div class="flex justify-end space-x-3">
                <Link
                  :href="route('cuentas.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-100 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:bg-gray-200 active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Cancelar
                </Link>
                <button
                  type="submit"
                  :disabled="form.processing || !form.archivo"
                  class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="form.processing">
                    <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Importando...
                  </span>
                  <span v-else>
                    Importar Plan de Cuentas
                  </span>
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
import { ref, reactive } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const fileInput = ref(null);
const fileName = ref('');

const form = useForm({
  archivo: null,
});

const handleFileChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.archivo = file;
    fileName.value = file.name;
  } else {
    form.archivo = null;
    fileName.value = '';
  }
};

const submitForm = () => {
  if (!form.archivo) {
    alert('Por favor, selecciona un archivo');
    return;
  }

  form.post(route('plan-cuentas.importar'), {
    forceFormData: true,
    onSuccess: () => {
      form.reset();
      fileName.value = '';
      if (fileInput.value) {
        fileInput.value.value = '';
      }
    },
  });
};
</script>
