<template>
  <AppLayoutAdmin title="Gestión de Usuarios">
    <template #actions>
      <Link href="/admin/users/create" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i>Nuevo Usuario
      </Link>
    </template>

    <div class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-4">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Roles</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="user in users.data" :key="user.id">
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.id }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                <td class="px-4 py-4">
                  <div class="flex flex-wrap gap-1">
                    <span v-if="user.roles && user.roles.length > 0"
                      v-for="role in user.roles"
                      :key="role.id || role.name"
                      class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                      {{ role.name || role }}
                    </span>
                    <span v-else class="text-xs text-gray-500">Sin roles</span>
                  </div>
                </td>
                <td class="px-4 py-4 whitespace-nowrap">
                  <span class="px-2 py-1 text-xs font-semibold rounded-full"
                    :class="user.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'">
                    {{ user.active ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td class="px-4 py-4 whitespace-nowrap text-sm font-medium">
                  <div class="flex space-x-2">
                    <Link :href="`/admin/users/${user.id}`"
                      class="text-indigo-600 hover:text-indigo-900">
                      <i class="fas fa-eye"></i>
                    </Link>
                    <Link :href="`/admin/users/${user.id}/edit`"
                      class="text-yellow-600 hover:text-yellow-900">
                      <i class="fas fa-edit"></i>
                    </Link>
                    <button @click="toggleStatus(user)"
                      v-if="user.id !== $page.props.auth.user.id"
                      class="text-gray-600 hover:text-gray-900">
                      <i :class="user.active ? 'fas fa-toggle-off' : 'fas fa-toggle-on'"></i>
                    </button>
                    <button @click="deleteUser(user)"
                      v-if="user.id !== $page.props.auth.user.id"
                      class="text-red-600 hover:text-red-900">
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <Pagination :links="users.links" class="mt-4" />
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import Pagination from '@/Components/Pagination.vue';
import { useSwal } from '@/Composables/useSwal';
import Swal from 'sweetalert2'; // 👈 SOLO AQUÍ

const { confirmDelete } = useSwal(); // 👈 SIN Swal aquí

const page = usePage();

// ✅ Toast
const Toast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true,
});

const props = defineProps({
  users: Object,
});

const toggleStatus = (user) => {
  if (user.id === page.props.auth.user.id) {
    Toast.fire({
      icon: 'error',
      title: 'No puedes cambiar tu propio estado'
    });
    return;
  }

  router.patch(`/admin/users/${user.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      Toast.fire({
        icon: 'success',
        title: `Usuario ${user.active ? 'desactivado' : 'activado'} correctamente`
      });
    },
    onError: (errors) => {
      Toast.fire({
        icon: 'error',
        title: errors.message || 'Error al cambiar estado'
      });
    },
  });
};

const deleteUser = (user) => {
  confirmDelete(() => {
    router.delete(`/admin/users/${user.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        Toast.fire({
          icon: 'success',
          title: 'Usuario eliminado correctamente'
        });
      },
      onError: () => {
        Toast.fire({
          icon: 'error',
          title: 'Error al eliminar usuario'
        });
      }
    });
  });
};
</script>
