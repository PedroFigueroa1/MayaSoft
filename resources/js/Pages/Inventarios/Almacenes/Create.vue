<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({ cuentas: Array })

const form = useForm({
  codigo: '',
  nombre: '',
  direccion: '',
  responsable: '',
  cuenta_id: '',
  es_punto_venta: false,
  es_produccion: false,
  activo: true,
})

const submit = () => {
  form.post(route('inventario.almacenes.store'))
}
</script>

<template>
  <AppLayoutAdmin title="Nuevo Almacén">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Almacén</h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-gray-700">Código <span class="text-red-500">*</span></label>
                <input v-model="form.codigo" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <div v-if="form.errors.codigo" class="text-sm text-red-600 mt-1">{{ form.errors.codigo }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Nombre <span class="text-red-500">*</span></label>
                <input v-model="form.nombre" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <div v-if="form.errors.nombre" class="text-sm text-red-600 mt-1">{{ form.errors.nombre }}</div>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Responsable</label>
                <input v-model="form.responsable" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Cuenta Contable</label>
                <select v-model="form.cuenta_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  <option value="">Sin cuenta asignada</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Funciones del almacén</label>
                <div class="flex gap-6">
                  <label class="inline-flex items-center">
                    <input v-model="form.es_punto_venta" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Es Punto de Venta (POS)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input v-model="form.es_produccion" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Recibe productos terminados (Producción)</span>
                  </label>
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="inline-flex items-center">
                  <input v-model="form.activo" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                  <span class="ml-2 text-sm text-gray-700">Activo</span>
                </label>
              </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <Link :href="route('inventario.almacenes.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancelar</Link>
              <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
