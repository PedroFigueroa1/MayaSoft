<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
// No importamos componentes pequeños, usaremos HTML básico

const props = defineProps({
  unidades: { type: Array, required: true },
  empresa: { type: Object, required: true },
})

const showModal = ref(false)
const editando = ref(null) // null = crear, objeto = editar

const form = useForm({
  codigo: '',
  nombre: '',
  descripcion: '',
  activo: true,
})

function abrirCrear() {
  editando.value = null
  form.reset()
  form.clearErrors()
  showModal.value = true
}

function abrirEditar(unidad) {
  editando.value = unidad
  form.codigo = unidad.codigo
  form.nombre = unidad.nombre
  form.descripcion = unidad.descripcion
  form.activo = unidad.activo
  form.clearErrors()
  showModal.value = true
}

function guardar() {
  if (editando.value) {
    form.put(route('inventario.unidades-medida.update', editando.value.id), {
      onSuccess: () => (showModal.value = false),
      preserveScroll: true,
    })
  } else {
    form.post(route('inventario.unidades-medida.store'), {
      onSuccess: () => (showModal.value = false),
      preserveScroll: true,
    })
  }
}

function eliminar(unidad) {
  if (!confirm(`¿Eliminar la unidad "${unidad.nombre}"?`)) return
  router.delete(route('inventario.unidades-medida.destroy', unidad.id), {
    preserveScroll: true,
  })
}
</script>

<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          Unidades de Medida
          <span class="text-sm font-normal text-gray-500">— {{ empresa.nombre }}</span>
        </h2>
        <!-- Botón Nueva Unidad sin componente -->
        <button
          @click="abrirCrear"
          class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
        >
          + Nueva Unidad
        </button>
      </div>
    </template>

    <div class="py-6 max-w-4xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Código</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Descripción</th>
              <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
              <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="u in unidades" :key="u.id">
              <td class="px-4 py-2 font-mono text-sm">{{ u.codigo }}</td>
              <td class="px-4 py-2">{{ u.nombre }}</td>
              <td class="px-4 py-2 text-sm text-gray-500">{{ u.descripcion }}</td>
              <td class="px-4 py-2">
                <span :class="u.activo ? 'text-green-600' : 'text-gray-400'">
                  {{ u.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-2 text-right space-x-2">
                <!-- Botón Editar -->
                <button
                  @click="abrirEditar(u)"
                  class="inline-flex items-center px-3 py-1 bg-blue-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-600 active:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Editar
                </button>
                <!-- Botón Eliminar -->
                <button
                  @click="eliminar(u)"
                  class="inline-flex items-center px-3 py-1 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 active:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                  Eliminar
                </button>
              </td>
            </tr>
            <tr v-if="unidades.length === 0">
              <td colspan="5" class="px-4 py-6 text-center text-gray-400">
                No hay unidades registradas todavía.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal sin componentes -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 overflow-y-auto"
      aria-labelledby="modal-title"
      role="dialog"
      aria-modal="true"
    >
      <!-- Fondo oscuro -->
      <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div
          class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
          @click="showModal = false"
        ></div>

        <!-- Contenido del modal -->
        <div
          class="inline-block w-full max-w-lg p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg"
        >
          <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4">
            {{ editando ? 'Editar Unidad' : 'Nueva Unidad de Medida' }}
          </h3>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label for="codigo" class="block text-sm font-medium text-gray-700">Código</label>
              <input
                id="codigo"
                v-model="form.codigo"
                type="text"
                maxlength="10"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm uppercase"
              />
              <p v-if="form.errors.codigo" class="mt-1 text-sm text-red-600">{{ form.errors.codigo }}</p>
            </div>
            <div>
              <label for="nombre" class="block text-sm font-medium text-gray-700">Nombre</label>
              <input
                id="nombre"
                v-model="form.nombre"
                type="text"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              />
              <p v-if="form.errors.nombre" class="mt-1 text-sm text-red-600">{{ form.errors.nombre }}</p>
            </div>
          </div>

          <div class="mt-4">
            <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripción</label>
            <input
              id="descripcion"
              v-model="form.descripcion"
              type="text"
              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
          </div>

          <div class="mt-4 flex items-center">
            <input
              id="activo"
              v-model="form.activo"
              type="checkbox"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
            />
            <label for="activo" class="ml-2 block text-sm text-gray-700">Activo</label>
          </div>

          <div class="mt-6 flex justify-end gap-3">
            <!-- Botón Cancelar -->
            <button
              @click="showModal = false"
              class="inline-flex justify-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition ease-in-out duration-150"
            >
              Cancelar
            </button>
            <!-- Botón Guardar/Actualizar -->
            <button
              @click="guardar"
              :disabled="form.processing"
              class="inline-flex justify-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
            >
              {{ editando ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </AppLayoutAdmin>
</template>