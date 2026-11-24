<template>
  <AppLayoutAdmin>
    <template #header>
      <h2 class="text-xl font-semibold text-gray-800 leading-tight">
        {{ producto ? 'Editar Producto' : 'Nuevo Producto' }}
      </h2>
    </template>

    <div class="py-6 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="bg-white rounded-xl border shadow-sm">

        <!-- Tabs de sección -->
        <div class="border-b border-gray-200 flex">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            @click="tabActivo = tab.id"
            :class="[
              'px-5 py-3 text-sm font-medium border-b-2 -mb-px transition',
              tabActivo === tab.id
                ? 'border-blue-600 text-blue-700'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
            ]">
            {{ tab.label }}
          </button>
        </div>

        <form @submit.prevent="submit" class="p-6">

          <!-- ── TAB: DATOS BÁSICOS ── -->
          <div v-show="tabActivo === 'basico'" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Código *</label>
                <input v-model="form.codigo" type="text" required
                  class="w-full border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                  :class="errors.codigo ? 'border-red-500' : 'border-gray-200'"/>
                <p v-if="errors.codigo" class="text-red-500 text-xs mt-1">{{ errors.codigo }}</p>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Código de Barra</label>
                <input v-model="form.codigo_barra" type="text"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Unidad de Medida *</label>
                <select v-model="form.unidad_medida_id" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option value="">Seleccionar...</option>
                  <option v-for="u in unidades" :key="u.id" :value="u.id">{{ u.codigo }} - {{ u.nombre }}</option>
                </select>
                <p v-if="errors.unidad_medida_id" class="text-red-500 text-xs mt-1">{{ errors.unidad_medida_id }}</p>
              </div>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Descripción *</label>
              <input v-model="form.descripcion" type="text" required
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              <p v-if="errors.descripcion" class="text-red-500 text-xs mt-1">{{ errors.descripcion }}</p>
            </div>

            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1">Descripción larga</label>
              <textarea v-model="form.descripcion_larga" rows="2"
                class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Categoría</label>
                <select v-model="form.categoria_id"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option value="">Sin categoría</option>
                  <option v-for="c in categorias" :key="c.id" :value="c.id">{{ c.nombre }}</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Tipo de producto *</label>
                <select v-model="form.tipo" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option value="mercaderia">Mercadería</option>
                  <option value="materia_prima">Materia prima</option>
                  <option value="producto_terminado">Producto terminado</option>
                  <option value="servicio">Servicio</option>
                  <option value="activo_fijo">Activo fijo</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Método de costeo *</label>
                <select v-model="form.metodo_costeo" required
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option value="PROM">Promedio ponderado</option>
                  <option value="PEPS">PEPS (primero en entrar)</option>
                  <option value="ESP">Precio específico</option>
                </select>
                <p class="text-xs text-gray-400 mt-1">No se puede cambiar si ya hay movimientos.</p>
              </div>
            </div>

            <div class="flex items-center gap-6 pt-1">
              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" v-model="form.controla_stock" class="rounded border-gray-300 text-blue-600"/>
                Controla stock
              </label>
              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" v-model="form.aplica_iva" class="rounded border-gray-300 text-blue-600"/>
                Aplica IVA (13%)
              </label>
              <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" v-model="form.activo" class="rounded border-gray-300 text-blue-600"/>
                Producto activo
              </label>
            </div>
          </div>

          <!-- ── TAB: PRECIOS ── -->
          <div v-show="tabActivo === 'precios'" class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="p-4 bg-gray-50 rounded-xl border">
                <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">Costos</h4>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Precio de costo *</label>
                  <input v-model="form.precio_costo" type="number" step="0.0001" min="0" required
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <div class="mt-3">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Stock mínimo</label>
                  <input v-model="form.stock_minimo" type="number" step="0.0001" min="0"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <div class="mt-3">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Stock máximo</label>
                  <input v-model="form.stock_maximo" type="number" step="0.0001" min="0"
                    class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
              </div>
              <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                <h4 class="text-xs font-semibold text-blue-700 uppercase tracking-wide mb-3">Precios de venta</h4>
                <div>
                  <label class="block text-xs font-medium text-gray-700 mb-1">Precio venta 1 (normal) *</label>
                  <input v-model="form.precio_venta" type="number" step="0.0001" min="0" required
                    class="w-full border border-blue-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <div class="mt-3">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Precio venta 2 (mayorista)</label>
                  <input v-model="form.precio_venta_2" type="number" step="0.0001" min="0"
                    class="w-full border border-blue-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <div class="mt-3">
                  <label class="block text-xs font-medium text-gray-700 mb-1">Precio venta 3 (especial)</label>
                  <input v-model="form.precio_venta_3" type="number" step="0.0001" min="0"
                    class="w-full border border-blue-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
                </div>
                <!-- Margen calculado -->
                <div v-if="form.precio_costo > 0 && form.precio_venta > 0"
                  class="mt-4 p-3 bg-white rounded-lg border border-blue-200 text-xs text-center">
                  Margen bruto: <span class="font-bold text-blue-700">{{ margen }}%</span>
                </div>
              </div>
            </div>
          </div>

          <!-- ── TAB: CUENTAS CONTABLES ── -->
          <div v-show="tabActivo === 'cuentas'" class="space-y-4">
            <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800">
              Estas cuentas se usan para generar asientos automáticos al comprar y vender. Si no se configuran, los asientos deberán hacerse manualmente.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div v-for="campo in camposCuentas" :key="campo.key">
                <label class="block text-xs font-semibold text-gray-700 mb-1">{{ campo.label }}</label>
                <p class="text-xs text-gray-400 mb-1">{{ campo.hint }}</p>
                <select v-model="form[campo.key]"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                  <option :value="null">Sin asignar</option>
                  <option v-for="c in cuentas" :key="c.id" :value="c.id">{{ c.codigo }} - {{ c.nombre }}</option>
                </select>
              </div>
            </div>
          </div>

          <!-- ── TAB: SIAT ── -->
          <div v-show="tabActivo === 'siat'" class="space-y-4">
            <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
              Datos requeridos para emitir facturas electrónicas a través de la API del SIN Bolivia.
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Código SIN (actividad económica)</label>
                <input v-model="form.codigo_sin" type="text" maxlength="20"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"/>
              </div>
              <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Descripción para factura SIAT</label>
                <input v-model="form.descripcion_sin" type="text" maxlength="200"
                  class="w-full border border-gray-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"
                  placeholder="Como aparecerá en la factura electrónica"/>
              </div>
            </div>
          </div>

          <!-- Errores generales -->
          <div v-if="Object.keys(errors).length"
            class="mt-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
            <div v-for="(e, k) in errors" :key="k">{{ e }}</div>
          </div>

          <!-- Botones -->
          <div class="flex items-center justify-end gap-3 mt-6 pt-4 border-t">
            <Link :href="route('productos.index')"
              class="px-4 py-2 border border-gray-200 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
              Cancelar
            </Link>
            <button type="submit" :disabled="guardando"
              class="px-5 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition disabled:opacity-50">
              {{ guardando ? 'Guardando...' : (producto ? 'Actualizar' : 'Crear Producto') }}
            </button>
          </div>
        </form>
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
    producto:   { type: Object, default: null },
    categorias: { type: Array, default: () => [] },
    unidades:   { type: Array, default: () => [] },
    cuentas:    { type: Array, default: () => [] },
    errors:     { type: Object, default: () => ({}) },
  },
  data() {
    const p = this.producto
    return {
      tabActivo: 'basico',
      guardando: false,
      tabs: [
        { id: 'basico',   label: 'Datos básicos' },
        { id: 'precios',  label: 'Precios y stock' },
        { id: 'cuentas',  label: 'Cuentas contables' },
        { id: 'siat',     label: 'Facturación SIAT' },
      ],
      camposCuentas: [
        { key: 'cuenta_activo_id',       label: 'Cuenta de Almacén / Activo',          hint: 'DEBE al comprar. Ej: Almacén de Mercaderías' },
        { key: 'cuenta_costo_venta_id',  label: 'Cuenta de Costo de Ventas',            hint: 'DEBE al vender. Ej: CMV - Costo de mercaderías vendidas' },
        { key: 'cuenta_ingreso_id',      label: 'Cuenta de Ingresos / Ventas',          hint: 'HABER al vender. Ej: Ventas de mercaderías' },
        { key: 'cuenta_variacion_id',    label: 'Cuenta de Variación / Ajuste',         hint: 'Para ajustes de inventario y mermas' },
      ],
      form: {
        codigo:              p?.codigo             ?? '',
        codigo_barra:        p?.codigo_barra       ?? '',
        descripcion:         p?.descripcion        ?? '',
        descripcion_larga:   p?.descripcion_larga  ?? '',
        categoria_id:        p?.categoria_id       ?? null,
        unidad_medida_id:    p?.unidad_medida_id   ?? '',
        tipo:                p?.tipo               ?? 'mercaderia',
        precio_costo:        p?.precio_costo       ?? 0,
        precio_venta:        p?.precio_venta       ?? 0,
        precio_venta_2:      p?.precio_venta_2     ?? 0,
        precio_venta_3:      p?.precio_venta_3     ?? 0,
        aplica_iva:          p?.aplica_iva         ?? true,
        porcentaje_iva:      p?.porcentaje_iva     ?? 13,
        metodo_costeo:       p?.metodo_costeo      ?? 'PROM',
        cuenta_activo_id:    p?.cuenta_activo_id   ?? null,
        cuenta_costo_venta_id: p?.cuenta_costo_venta_id ?? null,
        cuenta_ingreso_id:   p?.cuenta_ingreso_id  ?? null,
        cuenta_variacion_id: p?.cuenta_variacion_id ?? null,
        stock_minimo:        p?.stock_minimo       ?? 0,
        stock_maximo:        p?.stock_maximo       ?? 0,
        controla_stock:      p?.controla_stock     ?? true,
        codigo_sin:          p?.codigo_sin         ?? '',
        descripcion_sin:     p?.descripcion_sin    ?? '',
        activo:              p?.activo             ?? true,
      },
    }
  },
  computed: {
    margen() {
      const c = parseFloat(this.form.precio_costo) || 0
      const v = parseFloat(this.form.precio_venta) || 0
      if (!c || !v) return 0
      return Math.round(((v - c) / v) * 100 * 10) / 10
    },
  },
  methods: {
    submit() {
      this.guardando = true
      const method = this.producto ? 'put' : 'post'
      const url    = this.producto ? route('productos.update', this.producto.id) : route('productos.store')
      router[method](url, this.form, { onFinish: () => { this.guardando = false } })
    },
  },
}
</script>
