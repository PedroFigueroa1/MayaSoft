<script setup>
import { ref } from 'vue'
import { useForm, Link } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'

defineProps({ plantilla_url: String })

const form = useForm({ archivo: null })
const fileName = ref('')

const onChange = (e) => {
  form.archivo = e.target.files[0]
  fileName.value = e.target.files[0]?.name ?? ''
}

const submit = () => {
  form.post(route('inventario.productos.import.store'), {
    forceFormData: true,
    onSuccess: () => form.reset(),
  })
}
</script>

<template>
  <AppLayoutAdmin title="Importar Productos">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Importar Productos desde Excel</h2>
    </template>

    <div class="py-12">
      <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
              <div v-if="resultado" class="space-y-4">

    <!-- Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-green-50 border border-green-200 rounded-lg p-4">
        <div class="text-xs text-green-700 uppercase font-semibold">✅ Creados</div>
        <div class="text-2xl font-bold text-green-800">{{ resultado.creados }}</div>
      </div>
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
        <div class="text-xs text-blue-700 uppercase font-semibold">🔄 Actualizados</div>
        <div class="text-2xl font-bold text-blue-800">{{ resultado.actualizados }}</div>
      </div>
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="text-xs text-red-700 uppercase font-semibold">⚠️ Errores</div>
        <div class="text-2xl font-bold text-red-800">{{ resultado.errores }}</div>
      </div>
    </div>

    <!-- Detalle de errores -->
    <div v-if="resultado.detalle?.length" class="bg-red-50 border border-red-200 rounded-lg">
      <div class="px-4 py-3 border-b border-red-200 flex items-center justify-between">
        <h3 class="font-semibold text-red-800">Detalle de errores ({{ resultado.detalle.length }})</h3>
        <button
          @click="$el.querySelector('.detalle-errores').classList.toggle('hidden')"
          class="text-xs text-red-700 hover:text-red-900 underline"
        >
          Mostrar / Ocultar
        </button>
      </div>
      <ul class="detalle-errores max-h-72 overflow-y-auto divide-y divide-red-100">
        <li
          v-for="(err, i) in resultado.detalle"
          :key="i"
          class="px-4 py-2 text-sm text-red-700 font-mono"
        >
          {{ err }}
        </li>
      </ul>
    </div>

    <!-- Éxito total -->
    <div
      v-else-if="resultado.errores === 0"
      class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded"
    >
      🎉 ¡Importación completada sin errores!
    </div>

  </div>

          <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded text-sm space-y-2">
            <p class="font-semibold">📋 Instrucciones:</p>
            <ol class="list-decimal ml-5 space-y-1">
              <li>Descarga la plantilla (ya tiene los encabezados correctos).</li>
              <li>Llena una fila por producto. Solo <b>descripcion</b> y <b>tipo</b> son obligatorios.</li>
              <li>Los <b>tipos válidos</b> son: <code>mercaderia, materia_prima, producto_terminado, servicio, activo_fijo</code>.</li>
              <li>Los <b>códigos de cuenta</b> deben existir en tu catálogo de cuentas (ej: <code>1.1.4.01</code>).</li>
              <li>Los <b>nombres de categoría y unidad</b> deben coincidir con los existentes.</li>
              <li>Si el <code>codigo</code> ya existe, el producto se <b>actualiza</b>; si no existe, se <b>crea</b>.</li>
            </ol>
          </div>

          <a :href="plantilla_url" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700">
            ⬇️ Descargar plantilla Excel
          </a>

          <form @submit.prevent="submit" class="space-y-4 border-t pt-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Archivo Excel (.xlsx, .xls, .csv)</label>
              <input
                type="file"
                accept=".xlsx,.xls,.csv"
                @change="onChange"
                class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-medium hover:file:bg-indigo-100"
              />
              <p v-if="fileName" class="mt-2 text-xs text-gray-600">📄 {{ fileName }}</p>
              <p v-if="form.errors.archivo" class="mt-1 text-xs text-red-600">{{ form.errors.archivo }}</p>
            </div>

            <div class="flex gap-2">
              <button type="submit" :disabled="!form.archivo || form.processing"
                class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50">
                {{ form.processing ? 'Importando...' : 'Importar Productos' }}
              </button>
              <Link :href="route('inventario.productos.index')"
                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                Cancelar
              </Link>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>