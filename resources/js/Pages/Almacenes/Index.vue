<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800">Almacenes</h2>
    </template>

    <div class="py-6 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="$page.props.flash?.success" class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        {{ $page.props.flash.success }}
      </div>

      <!-- Formulario de creación -->
      <div class="bg-white rounded-xl border shadow-sm p-5">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">{{ editando ? 'Editar almacén' : 'Nuevo almacén' }}</h3>
        <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Código *</label>
            <input v-model="form.codigo" type="text" required :disabled="!!editando"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:bg-gray-50"/>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
            <input v-model="form.nombre" type="text" required
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Responsable</label>
            <input v-model="form.responsable" type="text"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Dirección</label>
            <input v-model="form.direccion" type="text"
              class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
          </div>
          <div class="flex items-center gap-4 md:col-span-2">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
              <input type="checkbox" v-model="form.es_punto_venta" class="rounded border-gray-300 text-blue-600"/>
              Punto de venta (POS)
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
              <input type="checkbox" v-model="form.activo" class="rounded border-gray-300 text-blue-600"/>
              Activo
            </label>
          </div>
          <div class="md:col-span-4 flex justify-end gap-2">
            <button v-if="editando" type="button" @click="cancelar"
              class="px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition">Cancelar</button>
            <button type="submit" :disabled="guardando"
              class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
              {{ guardando ? 'Guardando...' : (editando ? 'Actualizar' : 'Crear almacén') }}
            </button>
          </div>
        </form>
      </div>

      <!-- Lista de almacenes -->
      <div class="bg-white rounded-xl border overflow-hidden">
        <table class="w-full text-sm">
          <thead>
            <tr class="bg-gray-50 border-b">
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Código</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Nombre</th>
              <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wide">Responsable</th>
              <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Punto de venta</th>
              <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Estado</th>
              <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wide">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-if="!almacenes.length">
              <td colspan="6" class="px-6 py-10 text-center text-gray-400">No hay almacenes registrados. Crea el primero arriba.</td>
            </tr>
            <tr v-for="a in almacenes" :key="a.id" class="hover:bg-gray-50 transition-colors">
              <td class="px-4 py-2 font-mono text-xs">{{ a.codigo }}</td>
              <td class="px-4 py-2 font-medium text-gray-800 text-sm">{{ a.nombre }}</td>
              <td class="px-4 py-2 text-xs text-gray-600">{{ a.responsable || '-' }}</td>
              <td class="px-4 py-2 text-center">
                <span v-if="a.es_punto_venta" class="text-green-600 text-xs font-medium">✓ Sí</span>
                <span v-else class="text-gray-300 text-xs">-</span>
              </td>
              <td class="px-4 py-2 text-center">
                <span :class="a.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'" class="text-xs px-2 py-0.5 rounded-full font-medium">
                  {{ a.activo ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-4 py-2 text-center">
                <div class="flex items-center justify-center gap-2">
                  <Link :href="route('almacenes.stock', a.id)" class="text-xs text-blue-600 hover:underline">Ver stock</Link>
                  <button @click="editar(a)" class="p-1 text-gray-500 hover:text-amber-600 transition" title="Editar">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    almacenes: { type: Array, default: () => [] },
  },
  data() {
    return {
      guardando: false,
      editando: null,
      form: this.formVacio(),
    }
  },
  methods: {
    formVacio() {
      return { codigo: '', nombre: '', direccion: '', responsable: '', es_punto_venta: false, es_produccion: false, activo: true }
    },
    editar(a) {
      this.editando = a.id
      this.form = { codigo: a.codigo, nombre: a.nombre, direccion: a.direccion, responsable: a.responsable, es_punto_venta: !!a.es_punto_venta, es_produccion: !!a.es_produccion, activo: !!a.activo }
    },
    cancelar() {
      this.editando = null
      this.form = this.formVacio()
    },
    submit() {
      this.guardando = true
      if (this.editando) {
        router.put(route('almacenes.update', this.editando), this.form, {
          onFinish: () => { this.guardando = false },
          onSuccess: () => this.cancelar(),
        })
      } else {
        router.post(route('almacenes.store'), this.form, {
          onFinish: () => { this.guardando = false },
          onSuccess: () => this.cancelar(),
        })
      }
    },
  },
}
</script>
