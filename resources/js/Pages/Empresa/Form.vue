<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ isEditing ? 'Editar Empresa' : 'Crear Empresa' }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit" enctype="multipart/form-data">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Columna Izquierda -->
              <div class="space-y-6">
                <div>
                  <InputLabel for="nombre" value="Nombre de la Empresa *" />
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
                  <InputLabel for="nit" value="NIT *" />
                  <TextInput
                    id="nit"
                    v-model="form.nit"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.nit" />
                </div>

                <div>
                  <InputLabel for="direccion" value="Dirección *" />
                  <TextInput
                    id="direccion"
                    v-model="form.direccion"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.direccion" />
                </div>

                <div>
                  <InputLabel for="telefono" value="Teléfono *" />
                  <TextInput
                    id="telefono"
                    v-model="form.telefono"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.telefono" />
                </div>
              </div>

              <!-- Columna Derecha -->
              <div class="space-y-6">
                <div>
                  <InputLabel for="moneda" value="Moneda *" />
                  <select
                    id="moneda"
                    v-model="form.moneda"
                    class="mt-1 block w-full border-gray-300 focus:border-blue-800 focus:ring-blue-800 rounded-md shadow-sm"
                    required
                  >
                    <option v-for="moneda in monedas" :key="moneda" :value="moneda">
                      {{ moneda }}
                    </option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.moneda" />
                </div>

                <div>
                  <InputLabel for="departamento" value="Departamento *" />
                  <select
                    id="departamento"
                    v-model="form.departamento"
                    class="mt-1 block w-full border-gray-300 focus:border-blue-800 focus:ring-blue-800 rounded-md shadow-sm"
                    required
                    @change="form.ciudad = ''"
                  >
                    <option value="">Seleccione un departamento</option>
                    <option v-for="depto in departamentos" :key="depto" :value="depto">
                      {{ depto }}
                    </option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.departamento" />
                </div>

                <div>
                  <InputLabel for="ciudad" value="Ciudad *" />
                  <TextInput
                    id="ciudad"
                    v-model="form.ciudad"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.ciudad" />
                </div>

                <div>
                  <InputLabel for="actividad_economica" value="Actividad Económica *" />
                  <TextInput
                    id="actividad_economica"
                    v-model="form.actividad_economica"
                    type="text"
                    class="mt-1 block w-full"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.actividad_economica" />
                </div>
              </div>

              <!-- Campo de logo que ocupa ambas columnas -->
              <div class="md:col-span-2">
                <InputLabel for="logo" value="Logo de la Empresa" />
                <div class="mt-1 flex items-center">
                  <input
                    id="logo"
                    type="file"
                    class="block w-full text-sm text-gray-500
                      file:mr-4 file:py-2 file:px-4
                      file:rounded-md file:border-0
                      file:text-sm file:font-semibold
                      file:bg-blue-800 file:text-white
                      hover:file:bg-blue-700"
                    @change="handleLogoChange"
                    accept="image/*"
                  />
                </div>
                <InputError class="mt-2" :message="form.errors.logo" />
                <div class="mt-4 flex items-center space-x-4">
                  <div v-if="form.logoPreview" class="flex-shrink-0">
                    <img :src="form.logoPreview" class="h-20 w-20 object-cover rounded border border-gray-200" />
                  </div>
                  <div v-else-if="empresa?.logo" class="flex-shrink-0">
                    <img :src="`/storage/${empresa.logo}`" class="h-20 w-20 object-cover rounded border border-gray-200" />
                  </div>
                  <div class="text-sm text-gray-500">
                    <p>Formatos aceptados: JPEG, PNG, GIF</p>
                    <p>Tamaño máximo: 2MB</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end mt-8 space-x-4">
              <Link
                :href="route('empresas.index')"
                class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-800"
              >
                Cancelar
              </Link>
              <button
                type="submit"
                class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-800 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-800"
                :disabled="form.processing"
                :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
              >
                <span v-if="form.processing">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Procesando...
                </span>
                <span v-else>
                  {{ isEditing ? 'Actualizar Empresa' : 'Crear Empresa' }}
                </span>
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
import { ref } from 'vue';

const props = defineProps({
  empresa: Object,
  monedas: Array,
  departamentos: Array,
  isEditing: Boolean,
});

const form = useForm({
  nombre: props.empresa?.nombre || '',
  nit: props.empresa?.nit || '',
  direccion: props.empresa?.direccion || '',
  telefono: props.empresa?.telefono || '',
  moneda: props.empresa?.moneda || 'BOB',
  actividad_economica: props.empresa?.actividad_economica || '',
  ciudad: props.empresa?.ciudad || '',
  departamento: props.empresa?.departamento || '',
  logo: null,
});

const logoPreview = ref(props.empresa?.logo ? `/storage/${props.empresa.logo}` : null);

const handleLogoChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    form.logo = file;
    const reader = new FileReader();
    reader.onload = (e) => {
      logoPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);
  } else {
    form.logo = null;
    logoPreview.value = props.empresa?.logo ? `/storage/${props.empresa.logo}` : null;
  }
};

const submit = () => {
  if (props.isEditing) {
    // Usar transform para asegurar que los datos se envíen correctamente
    form.transform((data) => ({
      ...data,
      _method: 'PUT' // Forzar método PUT para Laravel
    })).post(route('empresas.update', props.empresa.id), {
      preserveScroll: true,
      forceFormData: true,
      onError: (errors) => {
        console.log('Errores:', errors);
      },
      onSuccess: () => {
        console.log('Actualizado exitosamente');
      }
    });
  } else {
    form.post(route('empresas.store'), {
      preserveScroll: true,
      forceFormData: true,
      onError: (errors) => {
        console.log('Errores:', errors);
      },
      onSuccess: () => {
        console.log('Creado exitosamente');
      }
    });
  }
};
</script>
