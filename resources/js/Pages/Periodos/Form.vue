<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ periodo ? 'Editar Periodo' : 'Crear Periodo' }}
      </h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <InputLabel for="empresa_id" value="Empresa" />
                <select
                  id="empresa_id"
                  v-model="form.empresa_id"
                  class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                  required
                  :disabled="isEditing"
                >
                  <option value="">Seleccione una empresa</option>
                  <option v-for="empresa in empresas" :key="empresa.id" :value="empresa.id">
                    {{ empresa.nombre }}
                  </option>
                </select>
                <InputError class="mt-2" :message="form.errors.empresa_id" />
              </div>

              <div>
                <InputLabel for="nombre" value="Nombre del Periodo" />
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
                <InputLabel for="fecha_inicio" value="Fecha Inicio" />
                <TextInput
                  id="fecha_inicio"
                  v-model="form.fecha_inicio"
                  type="date"
                  class="mt-1 block w-full"
                  required
                />
                <InputError class="mt-2" :message="form.errors.fecha_inicio" />
              </div>

              <div>
                <InputLabel for="fecha_cierre" value="Fecha Cierre" />
                <TextInput
                  id="fecha_cierre"
                  v-model="form.fecha_cierre"
                  type="date"
                  class="mt-1 block w-full"
                  required
                />
                <InputError class="mt-2" :message="form.errors.fecha_cierre" />
              </div>
            </div>

            <div class="flex items-center justify-end mt-6">
              <Link :href="route('periodos.index', form.empresa_id)" class="btn-secondary mr-4">Cancelar</Link>
              <button type="submit" class="btn-primary" :disabled="form.processing">
                {{ periodo ? 'Actualizar' : 'Crear' }}
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

const props = defineProps({
  periodo: Object,
  empresas: Array,
  estados: Object,
  isEditing: Boolean,
});

const form = useForm({
  empresa_id: props.periodo?.empresa_id || '',
  nombre: props.periodo?.nombre || '',
  fecha_inicio: props.periodo?.fecha_inicio?.split('T')[0] || '',
  fecha_cierre: props.periodo?.fecha_cierre?.split('T')[0] || '',
});


const submit = () => {
  if (props.periodo) {
    form.put(route('periodos.update', {
        empresa: form.empresa_id,
        periodo: props.periodo.id
    }));
  } else {
    form.post(route('periodos.store'));
  }
};

</script>
