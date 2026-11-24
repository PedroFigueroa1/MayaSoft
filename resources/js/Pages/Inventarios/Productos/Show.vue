<script setup>
import { Link } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

const props = defineProps({
  producto: Object,
  stockPorAlmacen: Array,
  ultimosMovimientos: Array,
  stockTotal: [Number, String],
})

const tipos = {
  mercaderia: 'Mercadería',
  materia_prima: 'Materia Prima',
  producto_terminado: 'Producto Terminado',
  servicio: 'Servicio',
  activo_fijo: 'Activo Fijo',
}
const metodos = { PROM: 'Promedio Ponderado', PEPS: 'PEPS', ESP: 'Específico' }

const tipoClase = {
  entrada: 'bg-green-100 text-green-800',
  salida: 'bg-red-100 text-red-800',
  traspaso: 'bg-blue-100 text-blue-800',
  ajuste: 'bg-yellow-100 text-yellow-800',
  venta: 'bg-purple-100 text-purple-800',
  compra: 'bg-emerald-100 text-emerald-800',
}
</script>

<template>
  <AppLayoutAdmin :title="producto.descripcion">
    <template #header>
      <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ producto.descripcion }}</h2>
        <div class="flex gap-2">
          <Link :href="route('inventario.productos.kardex', producto.id)" class="px-4 py-2 bg-emerald-600 text-white rounded-md text-sm hover:bg-emerald-700">Kardex</Link>
          <Link :href="route('inventario.productos.edit', producto.id)" class="px-4 py-2 bg-yellow-500 text-white rounded-md text-sm hover:bg-yellow-600">Editar</Link>
          <Link :href="route('inventario.productos.index')" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-sm hover:bg-gray-300">Volver</Link>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Identificación -->
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Identificación</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Código</dt><dd class="text-sm font-mono font-bold text-gray-900">{{ producto.codigo }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Código de Barras</dt><dd class="text-sm text-gray-900">{{ producto.codigo_barra || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Tipo</dt><dd class="text-sm text-gray-900">{{ tipos[producto.tipo] }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Categoría</dt><dd class="text-sm text-gray-900">{{ producto.categoria?.nombre || '—' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Unidad</dt><dd class="text-sm text-gray-900">{{ producto.unidad_medida?.nombre || '—' }}</dd></div>
              </dl>
            </div>

            <!-- Precios -->
            <div>
              <h3 class="text-lg font-medium text-gray-900 mb-4">Precios</h3>
              <dl class="space-y-3">
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Costo</dt><dd class="text-sm font-mono text-gray-900">{{ Number(producto.precio_costo).toFixed(4) }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Venta 1</dt><dd class="text-sm font-mono font-bold text-gray-900">{{ Number(producto.precio_venta).toFixed(4) }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Venta 2</dt><dd class="text-sm font-mono text-gray-900">{{ Number(producto.precio_venta_2).toFixed(4) }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Venta 3</dt><dd class="text-sm font-mono text-gray-900">{{ Number(producto.precio_venta_3).toFixed(4) }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">IVA</dt>
                  <dd class="text-sm text-gray-900">{{ producto.aplica_iva ? producto.porcentaje_iva + '%' : 'No aplica' }}</dd></div>
                <div class="flex justify-between border-b pb-2"><dt class="text-sm font-medium text-gray-500">Costeo</dt><dd class="text-sm text-gray-900">{{ metodos[producto.metodo_costeo] }}</dd></div>
              </dl>
            </div>

            <!-- Stock por almacén -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Stock por Almacén</h3>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-indigo-50 p-4 rounded-lg text-center">
                  <div class="text-3xl font-bold text-indigo-900">{{ Number(stockTotal).toFixed(2) }}</div>
                  <div class="text-xs text-indigo-600 uppercase font-semibold">Stock Total</div>
                </div>
                <div v-for="s in stockPorAlmacen" :key="s.id" class="bg-gray-50 p-4 rounded-lg text-center">
                  <div class="text-2xl font-bold text-gray-900">{{ Number(s.cantidad).toFixed(2) }}</div>
                  <div class="text-xs text-gray-500 uppercase font-semibold">{{ s.almacen?.nombre || 'Sin almacén' }}</div>
                  <div class="text-xs text-gray-400">Costo prom.: {{ Number(s.costo_unitario).toFixed(4) }}</div>
                </div>
                <div v-if="!stockPorAlmacen.length" class="md:col-span-2 bg-gray-50 p-4 rounded-lg text-center text-sm text-gray-500">
                  Aún no hay stock registrado en ningún almacén.
                </div>
              </div>
            </div>

            <!-- SIAT -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-medium text-gray-900 mb-4">SIAT</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 p-4 rounded-lg">
                <div><div class="text-xs text-gray-500 uppercase">Código SIN</div><div class="text-sm font-mono">{{ producto.codigo_sin || '—' }}</div></div>
                <div><div class="text-xs text-gray-500 uppercase">Descripción SIN</div><div class="text-sm">{{ producto.descripcion_sin || '—' }}</div></div>
              </div>
            </div>

            <!-- Cuentas -->
            <div class="md:col-span-2">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Cuentas Contables</h3>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div v-for="(c, key) in { activo: producto.cuenta_activo, costo: producto.cuenta_costo_venta, ingreso: producto.cuenta_ingreso }" :key="key" class="bg-gray-50 p-4 rounded-lg">
                  <div class="text-xs text-gray-500 uppercase">{{ { activo: 'Activo', costo: 'Costo Venta', ingreso: 'Ingreso' }[key] }}</div>
                  <div class="text-sm font-mono font-medium">{{ c?.codigo || '—' }}</div>
                  <div class="text-xs text-gray-500">{{ c?.nombre || 'No asignada' }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Últimos movimientos -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Últimos Movimientos</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tipo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Almacén</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Cantidad</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Concepto</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="m in ultimosMovimientos" :key="m.id">
                    <td class="px-4 py-2 text-sm text-gray-900">{{ m.fecha }}</td>
                    <td class="px-4 py-2 text-sm">
                      <span class="px-2 inline-flex text-xs font-semibold rounded-full" :class="tipoClase[m.tipo] || 'bg-gray-100 text-gray-800'">{{ m.tipo }}</span>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ m.almacen?.nombre }}</td>
                    <td class="px-4 py-2 text-sm text-right font-mono" :class="['entrada','compra','ajuste'].includes(m.tipo) ? 'text-green-600' : 'text-red-600'">
                      {{ ['entrada','compra','ajuste'].includes(m.tipo) ? '+' : '-' }}{{ Number(m.cantidad).toFixed(2) }}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ m.concepto || m.documento_referencia || '—' }}</td>
                  </tr>
                  <tr v-if="!ultimosMovimientos.length">
                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Sin movimientos registrados.</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>
