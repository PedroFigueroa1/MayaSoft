<template>
    <AppLayoutAdmin>
        <div class="p-4">
            <Head :title="`Libro de Ventas - ${periodo.nombre}`" />

            <!-- Encabezado y botones -->
            <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
                <h1 class="text-xl font-bold">Libro de Ventas - {{ periodo.nombre }}</h1>
                <div class="flex gap-2">
                    <button @click="modalImportar = true" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm shadow">
                        Importar Excel
                    </button>
                    <button @click="contabilizarMasivo" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm shadow">
                        Contabilizar Seleccionados
                    </button>
                    <button @click="eliminarSeleccionados" class="bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded text-sm shadow">
                        Eliminar Seleccionados
                    </button>
                </div>
            </div>

            <!-- Tarjeta de resumen -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Registros:</span> {{ totales.total_registros }}</div>
                <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Total Importe:</span> {{ formatNumber(totales.total_importe) }}</div>
                <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Total IVA Débito:</span> {{ formatNumber(totales.total_iva) }}</div>
                <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Contabilizados:</span> {{ totales.total_contabilizados }}</div>
                <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Pendientes:</span> {{ totales.total_pendientes }}</div>
            </div>

            <!-- Filtros -->
            <div class="bg-gray-100 p-3 rounded mb-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs font-medium mb-1">Estado</label>
                    <select v-model="filters.estado" @change="applyFilters" class="border rounded px-2 py-1 text-sm bg-white">
                        <option value="">Todos</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="contabilizado">Contabilizado</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium mb-1">Fecha Factura</label>
                    <input type="date" v-model="filters.fecha_factura" @change="applyFilters" class="border rounded px-2 py-1 text-sm" />
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium mb-1">Buscar (NIT/Cliente/Factura)</label>
                    <input type="text" v-model="filters.buscar" @input="debouncedApplyFilters" placeholder="Buscar..." class="border rounded px-2 py-1 text-sm w-full" />
                </div>
                <button @click="resetFilters" class="bg-gray-500 text-white px-3 py-1 rounded text-sm">Limpiar</button>
            </div>

            <!-- Tabla compacta (sin columna de IVA DF) -->
            <div class="overflow-x-auto bg-white rounded shadow">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-1 py-1 w-6"><input type="checkbox" v-model="selectAll" /></th>
                            <th class="px-1 py-1 text-left">NIT</th>
                            <th class="px-1 py-1 text-left">Cliente</th>
                            <th class="px-1 py-1 text-left">Nro Factura</th>
                            <th class="px-1 py-1 text-left">Fecha</th>
                            <th class="px-1 py-1 text-right">Importe</th>
                            <th class="px-1 py-1 text-right">Sin IVA</th>
                            <th class="px-1 py-1 text-right">IVA Débito</th>
                            <th class="px-1 py-1">Cuenta Ingreso</th>
                            <th class="px-1 py-1">Cuenta Cobro</th>
                            <th class="px-1 py-1">Acción</th>
                            <th class="px-1 py-1">Inventario</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="venta in ventas.data" :key="venta.id" :class="{'bg-green-50': venta.estado === 'contabilizado'}">
                            <td class="px-1 py-1">
                                <input type="checkbox" v-model="selectedIds" :value="venta.id" :disabled="venta.estado !== 'pendiente'" />
                            </td>
                            <td class="px-1 py-1">{{ venta.nit_cliente }}</td>
                            <td class="px-1 py-1 max-w-[10px] break-all">{{ venta.razon_social_cliente }}</td>
                            <td class="px-1 py-1">{{ venta.nro_factura }}</td>
                            <td class="px-1 py-1">{{ formatDate(venta.fecha_factura) }}</td>
                            <td class="px-1 py-1 text-right">{{ formatNumber(venta.importe_total) }}</td>
                            <td class="px-1 py-1 text-right">{{ formatNumber(venta.importe_total - venta.debito_fiscal) }}</td>
                            <td class="px-1 py-1 text-right font-medium">{{ formatNumber(venta.debito_fiscal) }}</td>
                            <!-- Cuenta Ingreso -->
                            <td class="px-1 py-1">
                                <SearchableSelect
                                    :options="cuentas"
                                    :value="venta.cuenta_ingreso_id"
                                    @update="(val) => updateCuentas(venta, 'cuenta_ingreso_id', val)"
                                    placeholder="Buscar cuenta..."
                                    :disabled="venta.estado !== 'pendiente'"
                                />
                            </td>
                            <!-- Cuenta Cobro (ya no hay IVA DF) -->
                            <td class="px-1 py-1">
                                <SearchableSelect
                                    :options="cuentas"
                                    :value="venta.cuenta_cobro_id"
                                    @update="(val) => updateCuentas(venta, 'cuenta_cobro_id', val)"
                                    placeholder="Buscar cuenta..."
                                    :disabled="venta.estado !== 'pendiente'"
                                />
                            </td>
                            <td class="px-1 py-1 whitespace-nowrap">
                                <button v-if="venta.estado === 'pendiente'" @click="contabilizar(venta.id)" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-0.5 rounded text-xs mr-1">Contab</button>
                                <button v-else-if="venta.estado === 'contabilizado'" @click="anular(venta.id)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-2 py-0.5 rounded text-xs mr-1">Anular</button>
                                <button v-if="venta.estado === 'pendiente'" @click="eliminar(venta.id)" class="bg-red-500 hover:bg-red-600 text-white px-2 py-0.5 rounded text-xs">Elim</button>
                            </td>
                            <td class="px-1 py-1 whitespace-nowrap">
                                <!-- Ya está en inventario (venga de venta manual o de envío desde libro) -->
                                <span
                                    v-if="enInventario(venta)"
                                    class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-semibold"
                                    :title="`Ingresado ${venta.ingresado_inventario_at ?? ''}`"
                                >
                                    ✔ En inventario
                                </span>

                                <!-- Pendiente de inventario: importado del SIAT, aún sin venta vinculada -->
                                <Link
                                    v-else-if="venta.estado === 'pendiente' && !venta.venta_id"
                                    :href="route('libro-ventas.inventario', venta.id)"
                                    class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-0.5 rounded text-[10px]"
                                >
                                    A inventario
                                </Link>

                                <!-- Contabilizado sin inventario (raro, pero cubierto) -->
                                <span v-else class="text-gray-400 text-[10px]">—</span>
                            </td>
                        </tr>
                        <tr v-if="ventas.data.length === 0">
                            <td colspan="11" class="text-center py-4 text-gray-500 text-sm">No hay registros. Importe un archivo Excel del SIN.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <Pagination :links="ventas.links" class="mt-3" />

            <!-- Modal de importación -->
            <div v-if="modalImportar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-5">
                    <h2 class="text-lg font-bold mb-3">Importar Libro de Ventas (SIN)</h2>
                    <p class="text-gray-600 mb-3 text-sm">Seleccione el archivo Excel (pestaña LVentas).</p>

                    <!-- Campo Mes (opcional) -->
                    <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Mes (opcional)</label>
                    <input type="text" v-model="formImportacion.mes" placeholder="Ej: ENERO 2025" class="w-full border rounded px-2 py-1 text-sm" />
                    <p class="text-xs text-gray-500 mt-1">Si se deja vacío, se tomará del archivo o del período.</p>
                    </div>

                    <!-- Campo Descripción (opcional) -->
                    <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Descripción / Nota</label>
                    <textarea v-model="formImportacion.descripcion" rows="2" class="w-full border rounded px-2 py-1 text-sm" placeholder="Opcional: lote de facturas de marzo..."></textarea>
                    </div>

                    <!-- Selección de archivo -->
                    <div class="mb-3">
                    <label class="block text-sm font-medium mb-1">Archivo Excel</label>
                    <input type="file" ref="archivoInput" multiple accept=".xlsx, .xls" class="w-full border p-2 rounded text-sm" />
                    </div>

                    <div class="flex justify-end gap-2">
                    <button @click="modalImportar= false" class="bg-gray-300 px-3 py-1 rounded text-sm">Cancelar</button>
                    <button @click="subirImportacion" class="bg-blue-600 text-white px-3 py-1 rounded text-sm" :disabled="subiendo">
                        {{ subiendo ? 'Subiendo...' : 'Importar' }}
                    </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayoutAdmin>
</template>

<script setup>
import { ref, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import { Head } from '@inertiajs/vue3'
import Pagination from '@/Components/Pagination.vue'
import SearchableSelect from '@/Components/SearchableSelect.vue'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
    periodo: Object,
    ventas: Object,
    cuentas: Array,
    totales: Object,
    filters: Object,
    cuenta_iva_df_default: Object,
})

const selectedIds = ref([])
const modalImportar = ref(false)
const archivoInput = ref(null)
const subiendo = ref(false)

const enInventario = (v) =>
v.estado_inventario === 'ingresado' ||
(!!v.va_a_inventario && !!v.venta_id)

const selectAll = computed({
    get: () => {
        const pendientes = props.ventas.data.filter(v => v.estado === 'pendiente')
        return pendientes.length > 0 && selectedIds.value.length === pendientes.length
    },
    set: (val) => {
        if (val) {
            selectedIds.value = props.ventas.data.filter(v => v.estado === 'pendiente').map(v => v.id)
        } else {
            selectedIds.value = []
        }
    }
})

const formatNumber = (num) => (num ?? 0).toLocaleString('es-BO', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatDate = (dateValue) => {
    if (!dateValue) return ''
    const date = new Date(dateValue)
    if (isNaN(date.getTime())) return dateValue
    return date.toISOString().split('T')[0]
}

// Actualizar cuentas (solo ingreso y cobro, el IVA DF es fijo)
const updateCuentas = (venta, field, newId) => {
    venta[field] = newId
    // Enviamos solo los dos campos que el usuario puede modificar
    router.patch(route('libro-ventas.actualizar-cuentas', { ventaId: venta.id }), {
        cuenta_ingreso_id: venta.cuenta_ingreso_id,
        cuenta_cobro_id: venta.cuenta_cobro_id,
        // Los campos de descuento e IVA descuento se pueden enviar como null o no enviarlos
        cuenta_iva_df_id: props.cuenta_iva_df_default?.id ?? null,
        cuenta_descuento_id: null,
        cuenta_iva_descuento_id: null,
    }, { preserveState: true, preserveScroll: true })
}

const contabilizar = (id) => {
    if (confirm('¿Contabilizar esta venta? Se generará el asiento correspondiente.')) {
        router.post(route('libro-ventas.contabilizar', { ventaId: id }))
    }
}

const contabilizarMasivo = () => {
    if (selectedIds.value.length === 0) {
        alert('Seleccione al menos una venta pendiente.')
        return
    }
    if (confirm(`¿Contabilizar ${selectedIds.value.length} ventas seleccionadas?`)) {
        router.post(route('libro-ventas.contabilizar-masivo', props.periodo.id), { ids: selectedIds.value })
    }
}

const anular = (id) => {
    if (confirm('¿Anular contabilización? Se eliminará el asiento.')) {
        router.delete(route('libro-ventas.anular', { ventaId: id }))
    }
}

const eliminar = (id) => {
    if (confirm('¿Eliminar este registro? (Solo pendientes)')) {
        router.delete(route('libro-ventas.eliminar', { ventaId: id }))
    }
}

const eliminarTodosPendientes = () => {
    if (confirm('¡ATENCIÓN! Eliminará TODOS los registros pendientes de este período.')) {
        router.delete(route('libro-ventas.eliminar-importacion', props.periodo.id))
    }
}

// Nuevo estado reactivo para los datos del formulario de importación
const formImportacion = ref({
  mes: '',
  descripcion: ''
})
const subirImportacion = () => {
    const files = archivoInput.value?.files

    if (!files || files.length === 0) {
        alert('Seleccione uno o más archivos Excel.')
        return
    }

    subiendo.value = true

    const formData = new FormData()

    Array.from(files).forEach(file => {
        formData.append('archivos[]', file)
    })

    router.post(route('libro-ventas.importar', props.periodo.id), formData, {
        forceFormData: true,
        onSuccess: () => {
            modalImportar.value = false
            subiendo.value = false
        },
        onError: () => {
            subiendo.value = false
        }
    })
}

// Filtros
const filters = ref({
    estado: props.filters.estado || '',
    fecha_factura: props.filters.fecha_factura || '',
    buscar: props.filters.buscar || ''
})

let debounceTimer = null
const debouncedApplyFilters = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => applyFilters(), 500)
}
const applyFilters = () => {
    router.get(route('libro-ventas.index', props.periodo.id), filters.value, { preserveState: true, replace: true })
}
const resetFilters = () => {
    filters.value = { estado: '', fecha_factura: '', buscar: '' }
    applyFilters()
}
const eliminarSeleccionados = () => {
    // Filtramos solo IDs de registros pendientes (por seguridad)
    const idsPendientes = selectedIds.value.filter(id => {
        const venta = props.ventas.data.find(v => v.id === id)
        return venta && venta.estado === 'pendiente'
    })

    if (idsPendientes.length === 0) {
        alert('No hay registros pendientes seleccionados.')
        return
    }

    if (confirm(`¿Eliminar los ${idsPendientes.length} registros seleccionados?`)) {
        router.post(route('libro-ventas.eliminar-seleccionados', props.periodo.id), {
            ids: idsPendientes
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                selectedIds.value = [] // Limpiar selección
            }
        })
    }

}

</script>
