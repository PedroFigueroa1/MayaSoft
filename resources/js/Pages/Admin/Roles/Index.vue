<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    roles: Array,
    can: Object,
});
</script>

<template>
    <Head title="Roles" />

    <AppLayoutAdmin>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Roles</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <div class="flex justify-between mb-6">
                            <h3 class="text-lg font-medium">Lista de Roles</h3>
                            <Link v-if="can.create" href="/roles/create" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                Crear Nuevo Rol
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Permisos</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="role in roles" :key="role.id">
                                        <td class="px-6 py-4 whitespace-nowrap">{{ role.id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">{{ role.name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span v-for="permission in role.permissions" :key="permission" class="mr-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ permission }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <Link :href="`/roles/${role.id}/edit`" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</Link>
                                            <Link :href="`/roles/${role.id}`" method="delete" as="button" class="text-red-600 hover:text-red-900" onclick="return confirm('¿Estás seguro?')">
                                                Eliminar
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayoutAdmin>
</template>
