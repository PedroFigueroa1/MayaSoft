<template>
  <AppLayoutAdmin title="Editar Usuario">
    <div class="max-w-3xl mx-auto">
      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold mb-6">Editar Usuario</h2>

        <form @submit.prevent="submit" class="space-y-5">
          <div>
            <label class="text-sm text-gray-700">Nombre</label>
            <input v-model="form.name" class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-yellow-200" />
            <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
          </div>

          <div>
            <label class="text-sm text-gray-700">Email</label>
            <input v-model="form.email" class="mt-1 w-full rounded-lg border-gray-300" />
            <div v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</div>
          </div>

          <div>
            <label class="text-sm text-gray-700">Estado</label>
            <select v-model="form.active" class="mt-1 w-full rounded-lg border-gray-300">
              <option :value="1">Activo</option>
              <option :value="0">Inactivo</option>
            </select>
          </div>

          <!-- Sección contraseña: SOLO visible para SUPERADMIN -->
          <div v-if="isSuperAdmin" class="border border-blue-200 rounded-lg p-4 bg-blue-50 space-y-4">
            <div class="flex items-center gap-2 text-blue-700">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
              <span class="text-sm font-medium">Sección SUPERADMIN — Cambio de contraseña</span>
            </div>

            <div>
              <label class="text-sm text-blue-800">Nueva contraseña</label>
              <input
                v-model="form.password"
                type="password"
                autocomplete="new-password"
                class="mt-1 w-full rounded-lg border-blue-300 bg-white focus:ring focus:ring-blue-200"
              />
              <p class="text-xs text-blue-600 mt-1">Dejar en blanco para no cambiarla</p>
              <div v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</div>
            </div>

            <div>
              <label class="text-sm text-blue-800">Confirmar nueva contraseña</label>
              <input
                v-model="form.password_confirmation"
                type="password"
                autocomplete="new-password"
                class="mt-1 w-full rounded-lg border-blue-300 bg-white focus:ring focus:ring-blue-200"
              />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <Link href="/admin/users" class="px-4 py-2 bg-gray-200 rounded-lg">Cancelar</Link>
            <button
              type="submit"
              :disabled="form.processing"
              class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 disabled:opacity-60"
            >
              Actualizar
            </button>
          </div>
        </form>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
  user: Object,
});

// Detectar si el usuario autenticado es SUPERADMIN
const page = usePage();
const isSuperAdmin = computed(() =>
  page.props.auth.user.roles?.includes('SUPERADMIN') ?? false
);

const form = useForm({
  name: props.user.name,
  email: props.user.email,
  password: '',
  password_confirmation: '',
  active: props.user.active,
});

const submit = () => {
  form.put(`/admin/users/${props.user.id}`, {
    onSuccess: () => {
      form.reset('password', 'password_confirmation');
      Swal.fire({
        icon: 'success',
        title: 'Actualizado correctamente',
        timer: 2000,
        showConfirmButton: false,
      });
    },
  });
};
</script>
