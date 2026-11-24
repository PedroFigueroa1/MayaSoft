<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import CompraForm from './CompraForm.vue'
defineProps(['proveedores', 'almacenes', 'productos', 'cuentas'])
</script>

<template>
  <AppLayoutAdmin title="Editar Compra">
    <template #header><h2 class="font-semibold text-xl text-gray-800">Editar Compra</h2></template>
    <div class="py-12">
      <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6">
          <CompraForm :proveedores="proveedores" :almacenes="almacenes" :productos="productos" :cuentas="cuentas" />
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
