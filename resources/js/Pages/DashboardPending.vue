<template>
    <AppLayoutAdmin>
        <div class="min-h-screen flex flex-col justify-center items-center bg-gray-50 p-6">
            <!-- Icono de advertencia / reloj -->
            <svg
            class="w-20 h-20 text-yellow-500 mb-6 animate-bounce"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4l3 3m6 1a9 9 0 11-18 0 9 9 0 0118 0z"
            />
            </svg>

            <!-- Mensaje principal -->
            <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">
            ¡Cuenta en revisión!
            </h1>
            <p class="text-gray-600 text-center mb-6">
            Tu cuenta está pendiente de aprobación por un administrador.<br />
            Una vez aprobada, podrás acceder a tu panel de control completo.
            </p>

            <!-- Indicador de espera animado -->
            <div class="flex items-center space-x-2">
            <div class="w-4 h-4 bg-blue-500 rounded-full animate-ping"></div>
            <div class="w-4 h-4 bg-blue-500 rounded-full animate-ping delay-200"></div>
            <div class="w-4 h-4 bg-blue-500 rounded-full animate-ping delay-400"></div>
            </div>

            <!-- Mensaje adicional -->
            <p class="text-sm text-gray-400 mt-4">
            Si tienes dudas, contacta con soporte o con tu administrador.
            </p>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

</script>

<style scoped>
/* Animaciones adicionales opcionales */
</style>
