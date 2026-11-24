<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    empresa: Object,
    sinEmpresa: Boolean,
    mensaje: String,
    tienePeriodos: Boolean,
    primerPeriodoId: Number,
    totalAsientos: Number,
    porcentajeAsientos: Number,
    tendenciaAsientos: String,
    asientosRecientes: Array,
    // 👇 Nuevo prop: tipos de cambio desde el controlador
    tiposCambio: {
        type: Object,
        default: () => ({})
    }
});
</script>

<template>
    <AppLayoutAdmin :title="`Dashboard - ${empresa?.nombre || 'Mi Empresa'}`">
        <!-- Encabezado -->
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                {{ empresa?.nombre || 'Mi Empresa' }}
            </h2>
            <p class="text-gray-600">
                Resumen y actividad de tu empresa.
                <span v-if="empresa?.ruc" class="ml-2 text-sm">(RUC: {{ empresa.ruc }})</span>
            </p>
        </div>

        <!-- Usuario con rol pero sin empresa -->
        <div v-if="sinEmpresa" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded mb-6">
            <p class="font-bold">Atención</p>
            <p>{{ mensaje }}</p>
        </div>

        <!-- Dashboard normal -->
        <div v-else>
            <!-- ================= SECCIÓN INDICADORES ECONÓMICOS ================= -->
            <div v-if="tiposCambio && (tiposCambio.ufv || tiposCambio.dolar_oficial)"
                 class="bg-gradient-to-r from-gray-50 to-gray-100 rounded-xl shadow-sm border border-gray-200 p-5 mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-md font-semibold text-gray-700">
                        <i class="fas fa-chart-line mr-2 text-blue-500"></i> Indicadores Económicos
                    </h3>
                    <span v-if="tiposCambio.fecha_actualizacion" class="text-xs text-gray-400">
                        Actualizado: {{ tiposCambio.fecha_actualizacion }}
                    </span>
                </div>

                <!-- Grid principal: primero los más importantes (UFV y Dólar Oficial) más grandes -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-5">
                    <!-- Dólar Referencial -->
                    <div v-if="tiposCambio.dolar_referencial"
                        class="bg-white rounded-lg p-4 shadow-sm border-l-4 border-yellow-500">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Dólar Tipo de Cambio</p>
                        <div class="flex items-baseline justify-between mt-1">
                            <div>
                                <p class="text-2xl font-bold text-gray-800"><span class="text-sm font-normal"> {{ tiposCambio.dolar_referencial.compra }} Bs</span></p>
                                <p class="text-xs text-gray-400">{{ tiposCambio.dolar_referencial.venta }}  Bs</p>
                            </div>
                            <i class="fas fa-exchange-alt text-2xl text-yellow-400"></i>
                        </div>
                    </div>
                    </div>
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

                <!-- Otras monedas (Euro, Real) en un grid más pequeño -->
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
                    <!-- Si más adelante agregas otras monedas, aquí puedes añadirlas -->
                </div>
            </div>

            <!-- ================= TARJETAS DE ESTADÍSTICAS (existentes) ================= -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Total Asientos -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-blue-100 text-blue-600">
                            <i class="fas fa-file-invoice text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">Total Asientos Contables</p>
                            <p class="text-2xl font-bold text-gray-800">{{ totalAsientos }}</p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <p :class="`text-sm flex items-center ${
                            tendenciaAsientos === 'up' ? 'text-green-600' : 'text-red-600'
                        }`">
                            <i :class="`fas fa-arrow-${tendenciaAsientos === 'up' ? 'up' : 'down'} mr-1`"></i>
                            {{ porcentajeAsientos }}% desde el mes pasado
                        </p>
                    </div>
                </div>

                <!-- Indicador de períodos -->
                <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                    <div class="flex items-center">
                        <div class="p-3 rounded-lg bg-purple-100 text-purple-600">
                            <i class="fas fa-calendar-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-gray-600 text-sm">Períodos Contables</p>
                            <p class="text-2xl font-bold text-gray-800">
                                {{ tienePeriodos ? 'Activos' : 'Sin períodos' }}
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <Link :href="route('periodos.index', { empresa: empresa.id })"
                              class="text-blue-600 hover:text-blue-800 text-sm">
                            Administrar períodos →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Acciones rápidas (sin cambios) -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Acciones Rápidas</h3>
                <div class="flex flex-wrap gap-3">
                    <Link :href="route('periodos.index', { empresa: empresa.id })"
                          class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-calendar-alt mr-2"></i> Ver Períodos
                    </Link>
                    <Link :href="route('cuentas.index')"
                          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-chart-line mr-2"></i> Plan de Cuentas
                    </Link>
                    <Link v-if="tienePeriodos" :href="route('asientos.create', { periodo: primerPeriodoId })"
                          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-plus-circle mr-2"></i> Nuevo Asiento
                    </Link>
                    <button v-else disabled
                            class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed"
                            title="Debes crear al menos un período contable primero">
                        <i class="fas fa-plus-circle mr-2"></i> Nuevo Asiento
                    </button>
                    <Link v-if="tienePeriodos" :href="route('libro-diario.index', { periodoId: primerPeriodoId })"
                          class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                        <i class="fas fa-book mr-2"></i> Libro Diario
                    </Link>
                    <button v-else disabled
                            class="bg-gray-300 text-gray-500 px-4 py-2 rounded-lg text-sm font-medium cursor-not-allowed"
                            title="Primero crea períodos contables">
                        <i class="fas fa-book mr-2"></i> Libro Diario
                    </button>
                </div>
                <div v-if="!tienePeriodos" class="mt-4 text-sm text-yellow-600 bg-yellow-50 p-3 rounded">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    No hay períodos contables. Ve a "Ver Períodos" y crea uno para poder registrar asientos.
                </div>
            </div>

            <!-- Últimos Asientos (sin cambios) -->
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-800">Últimos Asientos Contables</h3>
                    <Link v-if="tienePeriodos" :href="route('asientos.index', { periodoId: primerPeriodoId })"
                          class="text-blue-600 hover:text-blue-800 text-sm">
                        Ver todos →
                    </Link>
                </div>

                <div v-if="asientosRecientes.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Glosa</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Creado por</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="asiento in asientosRecientes" :key="asiento.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ asiento.fecha }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ asiento.glosa }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span :class="{
                                        'bg-green-100 text-green-800': asiento.tipo === 'ingreso',
                                        'bg-red-100 text-red-800': asiento.tipo === 'egreso',
                                        'bg-gray-100 text-gray-800': !asiento.tipo
                                    }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                        {{ asiento.tipo || 'N/A' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ asiento.creado_por }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <Link :href="route('asientos.show', { asiento: asiento.id })"
                                          class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="text-center text-gray-500 py-8">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>No hay asientos registrados aún.</p>
                    <p v-if="!tienePeriodos" class="text-sm mt-2">Primero debes crear un período contable.</p>
                </div>
            </div>
        </div>
    </AppLayoutAdmin>
</template>
