<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Centros de Costo
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

        <!-- Notificaciones -->
        <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 text-green-700 rounded">
          {{ $page.props.flash.success }}
        </div>
        <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-100 text-red-700 rounded">
          {{ $page.props.flash.error }}
        </div>

        <!-- Leyenda -->
        <div class="flex gap-4 mb-4 text-xs text-gray-500">
          <span class="flex items-center gap-1">
            <span class="px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">📋 Base</span>
            Centro plantilla (solo lectura)
          </span>
          <span class="flex items-center gap-1">
            <span class="px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">👤 Propio</span>
            Tu centro personalizado
          </span>
        </div>

        <!-- Botones -->
        <div class="mb-6 flex justify-end gap-2">
          <!--<Link
            href="#"
            class="bg-gray-400 text-gray-900 px-4 py-2 rounded hover:bg-gray-500 text-sm font-semibold"
          >
            Importar desde Base
          </Link>-->
          <button
            @click="abrirModalCrear"
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm font-semibold"
          >
            + Nuevo Centro
          </button>
        </div>

        <!-- Tabla -->
        <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
          <div class="p-6">
            <table class="min-w-full">
              <thead>
                <tr class="bg-gray-50 border-b">
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Código</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Nombre</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Descripción</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Estado</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Origen</th>
                  <th class="px-4 py-3 text-left text-sm font-medium text-gray-700">Acciones</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="centro in centros.data"
                  :key="centro.id"
                  class="border-b hover:bg-gray-50"
                  :class="centro.es_base && !centro.user_id ? 'bg-gray-50' : 'bg-white'"
                >
                  <td class="px-4 py-3 font-mono text-sm">{{ centro.codigo }}</td>
                  <td class="px-4 py-3" :class="centro.es_base && !centro.user_id ? 'text-gray-500' : 'text-gray-900'">
                    {{ centro.nombre }}
                  </td>
                  <td class="px-4 py-3 text-gray-500 text-sm">
                    {{ centro.descripcion || 'Sin descripción' }}
                  </td>
                  <td class="px-4 py-3">
                    <span
                      :class="centro.activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                      class="px-2 py-1 rounded text-xs"
                    >
                      {{ centro.activo ? 'Activo' : 'Inactivo' }}
                    </span>
                  </td>

                  <!-- Origen -->
                  <td class="px-4 py-3">
                    <span
                      v-if="centro.es_base && !centro.user_id"
                      class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-600"
                    >
                      📋 Base
                    </span>
                    <span
                      v-else
                      class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700"
                    >
                      👤 Propio
                    </span>
                  </td>

                  <!-- Acciones -->
                  <td class="px-4 py-3">
                    <!-- Centro base: solo importar -->
                    <template v-if="centro.es_base && !centro.user_id">
                      <button
                        @click="importarUno(centro)"
                        class="text-green-600 hover:text-green-800 text-sm"
                        title="Copiar a mis centros de costo"
                      >
                        ↓ Usar
                      </button>
                    </template>

                    <!-- Centro propio: editar y eliminar -->
                    <template v-else>
                      <button
                        @click="abrirModalEditar(centro)"
                        class="text-blue-600 hover:text-blue-800 mr-3 text-sm"
                      >
                        Editar
                      </button>
                      <button
                        @click="confirmarEliminar(centro)"
                        class="text-red-600 hover:text-red-800 text-sm"
                      >
                        Eliminar
                      </button>
                    </template>
                  </td>
                </tr>
              </tbody>
            </table>

            <!-- Paginación -->
            <div class="mt-4 flex justify-between items-center">
              <div class="text-sm text-gray-700">
                Mostrando {{ centros.from }} a {{ centros.to }} de {{ centros.total }} registros
              </div>
              <div class="flex space-x-2">
                <Link
                  v-for="link in centros.links"
                  :key="link.label"
                  :href="link.url || '#'"
                  class="px-3 py-1 border rounded text-sm"
                  :class="{
                    'bg-blue-600 text-white': link.active,
                    'text-gray-400 cursor-not-allowed': !link.url,
                    'hover:bg-gray-100': link.url && !link.active
                  }"
                  v-html="link.label"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Crear/Editar -->
    <Modal :show="mostrarModal" @close="cerrarModal">
      <div class="p-6">
        <h2 class="text-lg font-medium mb-4">
          {{ esEdicion ? 'Editar' : 'Nuevo' }} Centro de Costo
        </h2>

        <div v-if="Object.keys($page.props.errors).length > 0" class="mb-4 p-4 bg-red-100 text-red-700 rounded text-sm">
          <ul>
            <li v-for="(error, field) in $page.props.errors" :key="field">{{ error }}</li>
          </ul>
        </div>

        <form @submit.prevent="guardarCentro">
          <div class="space-y-4">

            <div>
              <label class="block text-sm font-medium mb-1">Código *</label>
              <input
                v-model="form.codigo"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2"
                :class="{ 'border-red-500': $page.props.errors.codigo }"
                :readonly="esEdicion"
                required
              >
              <p v-if="$page.props.errors.codigo" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors.codigo }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Nombre *</label>
              <input
                v-model="form.nombre"
                type="text"
                class="w-full border border-gray-300 rounded px-3 py-2"
                :class="{ 'border-red-500': $page.props.errors.nombre }"
                required
              >
              <p v-if="$page.props.errors.nombre" class="text-red-500 text-sm mt-1">
                {{ $page.props.errors.nombre }}
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium mb-1">Descripción</label>
              <textarea
                v-model="form.descripcion"
                rows="3"
                class="w-full border border-gray-300 rounded px-3 py-2"
              ></textarea>
            </div>

            <div class="flex items-center">
              <input v-model="form.activo" type="checkbox" id="activo" class="mr-2">
              <label for="activo" class="text-sm">Activo</label>
            </div>

          </div>

          <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="cerrarModal"
              class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 text-sm">
              Cancelar
            </button>
            <button type="submit"
              class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
              {{ esEdicion ? 'Actualizar' : 'Crear' }}
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Modal Confirmar Eliminar -->
    <Modal :show="mostrarConfirmacion" @close="cerrarConfirmacion">
      <div class="p-6 text-center">
        <h3 class="text-lg font-medium mb-4">¿Eliminar centro de costo?</h3>
        <p class="text-gray-600 mb-6">
          ¿Estás seguro de eliminar
          <span class="font-semibold">{{ centroAEliminar?.codigo }} - {{ centroAEliminar?.nombre }}</span>?
        </p>
        <div class="flex justify-center gap-3">
          <button @click="cerrarConfirmacion"
            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 text-sm">
            Cancelar
          </button>
          <button @click="eliminarCentro"
            class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
            Sí, eliminar
          </button>
        </div>
      </div>
    </Modal>

    <!-- Modal Importar uno -->
    <Modal :show="mostrarImportar" @close="cerrarImportar">
      <div class="p-6 text-center">
        <h3 class="text-lg font-medium mb-4">¿Usar este centro de costo?</h3>
        <p class="text-gray-600 mb-6">
          Se copiará
          <span class="font-semibold">{{ centroAImportar?.codigo }} - {{ centroAImportar?.nombre }}</span>
          a tus centros de costo
        </p>
        <div class="flex justify-center gap-3">
          <button @click="cerrarImportar"
            class="px-4 py-2 border border-gray-300 rounded hover:bg-gray-50 text-sm">
            Cancelar
          </button>
          <button @click="confirmarImportar"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
            Importar
          </button>
        </div>
      </div>
    </Modal>

  </AppLayoutAdmin>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  centros: Object,
})

// ==========================================
// ESTADO
// ==========================================
const mostrarModal        = ref(false)
const mostrarConfirmacion = ref(false)
const mostrarImportar     = ref(false)
const esEdicion           = ref(false)
const centroAEliminar     = ref(null)
const centroAImportar     = ref(null)

const form = reactive({
  id:          null,
  codigo:      '',
  nombre:      '',
  descripcion: '',
  activo:      true,
})

// ==========================================
// CREAR / EDITAR
// ==========================================
const abrirModalCrear = () => {
  esEdicion.value = false
  resetForm()
  mostrarModal.value = true
}

const abrirModalEditar = (centro) => {
  esEdicion.value      = true
  form.id          = centro.id
  form.codigo      = centro.codigo
  form.nombre      = centro.nombre
  form.descripcion = centro.descripcion
  form.activo      = centro.activo
  mostrarModal.value   = true
}

const cerrarModal = () => {
  mostrarModal.value = false
  resetForm()
  router.reload({ only: ['errors'] })
}

const resetForm = () => {
  form.id = null; form.codigo = ''; form.nombre = '';
  form.descripcion = ''; form.activo = true
}

const guardarCentro = () => {
  if (esEdicion.value) {
    router.put(route('centros-costo.update', form.id), form, {
      onSuccess: () => cerrarModal(),
    })
  } else {
    router.post(route('centros-costo.store'), form, {
      onSuccess: () => cerrarModal(),
    })
  }
}

// ==========================================
// ELIMINAR
// ==========================================
const confirmarEliminar = (centro) => {
  centroAEliminar.value     = centro
  mostrarConfirmacion.value = true
}

const cerrarConfirmacion = () => {
  mostrarConfirmacion.value = false
  centroAEliminar.value     = null
}

const eliminarCentro = () => {
  if (!centroAEliminar.value) return
  router.delete(route('centros-costo.destroy', centroAEliminar.value.id), {
    onSuccess: () => cerrarConfirmacion(),
  })
}

// ==========================================
// IMPORTAR DESDE BASE
// ==========================================
const importarUno = (centro) => {
  centroAImportar.value = centro
  mostrarImportar.value = true
}

const cerrarImportar = () => {
  mostrarImportar.value = false
  centroAImportar.value = null
}

const confirmarImportar = () => {
  if (!centroAImportar.value) return
  router.post(route('centros-costo.importar'), {
    centro_ids: [centroAImportar.value.id],
  }, {
    onSuccess: () => cerrarImportar(),
  })
}
</script>
