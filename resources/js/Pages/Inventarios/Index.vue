<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue';

const props = defineProps({
  periodos:      { type: Array,  default: () => [] },
  periodoActivo: { type: Object, default: null },
});

const page = usePage();
const empresa = computed(() => page.props.empresaActual ?? null);

const periodoId = ref(props.periodoActivo?.id ?? null);

watch(periodoId, (val) => {
  if (!val) return;
  router.post(route('periodo.cambiar'), { periodo_id: val }, {
    preserveScroll: true,
    preserveState: true,
  });
});

// Catálogos
const catalogo = [
  { t: 'Productos',              i: 'fas fa-cube',         h: '/inventario/productos' },
  { t: 'Unidades de medida',     i: 'fas fa-ruler',        h: '/inventario/unidades-medida' },
  { t: 'Categorías',             i: 'fas fa-tags',         h: '/inventario/categorias' },
  { t: 'Almacenes',              i: 'fas fa-warehouse',    h: '/inventario/almacenes' },
  { t: 'Proveedores / Clientes', i: 'fas fa-address-book', h: '/inventario/proveedores-clientes' },
];

// Operación: requieren período activo
const operacion = computed(() => {
  const p = periodoId.value;
  if (!p) return [];
  return [
    { t: 'Compras',        i: 'fas fa-shopping-cart',  h: `/inventario/compras/${p}` },
    { t: 'Ventas',         i: 'fas fa-cash-register',  h: `/inventario/ventas/${p}` },
    { t: 'Kardex',         i: 'fas fa-clipboard-list', h: `/inventario/periodos/${p}/reportes/fisico-valorado` },
    { t: 'Entrada manual', i: 'fas fa-arrow-down',     h: `/inventario/periodos/${p}/movimientos/entrada/crear` },
    { t: 'Salida manual',  i: 'fas fa-arrow-up',       h: `/inventario/periodos/${p}/movimientos/salida/crear` },
  ];
});

// Reportes: requieren período activo
const reportes = computed(() => {
  const p = periodoId.value;
  if (!p) return [];
  return [
    { t: 'Físico valorado',        i: 'fas fa-coins',         h: `/inventario/periodos/${p}/reportes/fisico-valorado` },
    { t: 'Valorado al corte',      i: 'fas fa-calendar-check', h: `/inventario/periodos/${p}/reportes/fisico-valorado?hasta=${hoy}` },
  ];
});
const hoy = new Date().toISOString().slice(0, 10);

// Configuración (siempre visible, sin período)
const configuracion = [
  { t: 'Configuración SIAT', i: 'fas fa-file-invoice', h: '/configuracion/siat' },
];
</script>

<template>
  <AppLayoutAdmin>
    <Head title="Inventario" />

    <template #header>
      <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
          <h2 class="font-semibold text-xl text-gray-800">Inventario</h2>
          <p class="text-sm text-gray-500">{{ empresa?.nombre ?? 'Sin empresa' }}</p>
        </div>

        <div class="flex items-center gap-2">
          <label class="text-sm text-gray-600 whitespace-nowrap">Período:</label>
          <select
            v-model="periodoId"
            class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-400 focus:outline-none"
          >
            <option :value="null">— Ninguno —</option>
            <option v-for="p in periodos" :key="p.id" :value="p.id">
              {{ p.nombre }}
            </option>
          </select>
        </div>
      </div>
    </template>

    <div class="space-y-8">
      <!-- Catálogos -->
      <section>
        <h3 class="font-semibold text-gray-700 mb-3">Catálogos</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <Link
            v-for="c in catalogo"
            :key="c.t"
            :href="c.h"
            class="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex flex-col items-center gap-3"
          >
            <i :class="`${c.i} text-2xl text-blue-500`"></i>
            <span class="text-sm font-medium text-gray-700 text-center">{{ c.t }}</span>
          </Link>
        </div>
      </section>

      <!-- Operación -->
      <section>
        <h3 class="font-semibold text-gray-700 mb-3">Operación</h3>

        <div
          v-if="!periodoId"
          class="text-sm text-amber-800 bg-amber-50 border border-amber-300 p-4 rounded-lg"
        >
          <i class="fas fa-info-circle mr-2"></i>
          Selecciona un período para habilitar compras, ventas y movimientos.
        </div>

        <div v-else class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <Link
            v-for="c in operacion"
            :key="c.t"
            :href="c.h"
            class="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex flex-col items-center gap-3"
          >
            <i :class="`${c.i} text-2xl text-emerald-500`"></i>
            <span class="text-sm font-medium text-gray-700 text-center">{{ c.t }}</span>
          </Link>
        </div>
      </section>

      <!-- Reportes -->
      <section v-if="periodoId">
        <h3 class="font-semibold text-gray-700 mb-3">Reportes</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <Link
            v-for="c in reportes"
            :key="c.t"
            :href="c.h"
            class="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex flex-col items-center gap-3"
          >
            <i :class="`${c.i} text-2xl text-amber-500`"></i>
            <span class="text-sm font-medium text-gray-700 text-center">{{ c.t }}</span>
          </Link>
        </div>
        <p class="text-xs text-gray-400 mt-2">
          El kardex de un producto se abre desde la tabla del Kardex (clic en el producto).
        </p>
      </section>

      <!-- Configuración -->
      <section>
        <h3 class="font-semibold text-gray-700 mb-3">Configuración</h3>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
          <Link
            v-for="c in configuracion"
            :key="c.t"
            :href="c.h"
            class="bg-white p-5 rounded-xl shadow-sm hover:shadow-md hover:-translate-y-0.5 transition flex flex-col items-center gap-3 border border-violet-100"
          >
            <i :class="`${c.i} text-2xl text-violet-500`"></i>
            <span class="text-sm font-medium text-gray-700 text-center">{{ c.t }}</span>
          </Link>
        </div>
      </section>
    </div>
  </AppLayoutAdmin>
</template>
