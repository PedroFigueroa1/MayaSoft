<template>
  <AppLayoutAdmin>
    <template #header>
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Configuración SIAT</h2>
        <p class="text-xs text-gray-500 mt-0.5">{{ empresa.razon_social }} · NIT {{ empresa.nit }}</p>
      </div>
    </template>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

      <div v-if="$page.props.flash?.success"
        class="p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        {{ $page.props.flash.success }}
      </div>
      <div v-if="$page.props.errors?.error"
        class="p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
        {{ $page.props.errors.error }}
      </div>

      <!-- Avisos -->
      <div v-if="!config" class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs">
        Esta empresa aún no tiene configuración SIAT. Completa las credenciales para poder facturar.
      </div>
      <div v-else-if="tokenPorVencer"
        class="p-3 bg-amber-50 border border-amber-200 text-amber-800 rounded-lg text-xs">
        El token delegado vence el {{ fmtFecha(config.token_expira_at) }}. Genera uno nuevo en el portal del SIAT y pégalo aquí.
      </div>
      <div v-if="form.cod_ambiente === '2'" class="p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-lg text-xs">
        Ambiente <b>piloto</b>: las facturas no tienen validez tributaria. Cambia a producción solo después de aprobar las pruebas de certificación del SIN.
      </div>

      <!-- ══ Credenciales ══ -->
      <form @submit.prevent="guardar" class="bg-white rounded-xl border shadow-sm p-5 space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 pb-2 border-b">Credenciales de la empresa</h3>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Ambiente *</label>
            <select v-model="form.cod_ambiente" :class="input">
              <option value="2">Piloto (pruebas)</option>
              <option value="1">Producción</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Modalidad *</label>
            <select v-model="form.cod_modalidad" :class="input">
              <option value="1">Electrónica en línea</option>
              <option value="2">Computarizada en línea</option>
              <option value="3">Portal web en línea</option>
            </select>
            <p class="text-[11px] text-gray-400 mt-1">Debe coincidir con la habilitada en el SIN para tu NIT.</p>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Tipo de factura *</label>
            <input v-model="form.cod_tipo_factura" type="text" maxlength="5" :class="input"/>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Código de sistema</label>
            <input v-model="form.cod_sistema" type="text" placeholder="Asignado por el SIN" :class="input"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Vencimiento del token</label>
            <input v-model="form.token_expira_at" type="date" :class="input"/>
          </div>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">
            Token delegado {{ config?.tiene_token ? '' : '*' }}
          </label>
          <textarea v-model="form.token_delegado" rows="3" autocomplete="off" spellcheck="false"
            :placeholder="config?.tiene_token ? '•••••• guardado — pega uno nuevo solo para reemplazarlo' : 'Pega aquí el token del portal SIAT'"
            :class="[input, 'font-mono text-xs']"></textarea>
          <p v-if="$page.props.errors?.token_delegado" class="text-xs text-red-600 mt-1">{{ $page.props.errors.token_delegado }}</p>
          <p class="text-[11px] text-gray-400 mt-1">Se guarda cifrado y no se vuelve a mostrar.</p>
        </div>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">URL del servicio (opcional)</label>
          <input v-model="form.url_api" type="url" placeholder="Vacío = la URL oficial del ambiente" :class="input"/>
        </div>

        <div class="flex justify-end">
          <button type="submit" :disabled="guardando"
            class="px-5 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg transition disabled:opacity-50">
            {{ guardando ? 'Guardando...' : 'Guardar credenciales' }}
          </button>
        </div>
      </form>

      <!-- ══ Puntos de venta ══ -->
      <div v-if="config" class="bg-white rounded-xl border shadow-sm p-5">
        <div class="flex items-center justify-between pb-2 border-b mb-3">
          <h3 class="text-sm font-semibold text-gray-700">Puntos de venta</h3>
          <button type="button" @click="nuevoPunto"
            class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium rounded-lg transition">
            + Agregar punto
          </button>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="bg-gray-50 border-y">
                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Nombre</th>
                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Suc.</th>
                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">P.V.</th>
                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-600">Cajeros</th>
                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">CUIS</th>
                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">CUFD</th>
                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-600">Últ. N°</th>
                <th class="px-3 py-2"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in puntos" :key="p.id" class="border-b border-gray-100" :class="p.activo ? '' : 'opacity-50'">
                <td class="px-3 py-2 text-xs text-gray-800">{{ p.nombre }}</td>
                <td class="px-3 py-2 text-center text-xs font-mono">{{ p.cod_sucursal }}</td>
                <td class="px-3 py-2 text-center text-xs font-mono">{{ p.cod_punto_venta }}</td>
                <td class="px-3 py-2 text-xs text-gray-600">
                  {{ p.usuarios.map(u => u.name).join(', ') || '—' }}
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="p.cuis_vigente ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                    class="text-xs font-medium px-2 py-0.5 rounded-full">{{ p.cuis_vigente ? 'Vigente' : 'Falta' }}</span>
                </td>
                <td class="px-3 py-2 text-center">
                  <span :class="p.cufd_vigente ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700'"
                    class="text-xs font-medium px-2 py-0.5 rounded-full">{{ p.cufd_vigente ? 'Vigente' : 'Vencido' }}</span>
                </td>
                <td class="px-3 py-2 text-center text-xs font-mono text-gray-500">{{ p.correlativo_actual }}</td>
                <td class="px-3 py-2">
                  <div class="flex justify-end gap-1">
                    <button type="button" @click="renovar(p)" :disabled="procesando === p.id"
                      class="px-2 py-1 text-xs text-blue-700 hover:bg-blue-50 rounded transition disabled:opacity-50">
                      {{ procesando === p.id ? 'Solicitando...' : 'Solicitar CUIS/CUFD' }}
                    </button>
                    <button type="button" @click="editarPunto(p)"
                      class="px-2 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded transition">Editar</button>
                    <button type="button" @click="eliminarPunto(p)"
                      class="px-2 py-1 text-xs text-red-500 hover:bg-red-50 rounded transition">Eliminar</button>
                  </div>
                </td>
              </tr>
              <tr v-if="!puntos.length">
                <td colspan="8" class="px-3 py-8 text-center text-xs text-gray-400">
                  Sin puntos de venta. Agrega al menos uno (sucursal 0, punto 0 = casa matriz).
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="text-[11px] text-gray-400 mt-2">
          Cada cajero factura por el punto que tenga asignado. Los códigos de sucursal y punto deben existir en el SIN.
        </p>
      </div>
    </div>

    <!-- Modal punto de venta -->
    <div v-if="modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40" @click.self="modal = false">
      <form @submit.prevent="guardarPunto" class="bg-white rounded-xl shadow-xl w-full max-w-lg p-5 space-y-3">
        <h3 class="text-base font-semibold text-gray-800">{{ punto.id ? 'Editar' : 'Nuevo' }} punto de venta</h3>

        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Nombre *</label>
          <input v-model="punto.nombre" required maxlength="100" placeholder="Caja 1 - Casa matriz" :class="input"/>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cód. sucursal *</label>
            <input v-model="punto.cod_sucursal" type="number" min="0" required :class="input"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Cód. punto de venta *</label>
            <input v-model="punto.cod_punto_venta" type="number" min="0" required :class="input"/>
            <p v-if="$page.props.errors?.cod_punto_venta" class="text-xs text-red-600 mt-1">{{ $page.props.errors.cod_punto_venta }}</p>
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Dirección (sale en la factura)</label>
          <input v-model="punto.direccion" maxlength="250" :class="input"/>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Municipio</label>
            <input v-model="punto.municipio" maxlength="100" :class="input"/>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Teléfono</label>
            <input v-model="punto.telefono" maxlength="30" :class="input"/>
          </div>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Cajeros autorizados</label>
          <div class="border border-gray-200 rounded-lg p-2 max-h-32 overflow-y-auto space-y-1">
            <label v-for="u in usuarios" :key="u.id" class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
              <input type="checkbox" :value="u.id" v-model="punto.usuarios" class="rounded border-gray-300 text-blue-600"/>
              {{ u.name }}
            </label>
          </div>
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
          <input type="checkbox" v-model="punto.activo" class="rounded border-gray-300 text-blue-600"/> Activo
        </label>

        <div class="flex justify-end gap-2 pt-1">
          <button type="button" @click="modal = false"
            class="px-3 py-1.5 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">Cancelar</button>
          <button type="submit" :disabled="guardando"
            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
            Guardar
          </button>
        </div>
      </form>
    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { router } from '@inertiajs/vue3'

const R = {
  guardar:  'configuracion.siat.guardar',
  pStore:   'configuracion.siat.puntos.store',
  pUpdate:  'configuracion.siat.puntos.update',
  pDestroy: 'configuracion.siat.puntos.destroy',
  codigos:  'configuracion.siat.puntos.codigos',
}

const puntoVacio = () => ({
  id: null, nombre: '', cod_sucursal: 0, cod_punto_venta: 0,
  direccion: '', municipio: '', telefono: '', activo: true, usuarios: [],
})

export default {
  components: { AppLayoutAdmin },
  props: {
    empresa:  { type: Object, required: true },
    config:   { type: Object, default: null },
    puntos:   { type: Array, default: () => [] },
    usuarios: { type: Array, default: () => [] },
  },
  data() {
    const c = this.config
    return {
      input: 'w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400',
      guardando: false,
      procesando: null,
      modal: false,
      punto: puntoVacio(),
      form: {
        cod_ambiente:     c?.cod_ambiente ?? '2',
        cod_modalidad:    c?.cod_modalidad ?? '2',
        cod_tipo_factura: c?.cod_tipo_factura ?? '1',
        cod_sistema:      c?.cod_sistema ?? '',
        url_api:          c?.url_api ?? '',
        token_expira_at:  c?.token_expira_at ? String(c.token_expira_at).substring(0, 10) : '',
        token_delegado:   '',
      },
    }
  },
  computed: {
    tokenPorVencer() {
      if (!this.config?.token_expira_at) return false
      const dias = (new Date(this.config.token_expira_at) - new Date()) / 86400000
      return dias < 15
    },
  },
  methods: {
    guardar() {
      this.guardando = true
      router.post(route(R.guardar), this.form, {
        preserveScroll: true,
        onSuccess: () => { this.form.token_delegado = '' },
        onFinish:  () => { this.guardando = false },
      })
    },
    nuevoPunto()   { this.punto = puntoVacio(); this.modal = true },
    editarPunto(p) {
      this.punto = {
        id: p.id, nombre: p.nombre, cod_sucursal: p.cod_sucursal, cod_punto_venta: p.cod_punto_venta,
        direccion: p.direccion ?? '', municipio: p.municipio ?? '', telefono: p.telefono ?? '',
        activo: !!p.activo, usuarios: [...p.usuarios_ids],
      }
      this.modal = true
    },
    guardarPunto() {
      this.guardando = true
      const opts = {
        preserveScroll: true,
        onSuccess: () => { this.modal = false },
        onFinish:  () => { this.guardando = false },
      }
      this.punto.id
        ? router.put(route(R.pUpdate, this.punto.id), this.punto, opts)
        : router.post(route(R.pStore), this.punto, opts)
    },
    eliminarPunto(p) {
      if (confirm(`¿Eliminar el punto «${p.nombre}»?`))
        router.delete(route(R.pDestroy, p.id), { preserveScroll: true })
    },
    renovar(p) {
      this.procesando = p.id
      router.post(route(R.codigos, p.id), {}, {
        preserveScroll: true,
        onFinish: () => { this.procesando = null },
      })
    },
    fmtFecha(d) {
      if (!d) return ''
      const [y, m, day] = String(d).substring(0, 10).split('-')
      return `${day}/${m}/${y}`
    },
  },
}
</script>
