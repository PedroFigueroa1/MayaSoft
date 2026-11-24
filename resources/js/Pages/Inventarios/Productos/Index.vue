<script setup>
import { ref } from 'vue'
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
  productos: Object,
  categorias: Array,
  filters: Object,
  resumen: Object,
})
const flash = usePage().props.flash
const mostrarErroresImport = ref(false)

const filter = useForm({
  buscar: props.filters?.buscar || '',
  tipo: props.filters?.tipo || '',
  categoria_id: props.filters?.categoria_id || '',
  activo: props.filters?.activo ?? '',
  bajo_minimo: props.filters?.bajo_minimo === 'true',
})

const aplicar = () => filter.get(route('inventario.productos.index'), { preserveState: true, preserveScroll: true })
const limpiar = () => {
  filter.buscar = ''
  filter.tipo = ''
  filter.categoria_id = ''
  filter.activo = ''
  filter.bajo_minimo = false
  filter.get(route('inventario.productos.index'), { preserveState: true })
}

const tipos = {
  mercaderia: 'Mercadería',
  materia_prima: 'Materia Prima',
  producto_terminado: 'Prod. Terminado',
  servicio: 'Servicio',
  activo_fijo: 'Activo Fijo',
}

const tipoClases = {
  mercaderia: 'bg-blue-100 text-blue-800',
  materia_prima: 'bg-orange-100 text-orange-800',
  producto_terminado: 'bg-green-100 text-green-800',
  servicio: 'bg-purple-100 text-purple-800',
  activo_fijo: 'bg-gray-100 text-gray-800',
}

const bajoMinimo = (p) => p.controla_stock && p.stock_minimo > 0 && (p.stock_total ?? 0) < p.stock_minimo
const eliminarProducto = (id) => {
    if (!confirm('¿Eliminar este producto?')) {
        return
    }

    router.delete(route('inventario.productos.destroy', id), {
        preserveScroll: true,
    })
}
</script>

<template>
  <AppLayoutAdmin title="Productos">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Catálogo de Productos</h2>
        <div class="flex gap-2">
            <Link :href="route('inventario.productos.import')" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">
              Importar Productos
            </Link>
          <Link :href="route('inventario.productos.stock-valorizado')" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm font-medium hover:bg-emerald-700">
            Stock Valorizado
          </Link>
          <Link :href="route('inventario.productos.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
            + Nuevo Producto
          </Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ flash.success }}</div>
        <div v-if="flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ flash.error }}</div>
        

        <!-- Tarjetas resumen -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Productos Activos</div>
            <div class="text-2xl font-bold text-gray-900">{{ resumen.total }}</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Bajo Stock Mínimo</div>
            <div class="text-2xl font-bold" :class="resumen.bajo_minimo > 0 ? 'text-red-600' : 'text-gray-900'">{{ resumen.bajo_minimo }}</div>
          </div>
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-4">
            <div class="text-xs text-gray-500 uppercase">Sin Cuentas Contables</div>
            <div class="text-2xl font-bold" :class="resumen.sin_cuentas > 0 ? 'text-yellow-600' : 'text-gray-900'">{{ resumen.sin_cuentas }}</div>
          </div>
        </div>

        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
          <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-gray-500 uppercase">Buscar</label>
              <input v-model="filter.buscar" @keyup.enter="aplicar" placeholder="Código, descripción, barra, SIN..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Tipo</label>
              <select v-model="filter.tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todos</option>
                <option v-for="(label, key) in tipos" :key="key" :value="key">{{ label }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Categoría</label>
              <select v-model="filter.categoria_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todas</option>
                <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Estado</label>
              <select v-model="filter.activo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todos</option>
                <option value="true">Activos</option>
                <option value="false">Inactivos</option>
              </select>
            </div>
            <div class="flex gap-2 items-end">
              <label class="inline-flex items-center pb-2">
                <input v-model="filter.bajo_minimo" type="checkbox" class="rounded border-gray-300 text-red-600 shadow-sm focus:ring-red-500">
                <span class="ml-2 text-sm text-gray-700">Bajo mínimo</span>
              </label>
              <button @click="aplicar" class="px-4 py-2 bg-gray-800 text-white rounded-md text-sm hover:bg-gray-900">Filtrar</button>
              <button @click="limpiar" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">Limpiar</button>
            </div>
          </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Precios</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="p in productos.data" :key="p.id" class="hover:bg-gray-50" :class="bajoMinimo(p) ? 'bg-red-50' : ''">
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-mono font-bold text-gray-900">{{ p.codigo }}</div>
                    <div v-if="p.codigo_barra" class="text-xs text-gray-500">{{ p.codigo_barra }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ p.descripcion }}</div>
                    <div v-if="p.categoria" class="text-xs text-gray-500">{{ p.categoria.nombre }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="tipoClases[p.tipo] || 'bg-gray-100 text-gray-800'">
                      {{ tipos[p.tipo] || p.tipo }}
                    </span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div>Costo: <span class="font-mono">{{ Number(p.precio_costo).toFixed(2) }}</span></div>
                    <div>Venta: <span class="font-mono font-medium text-gray-900">{{ Number(p.precio_venta).toFixed(2) }}</span></div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">
                    <div v-if="p.controla_stock" class="font-mono">
                      <span :class="bajoMinimo(p) ? 'text-red-600 font-bold' : 'text-gray-900'">
                        {{ Number(p.stock_total ?? 0).toFixed(2) }}
                      </span>
                      <span class="text-xs text-gray-400"> / mín. {{ Number(p.stock_minimo).toFixed(2) }}</span>
                    </div>
                    <span v-else class="text-xs text-gray-400">Sin control</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span v-if="p.activo" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                    <Link :href="route('inventario.productos.kardex', p.id)" class="text-emerald-600 hover:text-emerald-900">Kardex</Link>
                    <Link :href="route('inventario.productos.show', p.id)" class="text-indigo-600 hover:text-indigo-900">Ver</Link>
                    <Link :href="route('inventario.productos.edit', p.id)" class="text-yellow-600 hover:text-yellow-900">Editar</Link>
                    <button
                        type="button"
                        class="text-red-600 hover:text-red-900"
                        @click="eliminarProducto(p.id)"
                    >
                        Eliminar
                    </button>
                  </td>
                </tr>
                <tr v-if="!productos.data.length">
                  <td colspan="7" class="px-6 py-10 text-center text-gray-500">No se encontraron productos.</td>
                </tr>
              </tbody>
            </table>
          </div>
<div class="px-6 py-4 border-t border-gray-200 flex gap-2 justify-end">
    <template v-for="link in productos.links" :key="link.label">

        <Link
            v-if="link.url"
            :href="link.url"
            v-html="link.label"
            :class="[
                'px-3 py-1 border rounded text-sm',
                link.active
                    ? 'bg-indigo-600 text-white'
                    : 'bg-white text-gray-700 hover:bg-gray-50'
            ]"
        />

        <span
            v-else
            v-html="link.label"
            class="px-3 py-1 border rounded text-sm bg-gray-100 text-gray-400 cursor-not-allowed"
        />

    </template>
</div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
