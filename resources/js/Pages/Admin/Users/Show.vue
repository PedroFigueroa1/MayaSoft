<template>
  <AppLayoutAdmin :title="`Usuario: ${user.name}`">
    <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-sm overflow-hidden">

      <!-- Header -->
      <div class="bg-gray-50 px-6 py-4 border-b">
        <h2 class="text-xl font-semibold text-gray-800">Gestión de Usuario</h2>
        <p class="text-sm text-gray-500 mt-1">Detalles, roles, permisos y seguridad</p>
      </div>

      <!-- Flash: contraseña temporal (visible una sola vez) -->
      <div v-if="$page.props.flash?.temp_password"
        class="mx-6 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
        <p class="text-sm font-medium text-green-800 mb-2">
          Contraseña temporal generada (visible una sola vez):
        </p>
        <div class="flex items-center gap-3">
          <code class="text-base font-mono font-bold text-green-700 bg-green-100 px-3 py-1 rounded tracking-widest">
            {{ $page.props.flash.temp_password }}
          </code>
          <button
            @click="copyPassword($page.props.flash.temp_password)"
            class="text-xs px-3 py-1 bg-white border border-green-300 rounded hover:bg-green-50"
          >
            Copiar
          </button>
        </div>
        <p class="text-xs text-green-600 mt-2">
          Comparte esta contraseña de forma segura con el usuario. No se volverá a mostrar.
        </p>
      </div>

      <div class="p-6 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

          <!-- ===== COLUMNA IZQUIERDA ===== -->
          <div class="space-y-6">

            <!-- Info básica -->
            <div class="space-y-4">
              <h3 class="text-lg font-medium text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Información Básica
              </h3>

              <div class="space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Nombre</label>
                  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-800">{{ user.name }}</p>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Email</label>
                  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-800">{{ user.email }}</p>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Teléfono</label>
                  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-800">{{ user.phone ?? 'No registrado' }}</p>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Registro</label>
                  <div class="px-3 py-2 bg-gray-50 rounded-lg border border-gray-200">
                    <p class="text-gray-800">{{ formatDate(user.created_at) }}</p>
                  </div>
                </div>

                <div>
                  <label class="block text-sm font-medium text-gray-600 mb-1">Estado</label>
                  <div class="px-3 py-1.5 inline-flex items-center rounded-full text-sm font-medium"
                    :class="user.active ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'">
                    <span class="w-2 h-2 rounded-full mr-2"
                      :class="user.active ? 'bg-green-500' : 'bg-red-500'"></span>
                    {{ user.active ? 'Activo' : 'Inactivo' }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Botón editar perfil -->
            <div class="pt-2 border-t border-gray-100">
              <Link
                :href="`/admin/users/${user.id}/edit`"
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Editar Perfil
              </Link>
            </div>

            <!-- ===== CONTRASEÑA — solo SUPERADMIN ===== -->
            <div v-if="isSuperAdmin" class="pt-4 border-t border-gray-100">
              <h3 class="text-base font-medium text-blue-800 flex items-center gap-2 mb-2">
                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Contraseña
                <span class="text-xs font-normal text-blue-500">(solo SUPERADMIN)</span>
              </h3>
              <p class="text-xs text-gray-500 mb-3 leading-relaxed">
                Las contraseñas están encriptadas (bcrypt) y no pueden visualizarse.
                Puedes generar una contraseña temporal para este usuario.
              </p>
              <button
                @click="confirmReset"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 text-red-700 border border-red-200 rounded-md text-sm hover:bg-red-100 transition-colors w-full justify-center"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Resetear contraseña
              </button>
            </div>

          </div>

          <!-- ===== COLUMNA DERECHA ===== -->
          <div class="space-y-6">

            <!-- Roles -->
            <div class="space-y-3 bg-indigo-50/30 p-4 rounded-lg border border-indigo-100">
              <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-800 flex items-center">
                  <svg class="w-5 h-5 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                  Gestión de Roles
                  <span class="ml-2 text-xs font-normal text-gray-500">(Prioritario)</span>
                </h3>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Seleccionar Roles para el usuario</label>

                <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-md p-3 bg-white">
                  <div class="space-y-2">
                    <label
                      v-for="role in roles"
                      :key="role.name"
                      class="flex items-center space-x-3 cursor-pointer hover:bg-gray-50 p-1 rounded"
                    >
                      <input
                        type="checkbox"
                        :value="role.name"
                        v-model="form.roles"
                        class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                      >
                      <span class="text-sm text-gray-700">{{ role.name }}</span>
                      <span v-if="role.description" class="text-xs text-gray-400 ml-1">({{ role.description }})</span>
                    </label>
                  </div>
                </div>

                <div class="mt-3">
                  <p class="text-xs font-medium text-gray-500 mb-2">Roles asignados actualmente:</p>
                  <div v-if="form.roles.length > 0" class="flex flex-wrap gap-2">
                    <span
                      v-for="role in form.roles"
                      :key="role"
                      class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800"
                    >
                      {{ role }}
                    </span>
                  </div>
                  <p v-else class="text-sm text-amber-600 bg-amber-50 p-2 rounded-md">
                    No hay roles seleccionados. Asigna al menos un rol.
                  </p>
                </div>

                <button
                  @click="submitRoles"
                  class="mt-4 w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                  :disabled="form.processing"
                >
                  <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ form.processing ? 'Guardando...' : 'Actualizar Roles' }}
                </button>
              </div>
            </div>

            <!-- Permisos Directos -->
            <div class="space-y-3 pt-2">
              <h3 class="text-lg font-medium text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Permisos Directos
              </h3>

              <div>
                <label class="block text-sm font-medium text-gray-600 mb-3">Seleccionar Permisos</label>

                <div v-for="(perms, group) in groupedPermissions" :key="group" class="mb-5">
                  <h4 class="text-sm font-semibold text-gray-700 mb-2 px-1">{{ group }}</h4>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <label v-for="perm in perms" :key="perm.name" class="flex items-start space-x-3">
                      <div class="flex items-center h-5">
                        <input
                          type="checkbox"
                          v-model="form.permissions"
                          :value="perm.name"
                          class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded"
                        >
                      </div>
                      <div class="text-sm">
                        <p class="text-gray-800 font-medium">{{ perm.name }}</p>
                        <p v-if="perm.description" class="text-gray-500 text-xs mt-0.5">{{ perm.description }}</p>
                      </div>
                    </label>
                  </div>
                </div>

                <button
                  @click="submitPermissions"
                  class="mt-4 w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                  :disabled="form.processing"
                >
                  <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white"
                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  {{ form.processing ? 'Guardando...' : 'Actualizar Permisos' }}
                </button>
              </div>
            </div>

          </div>
        </div>

        <!-- Footer -->
        <div class="pt-5 border-t border-gray-200 flex justify-end">
          <Link
            href="/admin/users"
            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
          >
            <svg class="-ml-1 mr-2 h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Volver al listado
          </Link>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, useForm, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  user: Object,
  roles: Array,
  groupedPermissions: Object,
  userRoles: Array,
  userPermissions: Array,
});

const page = usePage();

// Detecta si el usuario autenticado es SUPERADMIN
const isSuperAdmin = computed(() =>
  page.props.auth?.user?.roles?.includes('SUPERADMIN') ?? false
);

const form = useForm({
  roles: props.userRoles,
  permissions: props.userPermissions,
});

// Formatear fecha legible
const formatDate = (dateStr) => {
  if (!dateStr) return 'No disponible';
  return new Intl.DateTimeFormat('es-BO', {
    day: '2-digit', month: 'short', year: 'numeric'
  }).format(new Date(dateStr));
};

// Copiar contraseña temporal al portapapeles
const copyPassword = (pwd) => {
  navigator.clipboard.writeText(pwd).then(() => {
    Swal.fire({
      icon: 'success',
      title: 'Copiada al portapapeles',
      timer: 1500,
      showConfirmButton: false,
    });
  });
};

// Confirmar y ejecutar reset de contraseña
const confirmReset = () => {
  Swal.fire({
    title: '¿Resetear contraseña?',
    html: `Se generará una contraseña temporal para <strong>${props.user.name}</strong>.<br>Esta acción queda registrada en el log del sistema.`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonText: 'Cancelar',
    confirmButtonText: 'Sí, resetear',
  }).then((result) => {
    if (result.isConfirmed) {
      router.post(`/admin/users/${props.user.id}/reset-password`, {}, {
        preserveScroll: true,
      });
    }
  });
};

const submitRoles = () => {
  form.post(`/admin/users/${props.user.id}/assign-roles`, {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({ icon: 'success', title: 'Roles actualizados', timer: 1800, showConfirmButton: false });
    },
  });
};

const submitPermissions = () => {
  form.post(`/admin/users/${props.user.id}/assign-permissions`, {
    preserveScroll: true,
    onSuccess: () => {
      Swal.fire({ icon: 'success', title: 'Permisos actualizados', timer: 1800, showConfirmButton: false });
    },
  });
};
</script>
