<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Plan de Cuentas</h2>
    </template>

    <div class="py-6">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

          <div class="flex justify-between items-center mb-6">
            <SearchBar v-model="search" placeholder="Buscar cuentas..." />
            <div class="flex gap-2">
              <Link
                :href="route('import')"
                class="inline-flex items-center px-4 py-2 bg-gray-400 text-gray-900 text-sm font-semibold rounded-lg shadow-md hover:bg-gray-500 transition"
              >
                Importar desde Base
              </Link>
              <Link
                href="/importar/index"
                class="inline-flex items-center px-4 py-2 bg-gray-200 text-gray-800 text-sm font-semibold rounded-lg shadow-md hover:bg-gray-300 transition"
              >
                Importar Excel
              </Link>
              <Link
                :href="route('cuentas.create')"
                class="inline-flex items-center px-4 py-2 bg-primary-medium text-white text-sm font-semibold rounded-lg shadow-md hover:bg-primary-dark transition"
              >
                Nueva Cuenta
              </Link>
            </div>
          </div>

          <!-- Leyenda -->
          <div class="flex gap-4 mb-4 text-xs text-gray-500">
            <span class="flex items-center gap-1">
              <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">📋 Base</span>
              Cuenta plantilla (solo lectura)
            </span>
            <span class="flex items-center gap-1">
              <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">👤 Propia</span>
              Tu cuenta personalizada
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nivel</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Movimientos</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origen</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr
                  v-for="cuenta in cuentas.data"
                  :key="cuenta.id"
                  :class="cuenta.es_base && !cuenta.user_id ? 'bg-gray-50' : 'bg-white'"
                >
                  <!-- Código -->
                  <td class="px-6 py-4 whitespace-nowrap font-mono text-sm">
                    {{ cuenta.codigo }}
                  </td>

                  <!-- Nombre -->
                  <td class="px-6 py-4">
                    <div class="font-medium" :class="cuenta.es_base && !cuenta.user_id ? 'text-gray-500' : 'text-gray-900'">
                      {{ cuenta.nombre }}
                    </div>
                    <div class="text-xs text-gray-400" v-if="cuenta.padre">
                      Padre: {{ cuenta.padre.codigo }} - {{ cuenta.padre.nombre }}
                    </div>
                  </td>

                  <!-- Tipo -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full" :class="tipoColor(cuenta.tipo)">
                      {{ tipos[cuenta.tipo] }}
                    </span>
                  </td>

                  <!-- Nivel -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    {{ cuenta.nivel }}
                  </td>

                  <!-- Movimientos -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span v-if="cuenta.permite_movimiento" class="text-green-600">✓</span>
                    <span v-else class="text-gray-400">✗</span>
                  </td>

                  <!-- Origen: base o propia -->
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span
                      v-if="cuenta.es_base && !cuenta.user_id"
                      class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600"
                    >
                      📋 Base
                    </span>
                    <span
                      v-else
                      class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"
                    >
                      👤 Propia
                    </span>
                  </td>

                  <!-- Acciones -->
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">

                    <!-- Cuenta base: solo puede importar (copiar como propia) -->
                    <template v-if="cuenta.es_base && !cuenta.user_id">
                      <!--<button
                        @click="importarCuenta(cuenta)"
                        class="text-green-600 hover:text-green-800"
                        title="Copiar esta cuenta a tu plan"
                      >
                        ↓ Usar cuenta
                      </button>-->
                    </template>

                    <!-- Cuenta propia: puede editar y eliminar -->
                    <template v-else>
                      <Link
                        :href="route('cuentas.edit', cuenta.id)"
                        class="text-indigo-600 hover:text-indigo-900 mr-3"
                      >
                        Editar
                      </Link>
                      <button
                        @click="confirmDelete(cuenta)"
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

          <Pagination class="mt-6" :links="cuentas.links" />
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import SearchBar from '@/Components/SearchBar.vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
  cuentas: Object,
  filters: Object,
  tipos: Object,
});

const search = ref(props.filters.search);
const { confirm } = useConfirm();

watch(search, (value) => {
  router.get(route('cuentas.index'), { search: value }, {
    preserveState: true,
    replace: true,
  });
});

const tipoColor = (tipo) => {
  const colors = {
    'A':  'bg-blue-100 text-blue-800',
    'P':  'bg-purple-100 text-purple-800',
    'PN': 'bg-green-100 text-green-800',
    'I':  'bg-yellow-100 text-yellow-800',
    'G':  'bg-red-100 text-red-800',
  };
  return colors[tipo] || 'bg-gray-100 text-gray-800';
};

// Importar una sola cuenta base como cuenta propia
const importarCuenta = (cuenta) => {
  confirm(
    '¿Usar esta cuenta?',
    `Se copiará "${cuenta.codigo} - ${cuenta.nombre}" a tu plan de cuentas`,
    {
      confirmText: 'Importar',
      cancelText: 'Cancelar',
      onConfirm: () => {
        router.post(route('cuentas.importar'), {
          cuenta_ids: [cuenta.id],
        });
      },
    }
  );
};

const confirmDelete = (cuenta) => {
  confirm(
    '¿Eliminar cuenta?',
    `Estás por eliminar la cuenta ${cuenta.codigo} - ${cuenta.nombre}`,
    {
      confirmText: 'Eliminar',
      cancelText: 'Cancelar',
      onConfirm: () => {
        router.delete(route('cuentas.destroy', cuenta.id));
      },
    }
  );
};
</script>
