<script setup>
import { ref } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import Modal from '@/Components/Modal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

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
          <span class="text-sm font-normal text-gray-500">— {{ empresa.razon_social }}</span>
        </h2>
        <PrimaryButton @click="abrirCrear">+ Nueva Unidad</PrimaryButton>
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
                <button class="text-indigo-600 hover:underline" @click="abrirEditar(u)">Editar</button>
                <button class="text-red-600 hover:underline" @click="eliminar(u)">Eliminar</button>
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

    <Modal :show="showModal" @close="showModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium mb-4">
          {{ editando ? 'Editar Unidad' : 'Nueva Unidad de Medida' }}
        </h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel for="codigo" value="Código" />
            <TextInput id="codigo" v-model="form.codigo" class="mt-1 block w-full uppercase" maxlength="10" />
            <InputError :message="form.errors.codigo" class="mt-1" />
          </div>
          <div>
            <InputLabel for="nombre" value="Nombre" />
            <TextInput id="nombre" v-model="form.nombre" class="mt-1 block w-full" />
            <InputError :message="form.errors.nombre" class="mt-1" />
          </div>
        </div>

        <div class="mt-4">
          <InputLabel for="descripcion" value="Descripción" />
          <TextInput id="descripcion" v-model="form.descripcion" class="mt-1 block w-full" />
        </div>

        <label class="flex items-center mt-4 gap-2">
          <input type="checkbox" v-model="form.activo" class="rounded" />
          <span class="text-sm text-gray-700">Activo</span>
        </label>

        <div class="mt-6 flex justify-end gap-3">
          <SecondaryButton @click="showModal = false">Cancelar</SecondaryButton>
          <PrimaryButton :disabled="form.processing" @click="guardar">
            {{ editando ? 'Actualizar' : 'Guardar' }}
          </PrimaryButton>
        </div>
      </div>
    </Modal>
  </AppLayoutAdmin>
</template>
