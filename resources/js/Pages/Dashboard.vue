<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import useAuth from '@/Composables/useAuth';

const { hasRole } = useAuth()

// Props reales que vienen del controlador
const props = defineProps({
    stats: Object,        // contiene empresas, usuarios, reportes
    usuariosRecientes: Array,
    actividadReciente: Array,
    // 👇 Nuevo prop: tipos de cambio desde el controlador
    tiposCambio: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <AppLayoutAdmin title="Dashboard">
        <!-- Encabezado -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Resumen General</h2>
            <p class="text-gray-600">Bienvenido de nuevo, aquí tienes un resumen global del sistema.</p>
        </div>

        <!-- ================= SECCIÓN INDICADORES ECONÓMICOS ================= -->
        <div v-if="tiposCambio && (tiposCambio.ufv || tiposCambio.dolar_oficial)"
             class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-md font-semibold text-gray-700">
                    <i class="fas fa-chart-line mr-2 text-blue-500"></i> Indicadores Económicos (BCB)
                </h3>
                <span v-if="tiposCambio.fecha_actualizacion" class="text-xs text-gray-400">
                    Actualizado: {{ tiposCambio.fecha_actualizacion }}
                </span>
            </div>

            <!-- Grid principal: UFV y Dólar Oficial + Referencial -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                <!-- UFV - destacado -->
                <div v-if="tiposCambio.ufv"
                     class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-blue-500 flex justify-between items-center">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">UFV (Unidad de Fomento)</p>
                        <p class="text-2xl font-bold text-gray-800">{{ tiposCambio.ufv }} <span class="text-sm font-normal text-gray-500">Bs</span></p>
                    </div>
                    <i class="fas fa-home text-3xl text-blue-300"></i>
                </div>

                <!-- Dólar Oficial -->
                <!--<div v-if="tiposCambio.dolar_oficial"
                     class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-green-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Dólar Oficial (BCB)</p>
                    <div class="flex items-baseline justify-between mt-1">
                        <div>
                            <p class="text-2xl font-bold text-gray-800">{{ tiposCambio.dolar_oficial.venta }} <span class="text-sm font-normal">Bs</span></p>
                            <p class="text-xs text-gray-400">Compra: {{ tiposCambio.dolar_oficial.compra }} Bs</p>
                        </div>
                        <i class="fas fa-dollar-sign text-2xl text-green-400"></i>
                    </div>
                </div>-->

                <!-- Dólar Referencial -->
                <div v-if="tiposCambio.dolar_referencial"
                     class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-yellow-500">
                    <p class="text-xs text-gray-500 uppercase tracking-wide">Dólar Referencial</p>
                    <div class="flex items-baseline justify-between mt-1">
                        <div>
                            <p class="text-2xl font-bold text-gray-800">{{ tiposCambio.dolar_referencial.venta }} <span class="text-sm font-normal">Bs</span></p>
                            <p class="text-xs text-gray-400">Compra: {{ tiposCambio.dolar_referencial.compra }} Bs</p>
                        </div>
                        <i class="fas fa-exchange-alt text-2xl text-yellow-400"></i>
                    </div>
                </div>
            </div>

            <!-- Otras monedas (Euro, Real) más pequeñas -->
            <div v-if="tiposCambio.euro || tiposCambio.real"
                 class="grid grid-cols-2 md:grid-cols-4 gap-3">
                <div v-if="tiposCambio.euro" class="bg-white/70 rounded p-2 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">EUR</span>
                    <span class="text-md font-semibold text-gray-800">{{ tiposCambio.euro }} <span class="text-xs text-gray-400">Bs</span></span>
                    <i class="fab fa-euro-sign text-gray-400"></i>
                </div>
                <div v-if="tiposCambio.real" class="bg-white/70 rounded p-2 flex justify-between items-center">
                    <span class="text-sm font-medium text-gray-600">BRL (Real)</span>
                    <span class="text-md font-semibold text-gray-800">{{ tiposCambio.real }} <span class="text-xs text-gray-400">Bs</span></span>
                    <i class="fas fa-coins text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Estadísticas (solo 3) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Empresas -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                        <i class="fas fa-building text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Empresas</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.empresas.total }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p :class="`text-sm flex items-center ${
                        stats.empresas.tendencia === 'up' ? 'text-green-600' : 'text-red-600'
                    }`">
                        <i :class="`fas fa-arrow-${stats.empresas.tendencia === 'up' ? 'up' : 'down'} mr-1`"></i>
                        {{ Math.abs(stats.empresas.porcentaje) }}% desde el mes pasado
                    </p>
                </div>
            </div>

            <!-- Usuarios -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-green-100 text-green-600">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Usuarios</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.usuarios.total }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p :class="`text-sm flex items-center ${
                        stats.usuarios.tendencia === 'up' ? 'text-green-600' : 'text-red-600'
                    }`">
                        <i :class="`fas fa-arrow-${stats.usuarios.tendencia === 'up' ? 'up' : 'down'} mr-1`"></i>
                        {{ Math.abs(stats.usuarios.porcentaje) }}% desde el mes pasado
                    </p>
                </div>
            </div>

            <!-- Asientos (Reportes) -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-lg bg-amber-100 text-amber-600">
                        <i class="fas fa-file-invoice text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Asientos Contables</p>
                        <p class="text-2xl font-bold text-gray-800">{{ stats.reportes.total }}</p>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <p class="text-sm text-gray-500">
                        Total de asientos registrados en el sistema
                    </p>
                </div>
            </div>
        </div>

        <!-- Actividad Reciente (ahora ocupa todo el ancho) -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-800">Actividad Reciente (últimos asientos)</h3>
                <button class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition">
                    <i class="fas fa-sync-alt mr-2"></i> Actualizar
                </button>
            </div>
            <div class="space-y-4">
                <div v-for="actividad in actividadReciente" :key="actividad.id"
                     class="flex items-start border-b border-gray-200 pb-4 last:border-b-0 last:pb-0">
                    <div class="p-2 bg-blue-100 rounded-full mr-3">
                        <i class="fas fa-file-export text-blue-600"></i>
                    </div>
                    <div>
                        <p class="font-medium text-gray-800">Asiento: {{ actividad.tipo || 'General' }}</p>
                        <p class="text-gray-600 text-sm">{{ actividad.glosa }}</p>
                        <p class="text-gray-500 text-xs mt-1">
                            {{ actividad.created_at }} - {{ actividad.empresa }} (por {{ actividad.creado_por }})
                        </p>
                    </div>
                </div>
                <div v-if="actividadReciente.length === 0" class="text-center text-gray-500 py-4">
                    No hay actividad reciente
                </div>
            </div>
        </div>

        <!-- Tabla de Usuarios Recientes (solo visible para superadmin, pero ya lo es) -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h3 class="text-lg font-bold text-gray-800 mb-6">Usuarios Recientes</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usuario</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Empresa</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Registro</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="usuario in usuariosRecientes" :key="usuario.id">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="bg-blue-100 rounded-full h-10 w-10 flex items-center justify-center text-blue-600 font-bold">
                                            {{ usuario.name.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ usuario.name }}</div>
                                        <div class="text-sm text-gray-500">{{ usuario.email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ usuario.empresa_principal }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ usuario.created_at }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="text-blue-600 hover:text-blue-900">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="usuariosRecientes.length === 0">
                            <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                No hay usuarios recientes
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayoutAdmin>
</template>
