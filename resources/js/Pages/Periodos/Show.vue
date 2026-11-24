<template>
    <AppLayoutAdmin>
        <div class="min-h-screen bg-gray-50 py-8 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- Header -->
                <div class="mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">
                        Detalle del Periodo: <span class="text-indigo-600">{{ periodo.nombre }}</span>
                    </h2>
                    <div class="w-20 h-1 bg-indigo-600 rounded-full"></div>
                </div>

                <!-- Main Card -->
                <div class="bg-white shadow-xl rounded-lg overflow-hidden border border-gray-200">
                    <!-- Company Info Section -->
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-600 px-6 py-4">
                        <h3 class="text-xl font-semibold text-white mb-1">{{ periodo.empresa.nombre }}</h3>
                        <p class="text-indigo-100">
                            {{ formatDate(periodo.fecha_inicio) }} al {{ formatDate(periodo.fecha_cierre) }}
                        </p>
                    </div>

                    <!-- Details Sections -->
                    <div class="p-6 space-y-6">
                        <!-- Status Section -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-3">
                                <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Información del Estado</h4>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-600">Estado:</span>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                        :class="{
                                            'bg-green-100 text-green-800': periodo.estado === 'A',
                                            'bg-red-100 text-red-800': periodo.estado === 'C',
                                            'bg-yellow-100 text-yellow-800': periodo.estado === 'I'
                                        }">
                                        {{ estados[periodo.estado] }}
                                    </span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-600">Creado:</span>
                                    <span class="text-sm text-gray-900">{{ formatDateTime(periodo.created_at) }}</span>
                                </div>
                            </div>

                            <!-- Closing Info -->
                            <div class="space-y-3" v-if="periodo.estado === 'C'">
                                <h4 class="text-lg font-semibold text-gray-800 border-b pb-2">Información de Cierre</h4>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-600">Cerrado por:</span>
                                    <span class="text-sm text-gray-900">{{ periodo.cerrado_por?.name || 'N/A' }}</span>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <span class="text-sm font-medium text-gray-600">Fecha cierre:</span>
                                    <span class="text-sm text-gray-900">{{ formatDateTime(periodo.fecha_cierre_real) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Accounting Stats -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Estadísticas Contables</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                                            <svg class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Total asientos</p>
                                            <p class="text-2xl font-bold text-gray-900">{{ periodo.asientos_count || 0 }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                                            <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <p class="text-sm font-medium text-gray-600">Último asiento</p>
                                            <p class="text-lg font-semibold text-gray-900">{{ lastAsientoDate }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mt-8 flex justify-end">
                    <a href="/periodos"
                       class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import { computed } from 'vue';
import { formatDate, formatDateTime } from '@/Utils/format';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const props = defineProps({
  periodo: Object,
  estados: Object,
});

const lastAsientoDate = computed(() => {
  if (!props.periodo.asientos || props.periodo.asientos.length === 0) {
    return 'N/A';
  }
  return formatDate(props.periodo.asientos[0].fecha);
});
</script>
