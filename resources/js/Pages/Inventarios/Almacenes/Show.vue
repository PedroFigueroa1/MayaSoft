<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({ item: Object })
</script>

<template>
  <AppLayoutAdmin :title="item.nombre">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ item.nombre }}</h2>
        <div class="flex gap-2">
          <Link :href="route('inventario.almacenes.edit', item.id)" class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm hover:bg-yellow-600">Editar</Link>
          <Link :href="route('inventario.almacenes.index')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">Volver</Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Información del Almacén</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Código</dt><dd class="text-sm text-gray-900 font-mono font-bold">{{ item.codigo }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Nombre</dt><dd class="text-sm text-gray-900">{{ item.nombre }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Dirección</dt><dd class="text-sm text-gray-900">{{ item.direccion || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Responsable</dt><dd class="text-sm text-gray-900">{{ item.responsable || 'Sin asignar' }}</dd></div>
              </dl>
            </div>

            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Configuración</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Tipo</dt>
                  <dd class="text-sm text-gray-900">
                    <span v-if="item.es_punto_venta && item.es_produccion" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Punto de Venta + Producción</span>
                    <span v-else-if="item.es_punto_venta" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Punto de Venta</span>
                    <span v-else-if="item.es_produccion" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">Producción</span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Almacén General</span>
                  </dd>
                </div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Cuenta Contable</dt>
                  <dd class="text-sm text-gray-900">
                    <span v-if="item.cuenta" class="font-mono">{{ item.cuenta.codigo }} - {{ item.cuenta.nombre }}</span>
                    <span v-else class="text-gray-400">Sin cuenta asignada</span>
                  </dd>
                </div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Estado</dt>
                  <dd>
                    <span v-if="item.activo" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span>
                  </dd>
                </div>
              </dl>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
