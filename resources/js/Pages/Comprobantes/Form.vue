<template>
    <AppLayoutAdmin>
        <form @submit.prevent="submit">
            <!-- Campos del comprobante -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <SelectField v-model="form.periodo_id" :options="periodos" label="Periodo" required />
            <SelectField v-model="form.tipo" :options="tipos" label="Tipo" required />
            <TextField v-model="form.numero" label="Número" required />
            <DateField v-model="form.fecha_emision" label="Fecha Emisión" required />
            <!-- ... otros campos ... -->
            </div>

            <!-- Detalles del comprobante -->
            <div class="mt-6">
            <h3 class="text-lg font-medium">Detalles</h3>
            <div v-for="(detalle, index) in form.detalles" :key="index" class="grid grid-cols-12 gap-4 mt-2">
                <div class="col-span-5">
                <TextField v-model="detalle.producto_servicio" label="Producto/Servicio" required />
                </div>
                <div class="col-span-2">
                <TextField v-model="detalle.cantidad" label="Cantidad" type="number" step="0.01" required />
                </div>
                <div class="col-span-2">
                <SelectField v-model="detalle.unidad_medida" :options="unidades_medida" label="Unidad" required />
                </div>
                <!-- ... otros campos de detalle ... -->
            </div>
            <Button type="button" @click="addDetalle">Agregar Detalle</Button>
            </div>

            <Button type="submit" class="mt-4">Guardar Comprobante</Button>
        </form>
  </AppLayoutAdmin>
</template>

<script setup>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Pagination from '@/Components/Pagination.vue';
import { useForm } from '@inertiajs/vue3';
import TextField from '@/Components/TextField.vue';
import SelectField from '@/Components/SelectField.vue';
import DateField from '@/Components/DateField.vue';
import Button from '@/Components/Button.vue';

const props = defineProps({
  periodos: Array,
  tipos: Object,
  empresas: Array,
  terceros: Array,
  unidades_medida: Object,
});

const form = useForm({
  periodo_id: null,
  tipo: 'FAC',
  numero: '',
  fecha_emision: new Date().toISOString().split('T')[0],
  // ... otros campos ...
  detalles: [{
    producto_servicio: '',
    cantidad: 1,
    unidad_medida: 'UN',
    precio_unitario: 0,
    tasa_iva: 13,
    tasa_it: 3,
    tasa_ice: 0,
    descuento: 0,
    subtotal: 0,
    importe_iva: 0,
    importe_it: 0,
    importe_ice: 0,
    total: 0,
  }]
});

const addDetalle = () => {
  form.detalles.push({
    producto_servicio: '',
    cantidad: 1,
    unidad_medida: 'UN',
    precio_unitario: 0,
    tasa_iva: 13,
    tasa_it: 3,
    tasa_ice: 0,
    descuento: 0,
    subtotal: 0,
    importe_iva: 0,
    importe_it: 0,
    importe_ice: 0,
    total: 0,
  });
};

const submit = () => {
  form.post(route('comprobantes.store'), {
    onSuccess: () => form.reset(),
  });
};
</script>
