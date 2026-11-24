<template>
    <AppLayoutAdmin>
        <div class="bg-white shadow-md rounded-lg p-6 mt-6">
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h2 class="text-xl font-bold">
                        {{ cuenta.codigo }} - {{ cuenta.nombre }}
                    </h2>
                    <p class="text-gray-600" v-if="fecha_inicio && fecha_cierre">
                        Del {{ formatDate(fecha_inicio) }} al {{ formatDate(fecha_cierre) }}
                    </p>
                </div>
                <button @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="text-center">
                        <p class="text-gray-600">Saldo Inicial</p>
                        <p class="text-xl font-bold" :class="getSaldoClass(saldo_inicial)">
                            {{ formatCurrency(saldo_inicial) }}
                        </p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600">Movimientos</p>
                        <p class="text-xl font-bold">{{ movimientos ? movimientos.length : 0 }}</p>
                    </div>
                    <div class="text-center">
                        <p class="text-gray-600">Saldo Final</p>
                        <p class="text-xl font-bold" :class="getSaldoClass(saldo_final)">
                            {{ formatCurrency(saldo_final) }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto" v-if="movimientos && movimientos.length > 0">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Glosa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Debe</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Haber</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <!-- Saldo Inicial -->
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" colspan="6">SALDO INICIAL</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="getSaldoClass(saldo_inicial)">
                                {{ formatCurrency(saldo_inicial) }}
                            </td>
                        </tr>

                        <!-- Movimientos -->
                        <tr v-for="(mov, index) in movimientos" :key="index">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(mov.fecha) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ mov.numero_asiento || 'N/A' }}
                                <span v-if="mov.tipo_asiento" class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full ml-2">
                                    {{ mov.tipo_asiento }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ mov.glosa || '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ mov.descripcion || '-' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ mov.debe ? formatCurrency(mov.debe) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ mov.haber ? formatCurrency(mov.haber) : '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" :class="getSaldoClass(mov.saldo_acumulado)">
                                {{ formatCurrency(mov.saldo_acumulado) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-else class="text-center py-8 text-gray-500">
                <p>No hay movimientos en el período seleccionado</p>
            </div>

            <div class="mt-6 flex justify-end" v-if="movimientos && movimientos.length > 0">
                <button
                    @click="exportarPDF"
                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded flex items-center"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Exportar a PDF
                </button>
            </div>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { defineProps } from 'vue';

const props = defineProps({
    cuenta: {
        type: Object,
        default: () => ({})
    },
    saldo_inicial: {
        type: Number,
        default: 0
    },
    saldo_final: {
        type: Number,
        default: 0
    },
    movimientos: {
        type: Array,
        default: () => []
    },
    fecha_inicio: {
        type: String,
        default: ''
    },
    fecha_cierre: {
        type: String,
        default: ''
    }
});

const formatCurrency = (value) => {
    const numValue = Number(value) || 0;
    return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN',
        minimumFractionDigits: 2
    }).format(numValue);
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';

    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-PE', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit'
        });
    } catch (error) {
        return dateString;
    }
};

const getSaldoClass = (saldo) => {
    const numSaldo = Number(saldo) || 0;
    return numSaldo < 0 ? 'text-red-600' : 'text-green-600';
};

const exportarPDF = () => {
    // Implementar lógica de exportación
    alert('Funcionalidad de exportación a PDF');
};
</script>

<style scoped>
tbody tr:nth-child(even) {
    background-color: #f9fafb;
}
</style>
