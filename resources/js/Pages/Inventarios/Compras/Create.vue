<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import CompraForm from './CompraForm.vue'

defineProps([
  'periodo',
  'proveedores',
  'almacenes',
  'productos',
  'cuentas_pago',
  'cuentas_iva_cf',
])
</script>

<template>
  <AppLayoutAdmin title="Nueva Compra">
    <template #header><h2 class="font-semibold text-xl text-gray-800">Nueva Compra</h2></template>
    <div class="py-12">
      <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
          <CompraForm
            :periodo-id="periodo.id"
            :proveedores="proveedores"
            :almacenes="almacenes"
            :productos="productos"
            :cuentas-pago="cuentas_pago"
            :cuentas-iva-cf="cuentas_iva_cf"
          />
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>