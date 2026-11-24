<script setup>
import { useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

const props = defineProps({ cuentas: Array })

const form = useForm({
  nit: '',
  ci: '',
  complemento: '',
  razon_social: '',
  nombre_comercial: '',
  es_proveedor: false,
  es_cliente: false,
  email: '',
  telefono: '',
  direccion: '',
  ciudad: '',
  departamento: '',
  cuenta_por_cobrar_id: '',
  cuenta_por_pagar_id: '',
  dias_credito: 0,
  limite_credito: 0,
  activo: true,
})

const submit = () => {
  form.post(route('inventario.proveedores-clientes.store'))
}
</script>

<template>
  <AppLayout title="Nuevo Proveedor / Cliente">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Proveedor / Cliente</h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <form @submit.prevent="submit">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Tipo -->
              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de relación</label>
                <div class="flex gap-6">
                  <label class="inline-flex items-center">
                    <input v-model="form.es_proveedor" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Proveedor</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input v-model="form.es_cliente" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="ml-2 text-sm text-gray-700">Cliente</span>
                  </label>
                </div>
                <div v-if="form.errors.tipo" class="text-sm text-red-600 mt-1">{{ form.errors.tipo }}</div>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">NIT</label>
                <input v-model="form.nit" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <div v-if="form.errors.nit" class="text-sm text-red-600 mt-1">{{ form.errors.nit }}</div>
              </div>

              <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                  <label class="block text-sm font-medium text-gray-700">CI</label>
                  <input v-model="form.ci" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700">Comp.</label>
                  <input v-model="form.complemento" type="text" placeholder="Ej: 1A" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                </div>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Razón Social <span class="text-red-500">*</span></label>
                <input v-model="form.razon_social" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <div v-if="form.errors.razon_social" class="text-sm text-red-600 mt-1">{{ form.errors.razon_social }}</div>
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Nombre Comercial</label>
                <input v-model="form.nombre_comercial" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Teléfono</label>
                <input v-model="form.telefono" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Dirección</label>
                <input v-model="form.direccion" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Ciudad</label>
                <input v-model="form.ciudad" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Departamento</label>
                <select v-model="form.departamento" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  <option value="">Seleccione...</option>
                  <option v-for="dep in ['La Paz','Cochabamba','Santa Cruz','Oruro','Potosí','Tarija','Chuquisaca','Beni','Pando']" :key="dep" :value="dep">{{ dep }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Cuenta por Cobrar</label>
                <select v-model="form.cuenta_por_cobrar_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  <option value="">Seleccione cuenta...</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Cuenta por Pagar</label>
                <select v-model="form.cuenta_por_pagar_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                  <option value="">Seleccione cuenta...</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>

              <div>
                <label class="block text-sm font-medium text-gray-700">Días de Crédito</label>
                <input v-model.number="form.dias_credito" type="number" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Límite de Crédito (Bs.)</label>
                <input v-model.number="form.limite_credito" type="number" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
              </div>

              <div class="md:col-span-2">
                <label class="inline-flex items-center">
                  <input v-model="form.activo" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                  <span class="ml-2 text-sm text-gray-700">Activo</span>
                </label>
              </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
              <Link :href="route('inventario.proveedores-clientes.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancelar</Link>
              <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
