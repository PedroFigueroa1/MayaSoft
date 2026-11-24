    <template>
    <AppLayoutAdmin>
        <!-- Header compacto -->
        <div class="bg-white border-b border-gray-200 py-3 px-6 mb-2 shadow-sm">
        <div class="max-w-7xl mx-auto">
            <div class="flex justify-between items-center text-sm text-gray-600 mb-1">
            <div v-if="empresa" class="font-medium">
                {{ empresa.nombre }} <span v-if="empresa.ruc">(RUC: {{ empresa.ruc }})</span>
            </div>
            <div class="text-right">
                <span class="font-semibold">Período:</span> {{ periodo.nombre }} ({{ formatFecha(periodo.fecha_inicio) }} al {{ formatFecha(periodo.fecha_cierre) }})
            </div>
            </div>
            <div class="text-center">
            <h1 class="text-2xl font-bold uppercase tracking-wide text-gray-800">Sumas y Saldos</h1>
            <p class="text-xs text-gray-500">
                Estado de cuentas
                <span v-if="tieneFiltro">del {{ mostrarFecha(props.desde) }} al {{ mostrarFecha(props.hasta) }}</span>
                <span v-else>del período contable</span>
            </p>
            </div>
        </div>
        </div>

        <!-- Mensaje flash -->
        <div v-if="flashMessage" class="max-w-7xl mx-auto mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
        {{ flashMessage }}
        </div>

        <!-- Contenido principal -->
        <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <!-- Filtros de fecha + acciones -->
            <div class="mb-4 flex flex-wrap justify-between items-end gap-3">
            <div class="flex items-end space-x-2">
                <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Desde</label>
                <input
                    type="date"
                    v-model="filtroDesde"
                    class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
                </div>
                <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Hasta</label>
                <input
                    type="date"
                    v-model="filtroHasta"
                    class="border border-gray-300 rounded px-2 py-1.5 text-sm focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                />
                </div>
                <button
                @click="aplicarFiltro"
                class="px-3 py-1.5 bg-gray-800 text-white text-sm rounded hover:bg-gray-900"
                >
                Filtrar
                </button>
                <button
                v-if="tieneFiltro"
                @click="limpiarFiltro"
                class="px-3 py-1.5 bg-gray-200 text-gray-700 text-sm rounded hover:bg-gray-300"
                >
                Limpiar
                </button>
            </div>

            <div class="flex items-center space-x-2">
                <span v-if="!balance.balanceado" class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                ⚠️ No balanceado (Dif: {{ formatCurrency(balance.diferencia) }})
                </span>
                <button @click="recalcularSaldos" class="px-3 py-1.5 bg-blue-600 text-white text-sm rounded hover:bg-blue-700 disabled:opacity-50" :disabled="calculando">
                {{ calculando ? 'Calculando...' : 'Recalcular Saldos' }}
                </button>
                <button @click="exportarExcel" class="px-3 py-1.5 bg-green-600 text-white text-sm rounded hover:bg-green-700">Excel</button>
            </div>
            </div>

            <!-- Totales -->
            <div class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded-lg shadow">
                <h3 class="text-xs font-medium text-gray-500">Total Débitos</h3>
                <p class="text-xl font-bold text-red-600">{{ formatCurrency(totales.total_debe) }}</p>
            </div>
            <div class="bg-white p-3 rounded-lg shadow">
                <h3 class="text-xs font-medium text-gray-500">Total Créditos</h3>
                <p class="text-xl font-bold text-green-600">{{ formatCurrency(totales.total_haber) }}</p>
            </div>
            <div class="bg-white p-3 rounded-lg shadow">
                <h3 class="text-xs font-medium text-gray-500">Saldo Deudor</h3>
                <p class="text-xl font-bold text-blue-600">{{ formatCurrency(totales.saldo_final_debe) }}</p>
            </div>
            <div class="bg-white p-3 rounded-lg shadow">
                <h3 class="text-xs font-medium text-gray-500">Saldo Acreedor</h3>
                <p class="text-xl font-bold text-purple-600">{{ formatCurrency(totales.saldo_final_haber) }}</p>
            </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                    <th rowspan="2" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Código</th>
                    <th rowspan="2" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Nombre</th>
                    <th rowspan="2" class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase border-r">Tipo</th>
                    <th colspan="2" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase border-r">Movimientos</th>
                    <th colspan="2" class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Saldo Final</th>
                    </tr>
                    <tr>
                    <th class="px-2 py-1 text-xs font-medium text-gray-500 border-r">Debe</th>
                    <th class="px-2 py-1 text-xs font-medium text-gray-500 border-r">Haber</th>
                    <th class="px-2 py-1 text-xs font-medium text-gray-500 border-r">Debe</th>
                    <th class="px-2 py-1 text-xs font-medium text-gray-500">Haber</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="item in sumasSaldos" :key="item.id">
                    <td class="px-4 py-2 whitespace-nowrap font-mono border-r">{{ item.codigo }}</td>
                    <td class="px-4 py-2 border-r">{{ item.nombre }}</td>
                    <td class="px-4 py-2 whitespace-nowrap border-r">
                        <span class="px-2 py-0.5 text-xs rounded-full" :class="getTipoClass(item.tipo_codigo)">
                        {{ item.tipo }}
                        </span>
                    </td>
                    <td class="px-2 py-2 text-red-600 text-right border-r">{{ formatCurrency(item.total_debe) }}</td>
                    <td class="px-2 py-2 text-green-600 text-right border-r">{{ formatCurrency(item.total_haber) }}</td>
                    <td class="px-2 py-2 font-bold text-right border-r" :class="getSaldoClass(item.saldo_final_debe)">{{ formatCurrency(item.saldo_final_debe) }}</td>
                    <td class="px-2 py-2 font-bold text-right" :class="getSaldoClass(item.saldo_final_haber)">{{ formatCurrency(item.saldo_final_haber) }}</td>
                    </tr>
                    <tr v-if="sumasSaldos.length === 0">
                    <td colspan="7" class="px-4 py-8 text-center text-gray-400">
                        No se encontraron movimientos
                        <span v-if="tieneFiltro">en el rango seleccionado</span>
                        <span v-else>en este período</span>
                    </td>
                    </tr>
                </tbody>
                </table>
            </div>
            </div>

            <div class="mt-3 text-xs text-gray-500">Último cálculo: {{ fecha_calculo }}</div>
        </div>
        </div>
    </AppLayoutAdmin>
    </template>

    <script setup>
    import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
    import { router, usePage } from '@inertiajs/vue3'
    import { ref, onMounted, computed } from 'vue'

    const page = usePage()
    const flashMessage = ref(page.props.flash?.success)
    onMounted(() => {
    if (flashMessage.value) {
        setTimeout(() => {
        flashMessage.value = null
        }, 4000)
    }
    })

    function formatFecha(fecha) {
    if (!fecha) return '-'
    const f = new Date(fecha)
    return f.toLocaleDateString('es-BO')
    }

    function mostrarFecha(fecha) {
    if (!fecha) return '-'
    const [y, m, d] = fecha.split('-')
    return `${d}/${m}/${y}`
    }

    const props = defineProps({
    periodo: Object,
    empresa: Object,
    sumasSaldos: Array,
    totales: Object,
    balance: Object,
    fecha_calculo: String,
    desde: String,
    hasta: String,
    })

    const filtroDesde = ref(props.desde || '')
    const filtroHasta = ref(props.hasta || '')
    const calculando  = ref(false)

    const tieneFiltro = computed(() => !!filtroDesde.value && !!filtroHasta.value)

    const aplicarFiltro = () => {
    if (!filtroDesde.value || !filtroHasta.value) {
        alert('Seleccione ambas fechas')
        return
    }
    if (filtroDesde.value > filtroHasta.value) {
        alert('La fecha "Desde" no puede ser mayor que la fecha "Hasta"')
        return
    }
    router.get(route('sumas-saldos.index', props.periodo.id), {
        desde: filtroDesde.value,
        hasta: filtroHasta.value
    }, { preserveState: true })
    }

    const limpiarFiltro = () => {
    filtroDesde.value = ''
    filtroHasta.value = ''
    router.get(route('sumas-saldos.index', props.periodo.id), {}, { preserveState: true })
    }

    const formatCurrency = (value) => {
    return `Bs. ${new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(value || 0)}`
    }

    const getTipoClass = (tipo) => {
    const classes = {
        'A': 'bg-blue-100 text-blue-800',
        'P': 'bg-yellow-100 text-yellow-800',
        'PN': 'bg-purple-100 text-purple-800',
        'I': 'bg-green-100 text-green-800',
        'G': 'bg-red-100 text-red-800'
    }
    return classes[tipo] || 'bg-gray-100 text-gray-800'
    }

    const getSaldoClass = (saldo) => {
    return saldo > 0 ? 'text-blue-600' : 'text-gray-400'
    }

    const recalcularSaldos = () => {
    if (!confirm('¿Está seguro de recalcular todos los saldos?')) return
    calculando.value = true
    router.post(route('sumas-saldos.recalcular', props.periodo.id), {}, {
        onFinish: () => {
        calculando.value = false
        }
    })
    }

    const exportarExcel = () => {
    const params = new URLSearchParams()
    if (props.desde) params.append('desde', props.desde)
    if (props.hasta) params.append('hasta', props.hasta)

    const url = route('sumas-saldos.excel', props.periodo.id) + (params.toString() ? '?' + params.toString() : '')
    window.open(url, '_blank')
    }
    </script>
