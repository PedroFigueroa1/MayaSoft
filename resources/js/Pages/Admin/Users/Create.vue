<template>
  <AppLayoutAdmin title="Crear Usuario">
    <div class="max-w-3xl mx-auto">

      <div class="bg-white shadow rounded-xl p-6">
        <h2 class="text-lg font-semibold mb-6">Nuevo Usuario</h2>

        <form @submit.prevent="submit" class="space-y-5">

          <!-- Nombre -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Nombre</label>
            <input v-model="form.name" type="text"
              class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-blue-200 focus:border-blue-400" />
          </div>

          <!-- Email -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Email</label>
            <input v-model="form.email" type="email"
              class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-blue-200" />
          </div>

          <!-- Password -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Password</label>
            <input v-model="form.password" type="password"
              class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-blue-200" />
          </div>

          <!-- Confirm -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Confirmar Password</label>
            <input v-model="form.password_confirmation" type="password"
              class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-blue-200" />
          </div>

          <!-- Estado -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Estado</label>
            <select v-model="form.active"
              class="mt-1 w-full rounded-lg border-gray-300 focus:ring focus:ring-blue-200">
              <option :value="1">Activo</option>
              <option :value="0">Inactivo</option>
            </select>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4">
            <Link href="/admin/users"
              class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">
              Cancelar
            </Link>

            <button type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
              Guardar
            </button>
          </div>

        </form>
      </div>

    </div>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { useForm, Link } from '@inertiajs/vue3';
import Swal from 'sweetalert2';

const form = useForm({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  active: 1,
});

const submit = () => {
  form.post('/admin/users', {
    onSuccess: () => {
      Swal.fire({
        icon: 'success',
        title: 'Usuario creado',
        timer: 2000,
        showConfirmButton: false
      });
    }
  });
};
</script>
