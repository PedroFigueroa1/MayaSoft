<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import ProductoForm from './ProductoForm.vue'

defineProps({
    categorias: Array,
    unidades: Array,
    cuentas: Array,
})
</script>

<template>
  <AppLayoutAdmin title="Nuevo Producto">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Nuevo Producto</h2>
    </template>

    <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
          <ProductoForm :categorias="categorias" :unidades="unidades" :cuentas="cuentas" />
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
