<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Empresas</h2>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

          <!-- Encabezado -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
              <h3 class="text-lg font-semibold text-gray-900">¿En qué empresa vas a trabajar?</h3>
              <p class="text-sm text-gray-500 mt-1">
                Haz clic en una empresa para trabajar con ella. Puedes cambiar en cualquier momento.
              </p>
            </div>
            <Link
              v-if="esAdmin"
              href="/empresa/crear"
              class="inline-flex items-center justify-center px-4 py-2 bg-primary-medium text-white text-sm font-semibold rounded-lg shadow-md hover:bg-primary-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-light transition"
            >
              <i class="fas fa-plus mr-2 text-xs"></i> Nueva Empresa
            </Link>
          </div>

          <!-- Tarjeta: empresa seleccionada -->
          <div
            v-if="empresaActiva"
            class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-xl border-2 border-green-400 bg-green-50"
          >
            <div class="flex items-center gap-4 flex-1 min-w-0">
              <img
                v-if="empresaActiva.logo"
                :src="`/storage/${empresaActiva.logo}`"
                :alt="`Logo de ${empresaActiva.nombre}`"
                class="h-14 w-14 shrink-0 rounded-full object-cover border-2 border-green-300 bg-white"
              />
              <div
                v-else
                class="h-14 w-14 shrink-0 rounded-full bg-green-600 text-white flex items-center justify-center font-bold text-lg"
              >
                {{ getInitials(empresaActiva.nombre) }}
              </div>
              <div class="min-w-0">
                <div class="text-xs uppercase tracking-wide text-green-700 font-semibold">
                  <i class="fas fa-check-circle mr-1"></i> Empresa seleccionada
                </div>
                <div class="text-lg font-semibold text-gray-900 truncate">{{ empresaActiva.nombre }}</div>
                <div class="text-sm text-gray-500 truncate">
                  NIT {{ empresaActiva.nit }}
                  <span v-if="empresaActiva.ciudad"> · {{ empresaActiva.ciudad }}</span>
                  <span v-if="empresaActiva.moneda"> · {{ empresaActiva.moneda }}</span>
                </div>
              </div>
            </div>
            <Link
              href="/dashboard"
              class="inline-flex items-center justify-center px-5 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg shadow hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition"
            >
              Continuar <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </Link>
          </div>

          <!-- Tabla -->
          <div v-if="empresas.length > 0" class="overflow-x-auto rounded-lg border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIT</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teléfono</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciudad</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="empresa in empresas"
                  :key="empresa.id"
                  @click="elegir(empresa)"
                  class="cursor-pointer transition border-l-4"
                  :class="[
                    esActual(empresa)
                      ? 'bg-green-50 border-l-green-600'
                      : 'border-l-transparent hover:bg-gray-50 hover:border-l-blue-300',
                    cargando !== null && cargando !== empresa.id ? 'opacity-60 pointer-events-none' : '',
                  ]"
                >
                  <!-- Logo -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="h-10 w-10">
                      <img
                        v-if="empresa.logo"
                        :src="`/storage/${empresa.logo}`"
                        :alt="`Logo de ${empresa.nombre}`"
                        class="h-10 w-10 rounded-full object-cover border"
                        :class="esActual(empresa) ? 'border-green-400' : 'border-gray-200'"
                      />
                      <div
                        v-else
                        class="h-10 w-10 rounded-full flex items-center justify-center border text-xs font-bold"
                        :class="esActual(empresa)
                          ? 'bg-green-100 border-green-300 text-green-700'
                          : 'bg-blue-100 border-blue-200 text-blue-700'"
                      >
                        {{ getInitials(empresa.nombre) }}
                      </div>
                    </div>
                  </td>

                  <!-- Nombre + estado -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                      {{ empresa.nombre }}
                      <span v-if="cargando === empresa.id" class="text-xs text-gray-400">Entrando...</span>
                      <span
                        v-else-if="esActual(empresa)"
                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-green-700 bg-green-100 px-2 py-0.5 rounded-full"
                      >
                        <i class="fas fa-check-circle"></i> Seleccionada
                      </span>
                    </div>
                    <div class="text-sm text-gray-500">{{ empresa.actividad_economica }}</div>
                  </td>

                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ empresa.nit }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ empresa.telefono }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    {{ empresa.ciudad }}, {{ empresa.departamento }}
                  </td>

                  <!-- Acciones: @click.stop evita activar la empresa -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" @click.stop>
                    <Link
                      :href="`/empresa/${empresa.id}`"
                      class="text-indigo-600 hover:text-indigo-900 mr-3"
                    >
                      Ver
                    </Link>
                    <template v-if="esAdmin">
                      <Link
                        :href="`/empresa/${empresa.id}/editar`"
                        class="text-indigo-600 hover:text-indigo-900 mr-3"
                      >
                        Editar
                      </Link>
                      <button
                        type="button"
                        @click.prevent="confirmDelete(empresa)"
                        class="text-red-600 hover:text-red-900"
                      >
                        Eliminar
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Sin empresas -->
          <div v-else class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No hay empresas</h3>
            <p class="mt-1 text-sm text-gray-500">
              {{ esAdmin ? 'Comienza creando tu primera empresa.' : 'No tienes empresas asignadas. Contacta al administrador.' }}
            </p>
          </div>

        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, router } from '@inertiajs/vue3';
import useAuth from '@/Composables/useAuth';

const { hasRole } = useAuth();

const props = defineProps({
  empresas: { type: Array, default: () => [] },
  actual:   { type: Number, default: null },
  can:      Object,
});

const cargando = ref(null);

const esAdmin = computed(() => hasRole('SUPERADMIN') || hasRole('ADMIN'));
const esActual = (empresa) => empresa.id === props.actual;
const empresaActiva = computed(() => props.empresas.find((e) => e.id === props.actual) ?? null);

// Elegir empresa = clic en la fila
const elegir = (empresa) => {
  if (cargando.value !== null) return;
  cargando.value = empresa.id;
  router.post(route('empresas.cambiar'), { empresa_id: empresa.id }, {
    onFinish: () => { cargando.value = null; },
  });
};

const getInitials = (nombre) => {
  if (!nombre) return 'EM';
  return nombre
    .trim()
    .split(/\s+/)
    .map((word) => word.charAt(0).toUpperCase())
    .join('')
    .substring(0, 2);
};

const confirmDelete = (empresa) => {
  if (confirm(`¿Eliminar «${empresa.nombre}»? Esta acción no se puede deshacer.`)) {
    router.delete(`/empresa/${empresa.id}`);
  }
};
</script>
