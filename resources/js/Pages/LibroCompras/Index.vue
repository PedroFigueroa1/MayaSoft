<template>
    <AppLayoutAdmin>
        <div class="p-4">
            <Head :title="`Libro de Compras - ${periodo.nombre}`" />

            <!-- Encabezado y botones -->
            <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
            <h1 class="text-xl font-bold">Libro de Compras - {{ periodo.nombre }}</h1>
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
            <div class="bg-white p-2 rounded shadow text-center text-sm"><span class="font-bold">Total IVA CF:</span> {{ formatNumber(totales.total_iva) }}</div>
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
                <label class="block text-xs font-medium mb-1">Mes</label>
                <input type="text" v-model="filters.mes" @input="debouncedApplyFilters" placeholder="Ej: ENERO" class="border rounded px-2 py-1 text-sm w-28" />
            </div>
            <div class="flex-1">
                <label class="block text-xs font-medium mb-1">Buscar (NIT/Razón/Factura)</label>
                <input type="text" v-model="filters.buscar" @input="debouncedApplyFilters" placeholder="Buscar..." class="border rounded px-2 py-1 text-sm w-full" />
            </div>
            <button @click="resetFilters" class="bg-gray-500 text-white px-3 py-1 rounded text-sm">Limpiar</button>
            </div>

            <!-- Tabla compacta -->
            <div class="overflow-x-auto bg-white rounded shadow">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-1 py-1 w-6"><input type="checkbox" v-model="selectAll" /></th>
                    <th class="px-1 py-1 text-left">NIT</th>
                    <th class="px-1 py-1 text-left">Razón Social</th>
                    <th class="px-1 py-1 text-left">Nro Factura</th>
                    <th class="px-1 py-1 text-left">Fecha</th>
                    <th class="px-1 py-1 text-right">Importe</th>
                    <th class="px-1 py-1 text-right">Sin IVA</th>
                    <th class="px-1 py-1 text-right">Crédito Fiscal</th>
                    <th class="px-1 py-1">Cuenta Gasto</th>
                    <th class="px-1 py-1">Cuenta Pago</th>
                    <th class="px-1 py-1">Acción</th>
                    <th class="px-1 py-1">Inventario</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                <tr v-for="compra in compras.data" :key="compra.id" :class="{'bg-green-50': compra.estado === 'contabilizado'}">
                    <td class="px-1 py-1">
                    <input type="checkbox" v-model="selectedIds" :value="compra.id" :disabled="compra.estado !== 'pendiente'" />
                    </td>
                    <td class="px-1 py-1">{{ compra.nit_proveedor }}</td>
                    <td class="px-1 py-1 max-w-[10px] break-all">{{ compra.razon_social }}</td>
                    <td class="px-1 py-1">{{ compra.nro_factura }}</td>
                    <td class="px-1 py-1">{{ formatDate(compra.fecha_factura) }}</td>
                    <td class="px-1 py-1 text-right">
                        {{ formatNumber(Number(compra.importe_total).toFixed(2)) }}
                    </td>
                    <td class="px-1 py-1 text-right">{{ formatNumber(compra.importe_total - compra.credito_fiscal) }}</td>
                    <td class="px-1 py-1 text-right font-medium">{{ formatNumber(compra.credito_fiscal) }}</td>
                    <td class="px-1 py-1">
                    <SearchableSelect
                        :options="cuentas"
                        :value="compra.cuenta_gasto_id"
                        @update="(val) => updateCuentas(compra, 'cuenta_gasto_id', val)"
                        placeholder="Buscar cuenta..."
                        :disabled="compra.estado !== 'pendiente'"
                    />
                    </td>
                    <td class="px-1 py-1">
                        <SearchableSelect
                            :options="cuentas"
                            :value="compra.cuenta_pago_id"
                            @update="(val) => updateCuentas(compra, 'cuenta_pago_id', val)"
                            placeholder="Buscar cuenta..."
                            :disabled="compra.estado !== 'pendiente'"
                        />
                    </td>

                    <td class="px-1 py-1 whitespace-nowrap">
                    <button v-if="compra.estado === 'pendiente'" @click="contabilizar(compra.id)" class="bg-blue-500 hover:bg-blue-600 text-white px-2 py-0.5 rounded text-xs mr-1">Contab</button>
                    <button v-else-if="compra.estado === 'contabilizado'" @click="anular(compra.id)" class="bg-yellow-600 hover:bg-yellow-700 text-white px-2 py-0.5 rounded text-xs mr-1">Anular</button>
                    <button v-if="compra.estado === 'pendiente'" @click="eliminar(compra.id)" class="bg-red-500 hover:bg-red-600 text-white px-2 py-0.5 rounded text-xs">Elim</button>
                    </td>
                    <td class="px-1 py-1 whitespace-nowrap">
                        <!-- Ya está en inventario (venga del Camino A o B) -->
                        <span
                            v-if="enInventario(compra)"
                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 text-[10px] font-semibold"
                            :title="`Ingresado ${compra.ingresado_inventario_at ?? ''}`"
                        >
                            ✔ En inventario
                        </span>

                        <!-- Pendiente de inventario (importado del SIAT, sin compra) -->
                        <Link
                            v-else-if="compra.estado === 'pendiente' && !compra.compra_id"
                            :href="route('libro-compras.inventario', compra.id)"
                            class="bg-purple-500 hover:bg-purple-600 text-white px-2 py-0.5 rounded text-[10px]"
                        >
                            A inventario
                        </Link>

                        <!-- Contabilizado sin inventario (raro, pero cubierto) -->
                        <span v-else class="text-gray-400 text-[10px]">—</span>
                    </td>
                </tr>
                <tr v-if="compras.data.length === 0">
                    <td colspan="12" class="text-center py-4 text-gray-500 text-sm">No hay registros. Importe un archivo Excel del SIN.</td>
                </tr>
                </tbody>
            </table>
            </div>

            <!-- Paginación -->
            <Pagination :links="compras.links" class="mt-3" />

            <!-- Modal de importación (igual) -->
            <!-- Modal de importación extendido -->
            <div v-if="modalImportar" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-5">
                    <h2 class="text-lg font-bold mb-3">Importar Libro de Compras (SIN)</h2>
                    <p class="text-gray-600 mb-3 text-sm">Seleccione el archivo Excel (pestaña LCompras).</p>

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
import SearchableSelect from '@/Components/SearchableSelect.vue' // Componente reutilizable
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const props = defineProps({
  periodo: Object,
  compras: Object,
  cuentas: Array,
  totales: Object,
  filters: Object,
  cuenta_iva_cf_default: Object,
})

const selectedIds = ref([])
const modalImportar = ref(false)
const archivoInput = ref(null)
const subiendo = ref(false)

// Select all logic
const selectAll = computed({
  get: () => {
    const pendientes = props.compras.data.filter(c => c.estado === 'pendiente')
    return pendientes.length > 0 && selectedIds.value.length === pendientes.length
  },
  set: (val) => {
    if (val) {
      selectedIds.value = props.compras.data.filter(c => c.estado === 'pendiente').map(c => c.id)
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

// Actualizar cuentas en una fila (solo gasto y pago)

// Actualizar cuentas en una fila (solo gasto y pago)
const updateCuentas = (compra, field, newId) => {
  compra[field] = newId
  router.patch(
    route('libro-compras.actualizar-cuentas', {
      periodoId: props.periodo.id,   // 👈 faltaba
      compra: compra.id              // 👈 el parámetro se llama "compra", no "compraId"
    }),
    {
      cuenta_gasto_id: compra.cuenta_gasto_id,
      cuenta_pago_id: compra.cuenta_pago_id,
    },
    { preserveState: true, preserveScroll: true }
  )
}


const contabilizar = (id) => {
  if (confirm('¿Contabilizar esta compra? Se generará el asiento correspondiente.')) {
    router.post(route('libro-compras.contabilizar', { compraId: id }))
  }
}

const contabilizarMasivo = () => {
  if (selectedIds.value.length === 0) {
    alert('Seleccione al menos una compra pendiente.')
    return
  }
  if (confirm(`¿Contabilizar ${selectedIds.value.length} compras seleccionadas?`)) {
    router.post(route('libro-compras.contabilizar-masivo', props.periodo.id), { ids: selectedIds.value })
  }
}

const anular = (id) => {
  if (confirm('¿Anular contabilización? Se eliminará el asiento.')) {
    router.post(route('libro-compras.anular', { periodoId: props.periodo.id, compra: id }))
  }
}

const eliminar = (id) => {
  if (confirm('¿Eliminar este registro? (Solo pendientes)')) {
    router.delete(route('libro-compras.eliminar', { periodoId: props.periodo.id, compra: id }))
  }
}

const eliminarTodosPendientes = () => {
  if (confirm('¡ATENCIÓN! Eliminará TODOS los registros pendientes de este período.')) {
    router.delete(route('libro-compras.eliminar-importacion', props.periodo.id))
  }
}

// Nuevo estado reactivo para los datos del formulario de importación
const formImportacion = ref({
  mes: '',
  descripcion: ''
})

// Modificar subirImportacion para enviar los datos adicionales
const subirImportacion = () => {
    if (!archivoInput.value || archivoInput.value.files.length === 0) {
        alert('Seleccione al menos un archivo Excel.')
        return
    }

    subiendo.value = true

    const formData = new FormData()

    // Agregar todos los archivos
    Array.from(archivoInput.value.files).forEach((file) => {
        formData.append('archivos[]', file)
    })

    formData.append('mes_importacion', formImportacion.value.mes)
    formData.append('descripcion', formImportacion.value.descripcion)

    router.post(route('libro-compras.importar', props.periodo.id), formData, {
        onSuccess: () => {
            modalImportar.value = false
            subiendo.value = false

            formImportacion.value = {
                mes: '',
                descripcion: ''
            }

            archivoInput.value.value = ''
        },
        onError: () => {
            subiendo.value = false
        }
    })
}

// Filtros
const filters = ref({
  estado: props.filters.estado || '',
  mes: props.filters.mes || '',
  buscar: props.filters.buscar || ''
})

let debounceTimer = null
const debouncedApplyFilters = () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => applyFilters(), 500)
}
const applyFilters = () => {
  router.get(route('libro-compras.index', props.periodo.id), filters.value, { preserveState: true, replace: true })
}
const resetFilters = () => {
  filters.value = { estado: '', mes: '', buscar: '' }
  applyFilters()
}

const eliminarSeleccionados = () => {
    // Filtramos solo IDs de registros pendientes (por seguridad)
    const idsPendientes = selectedIds.value.filter(id => {
        const compra = props.compras.data.find(c => c.id === id)
        return compra && compra.estado === 'pendiente'
    })

    if (idsPendientes.length === 0) {
        alert('No hay registros pendientes seleccionados.')
        return
    }

    if (confirm(`¿Eliminar los ${idsPendientes.length} registros seleccionados?`)) {
        router.post(route('libro-compras.eliminar-seleccionados', props.periodo.id), {
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
// ¿Ya está en inventario? (cubre Camino A y B)
const enInventario = (c) =>
  c.estado_inventario === 'ingresado' ||
  (!!c.va_a_inventario && !!c.compra_id)
</script>
