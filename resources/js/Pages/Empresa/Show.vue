<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Detalle de Empresa
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <div class="flex justify-between items-start mb-6">
            <div>
              <h3 class="text-lg font-medium">{{ empresa.nombre }}</h3>
              <p class="text-gray-600">NIT: {{ empresa.nit }}</p>
            </div>
            <Link :href="`/empresa/${empresa.id}/editar`" class="btn-primary">
              Editar
            </Link>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h4 class="font-medium text-gray-900 mb-2">Información Básica</h4>
              <div class="space-y-2">
                <p><span class="font-medium">Dirección:</span> {{ empresa.direccion }}</p>
                <p><span class="font-medium">Teléfono:</span> {{ empresa.telefono }}</p>
                <p><span class="font-medium">Moneda:</span> {{ empresa.moneda }}</p>
              </div>
            </div>

            <div>
              <h4 class="font-medium text-gray-900 mb-2">Ubicación</h4>
              <div class="space-y-2">
                <p><span class="font-medium">Departamento:</span> {{ empresa.departamento }}</p>
                <p><span class="font-medium">Ciudad:</span> {{ empresa.ciudad }}</p>
                <p><span class="font-medium">Actividad Económica:</span> {{ empresa.actividad_economica }}</p>
              </div>
            </div>

            <div v-if="empresa.logo" class="md:col-span-2">
              <h4 class="font-medium text-gray-900 mb-2">Logo</h4>
              <img :src="`/imagenes/logo/${empresa.logo}`" alt="Logo" class="h-40 object-contain">
            </div>
          </div>

          <div class="mt-6">
            <Link href="/empresas" class="btn-secondary">Volver al listado</Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link } from '@inertiajs/vue3';

defineProps({
  empresa: Object,
});
</script>
