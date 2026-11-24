<script setup>
import { ref, computed } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import Modal from '@/Components/Modal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
  arbol: { type: Array, required: true },
  categoriasPlanas: { type: Array, required: true },
  empresa: { type: Object, required: true },
})

const showModal = ref(false)
const editando = ref(null)
const expandidas = ref(new Set())

const form = useForm({
  codigo: '',
  nombre: '',
  parent_id: null,
})

// Opciones del select generadas desde el árbol
// Helper para obtener los hijos sea cual sea el nombre de la relación
function getHijos(nodo) {
  return nodo?.hijos || nodo?.children || nodo?.descendientes || []
}

const opcionesPadre = computed(() => {
  const lista = []
  const recorrer = (nodos, nivel = 0) => {
    for (const n of (nodos || [])) {
      if (editando.value && n.id === editando.value.id) continue
      lista.push({
        id: n.id,
        label: '— '.repeat(nivel) + n.nombre + (n.codigo ? ` (${n.codigo})` : ''),
      })
      const hijos = getHijos(n)
      if (hijos.length) recorrer(hijos, nivel + 1)
    }
  }
  recorrer(props.arbol)
  return lista
})

function aplanarArbol(nodos, nivel = 0, padreExpandido = true) {
  if (!nodos || !Array.isArray(nodos)) return []
  let lista = []
  for (const nodo of nodos) {
    if (!nodo) continue
    const expandido = expandidas.value.has(nodo.id)
    const hijos = getHijos(nodo)
    lista.push({ ...nodo, hijos, nivel, visible: padreExpandido })
    if (padreExpandido && hijos.length) {
      lista = lista.concat(aplanarArbol(hijos, nivel + 1, expandido))
    }
  }
  return lista
}



const elementosVisibles = computed(() => {
  return aplanarArbol(props.arbol).filter(item => item && item.visible)
})

function toggle(id) {
  expandidas.value.has(id) ? expandidas.value.delete(id) : expandidas.value.add(id)
}

function abrirCrear(parentId = null) {
  editando.value = null
  form.reset()
  form.parent_id = parentId != null ? Number(parentId) : null
  form.clearErrors()
  showModal.value = true
}

function abrirEditar(cat) {
  editando.value = cat
  form.codigo = cat.codigo
  form.nombre = cat.nombre
  form.parent_id = cat.parent_id != null ? Number(cat.parent_id) : null
  form.clearErrors()
  showModal.value = true
}

function guardar() {
  const opts = { onSuccess: () => (showModal.value = false), preserveScroll: true }
  if (editando.value) {
    form.put(route('inventario.categorias.update', editando.value.id), opts)
  } else {
    form.post(route('inventario.categorias.store'), opts)
  }
}

function eliminar(cat) {
  if (!confirm(`¿Eliminar "${cat.nombre}"? Debe no tener subcategorías ni productos asociados.`)) return
  router.delete(route('inventario.categorias.destroy', cat.id), { preserveScroll: true })
}
</script>

<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-xl font-semibold text-gray-800">
          Categorías de Productos
          <span class="text-sm font-normal text-gray-500">— {{ empresa.nombre }}</span>
        </h2>
        <PrimaryButton @click="abrirCrear(null)">+ Categoría raíz</PrimaryButton>
      </div>
    </template>

    <div class="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
      <div class="bg-white rounded-lg shadow p-4">
        <!-- Lista plana de elementos visibles -->
        <div
          v-for="item in elementosVisibles"
          :key="item.id"
          class="flex items-center gap-2 py-1.5 group hover:bg-gray-50 rounded px-2"
          :style="{ paddingLeft: (item.nivel * 24 + 8) + 'px' }"
        >
          <button
            v-if="item.hijos?.length"
            @click="toggle(item.id)"
            class="w-4 text-gray-400"
          >
            {{ expandidas.has(item.id) ? '▾' : '▸' }}
          </button>
          <span v-else class="w-4"></span>

          <span>{{ item.hijos?.length ? '📁' : '📄' }}</span>
          <span class="font-medium">{{ item.nombre }}</span>
          <span class="text-xs text-gray-400 font-mono">{{ item.codigo }}</span>

          <span class="ml-auto flex gap-3 opacity-0 group-hover:opacity-100 text-xs">
            <button class="text-indigo-600 hover:underline" @click="abrirCrear(item.id)">+ subcategoría</button>
            <button class="text-indigo-600 hover:underline" @click="abrirEditar(item)">editar</button>
            <button class="text-red-600 hover:underline" @click="eliminar(item)">eliminar</button>
          </span>
        </div>

        <p v-if="arbol.length === 0" class="text-center text-gray-400 py-6">
          No hay categorías todavía. Empieza creando una raíz.
        </p>
      </div>
    </div>

    <Modal :show="showModal" @close="showModal = false">
      <div class="p-6">
        <h3 class="text-lg font-medium mb-4">
          {{ editando ? 'Editar Categoría' : 'Nueva Categoría' }}
        </h3>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <InputLabel value="Código" />
            <TextInput v-model="form.codigo" class="mt-1 block w-full uppercase" />
            <InputError :message="form.errors.codigo" class="mt-1" />
          </div>
          <div>
            <InputLabel value="Nombre" />
            <TextInput v-model="form.nombre" class="mt-1 block w-full" />
            <InputError :message="form.errors.nombre" class="mt-1" />
          </div>
        </div>

        <div class="mt-4">
            <InputLabel value="Categoría padre" />
                <select v-model="form.parent_id" class="mt-1 block w-full border-gray-300 rounded-md">
                    <option :value="null">— Ninguna (categoría raíz) —</option>
                    <option v-for="c in opcionesPadre" :key="c.id" :value="c.id">
                    {{ c.label }}
                    </option>
                </select>
            <InputError :message="form.errors.parent_id" class="mt-1" />
        </div>

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