<script setup>
import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({ items: Object, filters: Object })
const flash = usePage().props.flash

const filter = useForm({
  buscar: props.filters?.buscar || '',
  punto_venta: props.filters?.punto_venta || false,
  produccion: props.filters?.produccion || false,
  activo: props.filters?.activo ?? '',
})

const aplicar = () => {
  filter.get(route('inventario.almacenes.index'), { preserveState: true, preserveScroll: true })
}

const limpiar = () => {
  filter.buscar = ''
  filter.punto_venta = false
  filter.produccion = false
  filter.activo = ''
  filter.get(route('inventario.almacenes.index'), { preserveState: true })
}
const eliminarAlmacen = (id) => {
    if (!confirm('¿Eliminar este almacén?')) {
        return
    }

    router.delete(route('inventario.almacenes.destroy', id), {
        preserveScroll: true,
    })
}
</script>

<template>
  <AppLayoutAdmin title="Almacenes">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Almacenes / Ubicaciones</h2>
        <Link :href="route('inventario.almacenes.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">
          + Nuevo Almacén
        </Link>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div v-if="flash?.success" class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ flash.success }}</div>
        <div v-if="flash?.error" class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ flash.error }}</div>

        <!-- Filtros -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 p-4">
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-end">
            <div class="md:col-span-2">
              <label class="block text-xs font-medium text-gray-500 uppercase">Buscar</label>
              <input v-model="filter.buscar" @keyup.enter="aplicar" placeholder="Código, nombre, responsable..." class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase">Estado</label>
              <select v-model="filter.activo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="">Todos</option>
                <option value="1">Activos</option>
                <option value="0">Inactivos</option>
              </select>
            </div>
            <div class="flex items-center gap-4 pb-2">
              <label class="inline-flex items-center">
                <input v-model="filter.punto_venta" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Punto de Venta</span>
              </label>
              <label class="inline-flex items-center">
                <input v-model="filter.produccion" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ml-2 text-sm text-gray-700">Producción</span>
              </label>
            </div>
            <div class="flex gap-2">
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
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Responsable</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cuenta Contable</th>
                  <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Estado</th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="item in items.data" :key="item.id" class="hover:bg-gray-50">
                  <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-900 font-bold">{{ item.codigo }}</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="text-sm font-medium text-gray-900">{{ item.nombre }}</div>
                    <div v-if="item.direccion" class="text-xs text-gray-500">{{ item.direccion }}</div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span v-if="item.es_punto_venta && item.es_produccion" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Venta + Prod.</span>
                    <span v-else-if="item.es_punto_venta" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Punto de Venta</span>
                    <span v-else-if="item.es_produccion" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Producción</span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">General</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ item.responsable || '—' }}</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <span v-if="item.cuenta" class="font-mono text-xs">{{ item.cuenta.codigo }}</span>
                    <span v-else class="text-gray-400 text-xs">Sin cuenta</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-center">
                    <span v-if="item.activo" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                    <Link :href="route('inventario.almacenes.show', item.id)" class="text-indigo-600 hover:text-indigo-900">Ver</Link>
                    <Link :href="route('inventario.almacenes.edit', item.id)" class="text-yellow-600 hover:text-yellow-900">Editar</Link>
                    <button
                        type="button"
                        class="text-red-600 hover:text-red-900"
                        @click="eliminarAlmacen(item.id)"
                    >
                        Eliminar
                    </button>
                  </td>
                </tr>
                <tr v-if="!items.data.length">
                  <td colspan="7" class="px-6 py-10 text-center text-gray-500">No se encontraron almacenes.</td>
                </tr>
              </tbody>
            </table>
          </div>
<div class="px-6 py-4 border-t border-gray-200 flex gap-2 justify-end flex-wrap">
  <template v-for="(link, i) in items.links" :key="i">
    <Link
      v-if="link.url"
      :href="link.url"
      v-html="link.label"
      :class="['px-3 py-1 border rounded text-sm',
        link.active ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50']"
    />
    <span
      v-else
      v-html="link.label"
      class="px-3 py-1 border rounded text-sm bg-white text-gray-400 opacity-50 cursor-not-allowed"
    />
  </template>
</div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
