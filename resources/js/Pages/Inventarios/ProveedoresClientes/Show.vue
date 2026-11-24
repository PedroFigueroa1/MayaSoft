<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({ item: Object })
</script>

<template>
  <AppLayoutAdmin :title="item.razon_social">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ item.razon_social }}</h2>
        <div class="flex gap-2">
          <Link :href="route('inventario.proveedores-clientes.edit', item.id)" class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm hover:bg-yellow-600">Editar</Link>
          <Link :href="route('inventario.proveedores-clientes.index')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">Volver</Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Información General</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Tipo</dt>
                  <dd class="text-sm text-gray-900">
                    <span v-if="item.es_proveedor && item.es_cliente">Proveedor + Cliente</span>
                    <span v-else-if="item.es_proveedor">Proveedor</span>
                    <span v-else-if="item.es_cliente">Cliente</span>
                    <span v-else>Sin tipo</span>
                  </dd>
                </div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">NIT</dt><dd class="text-sm text-gray-900 font-mono">{{ item.nit || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">CI</dt><dd class="text-sm text-gray-900 font-mono">{{ item.ci || '—' }} {{ item.complemento }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Nombre Comercial</dt><dd class="text-sm text-gray-900">{{ item.nombre_comercial || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Estado</dt>
                  <dd><span v-if="item.activo" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Activo</span>
                  <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactivo</span></dd>
                </div>
              </dl>
            </div>

            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Contacto</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Email</dt><dd class="text-sm text-gray-900">{{ item.email || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Teléfono</dt><dd class="text-sm text-gray-900">{{ item.telefono || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Dirección</dt><dd class="text-sm text-gray-900">{{ item.direccion || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Ciudad / Depto.</dt><dd class="text-sm text-gray-900">{{ item.ciudad }}{{ item.departamento ? ', ' + item.departamento : '' }}</dd></div>
              </dl>
            </div>

            <div class="md:col-span-2">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Condiciones Comerciales y Contables</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-xs text-gray-500 uppercase">Días de Crédito</div>
                  <div class="text-2xl font-bold text-gray-900">{{ item.dias_credito }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-xs text-gray-500 uppercase">Límite de Crédito</div>
                  <div class="text-2xl font-bold text-gray-900">Bs. {{ Number(item.limite_credito).toFixed(2) }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-xs text-gray-500 uppercase">Cuenta por Cobrar</div>
                  <div class="text-sm font-medium text-gray-900">{{ item.cuenta_por_cobrar?.codigo || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ item.cuenta_por_cobrar?.nombre || 'No asignada' }}</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-xs text-gray-500 uppercase">Cuenta por Pagar</div>
                  <div class="text-sm font-medium text-gray-900">{{ item.cuenta_por_pagar?.codigo || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ item.cuenta_por_pagar?.nombre || 'No asignada' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
