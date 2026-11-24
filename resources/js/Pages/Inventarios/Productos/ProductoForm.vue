<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import SearchSelect from '@/Components/SearchSelect.vue'

const props = defineProps({
    producto: { type: Object, default: () => null },
    categorias: Array,
    unidades: Array,
    cuentas: Array,
})

const form = useForm({
    codigo: props.producto?.codigo ?? '',
    codigo_barra: props.producto?.codigo_barra ?? '',
    descripcion: props.producto?.descripcion ?? '',
    descripcion_larga: props.producto?.descripcion_larga ?? '',
    categoria_id: props.producto?.categoria_id ?? '',
    unidad_medida_id: props.producto?.unidad_medida_id ?? '',
    tipo: props.producto?.tipo ?? 'mercaderia',
    precio_costo: props.producto?.precio_costo ?? 0,
    precio_venta: props.producto?.precio_venta ?? 0,
    precio_venta_2: props.producto?.precio_venta_2 ?? 0,
    precio_venta_3: props.producto?.precio_venta_3 ?? 0,
    aplica_iva: props.producto?.aplica_iva ?? true,
    porcentaje_iva: props.producto?.porcentaje_iva ?? 13,
    metodo_costeo: props.producto?.metodo_costeo ?? 'PROM',
    cuenta_activo_id: props.producto?.cuenta_activo_id ?? '',
    cuenta_costo_venta_id: props.producto?.cuenta_costo_venta_id ?? '',
    cuenta_ingreso_id: props.producto?.cuenta_ingreso_id ?? '',
    cuenta_variacion_id: props.producto?.cuenta_variacion_id ?? '',
    stock_minimo: props.producto?.stock_minimo ?? 0,
    stock_maximo: props.producto?.stock_maximo ?? 0,
    controla_stock: props.producto?.controla_stock ?? true,
    codigo_sin: props.producto?.codigo_sin ?? '',
    descripcion_sin: props.producto?.descripcion_sin ?? '',
    activo: props.producto?.activo ?? true,
    _method: props.producto ? 'put' : 'post',
})

const submit = () => {
    if (props.producto) {
        form.post(route('inventario.productos.update', props.producto.id))
    } else {
        form.post(route('inventario.productos.store'))
    }
}

const tipos = [
    { value: 'mercaderia', label: 'Mercadería' },
    { value: 'materia_prima', label: 'Materia Prima' },
    { value: 'producto_terminado', label: 'Producto Terminado' },
    { value: 'servicio', label: 'Servicio' },
    { value: 'activo_fijo', label: 'Activo Fijo' },
]
</script>

<template>
  <form @submit.prevent="submit">
    <!-- IDENTIFICACIÓN -->
    <div class="mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Identificación</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Código <span class="text-red-500">*</span></label>
          <input v-model="form.codigo" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <div v-if="form.errors.codigo" class="text-sm text-red-600 mt-1">{{ form.errors.codigo }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Código de Barras</label>
          <input v-model="form.codigo_barra" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <div v-if="form.errors.codigo_barra" class="text-sm text-red-600 mt-1">{{ form.errors.codigo_barra }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Unidad de Medida <span class="text-red-500">*</span></label>
          <SearchSelect
            v-model="form.unidad_medida_id"
            :options="unidades.map(u => ({ ...u, _label: `${u.nombre} (${u.simbolo})` }))"
            label-key="_label"
            placeholder="Buscar unidad..."
            empty-label="Seleccione..."
            :allow-empty="false"
          />
          <div v-if="form.errors.unidad_medida_id" class="text-sm text-red-600 mt-1">{{ form.errors.unidad_medida_id }}</div>
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Descripción <span class="text-red-500">*</span></label>
          <input v-model="form.descripcion" type="text" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <div v-if="form.errors.descripcion" class="text-sm text-red-600 mt-1">{{ form.errors.descripcion }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Categoría</label>
          <SearchSelect
            v-model="form.categoria_id"
            :options="categorias"
            placeholder="Buscar categoría..."
            empty-label="Sin categoría"
            empty-option-label="Sin categoría"
          />
        </div>
        <div class="md:col-span-3">
          <label class="block text-sm font-medium text-gray-700">Descripción Larga</label>
          <textarea v-model="form.descripcion_larga" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
        </div>
      </div>
    </div>

    <!-- CLASIFICACIÓN Y PRECIOS -->
    <div class="mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Clasificación y Precios</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Tipo <span class="text-red-500">*</span></label>
          <select v-model="form.tipo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option v-for="t in tipos" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio Costo <span class="text-red-500">*</span></label>
          <input v-model.number="form.precio_costo" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <div v-if="form.errors.precio_costo" class="text-sm text-red-600 mt-1">{{ form.errors.precio_costo }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio Venta <span class="text-red-500">*</span></label>
          <input v-model.number="form.precio_venta" type="number" step="0.0001" min="0" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          <div v-if="form.errors.precio_venta" class="text-sm text-red-600 mt-1">{{ form.errors.precio_venta }}</div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio Venta 2 (Mayorista)</label>
          <input v-model.number="form.precio_venta_2" type="number" step="0.0001" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Precio Venta 3 (Especial)</label>
          <input v-model.number="form.precio_venta_3" type="number" step="0.0001" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
      </div>
    </div>

    <!-- IVA Y COSTEO -->
    <div class="mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">IVA y Costeo</h3>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex items-center gap-4">
          <label class="inline-flex items-center">
            <input v-model="form.aplica_iva" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-sm text-gray-700">Aplica IVA</span>
          </label>
          <div v-if="form.aplica_iva" class="flex-1">
            <input v-model.number="form.porcentaje_iva" type="number" step="0.01" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Método de Costeo <span class="text-red-500">*</span></label>
          <select v-model="form.metodo_costeo" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
            <option value="PROM">Promedio Ponderado (PROM)</option>
            <option value="PEPS">Primero en Entrar, Primero en Salir (PEPS)</option>
            <option value="ESP">Precio Específico (ESP)</option>
          </select>
          <div v-if="form.errors.metodo_costeo" class="text-sm text-red-600 mt-1">{{ form.errors.metodo_costeo }}</div>
        </div>
      </div>
    </div>

    <!-- CUENTAS CONTABLES -->
    <div class="mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Cuentas Contables</h3>
        <p class="text-xs text-gray-500 mb-4">
            Estas cuentas se usan para generar los asientos contables automáticos al comprar, vender o ajustar inventario.
            Si no sabes cuál elegir, consulta con contabilidad.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Cuenta Activo -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700">
                Cuenta Activo (Almacén)
                <span class="text-gray-400 font-normal">— Activo Corriente / Inventarios</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 mb-2">
                Aquí se acumula el <strong>valor del inventario</strong> mientras el producto está en almacén.
                Busca una cuenta que empiece con <code class="bg-gray-200 px-1 rounded">1.1.3</code> (Inventarios).
            </p>
            <SearchSelect
                v-model="form.cuenta_activo_id"
                :options="cuentas.map(c => ({ ...c, _label: `${c.codigo} - ${c.nombre}` }))"
                label-key="_label"
                placeholder="Ej: 1.1.3.01 - Inventario de Mercaderías"
                empty-label="Seleccione cuenta de inventario..."
            />
            <p class="mt-1 text-xs text-gray-400">Ejemplo: <code>1.1.3.01 - Inventario de Mercaderías</code></p>
            <div v-if="form.errors.cuenta_activo_id" class="text-sm text-red-600 mt-1">{{ form.errors.cuenta_activo_id }}</div>
            </div>

            <!-- Cuenta Costo de Venta -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700">
                Cuenta Costo de Venta
                <span class="text-gray-400 font-normal">— Gasto / Costo de Ventas</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 mb-2">
                Se <strong>debita al vender</strong>: representa el costo del producto que salió del almacén.
                Busca una cuenta que empiece con <code class="bg-gray-200 px-1 rounded">5.1</code> (Gastos).
            </p>
            <SearchSelect
                v-model="form.cuenta_costo_venta_id"
                :options="cuentas.map(c => ({ ...c, _label: `${c.codigo} - ${c.nombre}` }))"
                label-key="_label"
                placeholder="Ej: 5.1.1.01 - Costo de Ventas"
                empty-label="Seleccione cuenta de costo..."
            />
            <p class="mt-1 text-xs text-gray-400">Ejemplo: <code>5.1.1.01 - Costo de Ventas</code></p>
            <div v-if="form.errors.cuenta_costo_venta_id" class="text-sm text-red-600 mt-1">{{ form.errors.cuenta_costo_venta_id }}</div>
            </div>

            <!-- Cuenta Ingreso -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700">
                Cuenta Ingreso (Ventas)
                <span class="text-gray-400 font-normal">— Ingreso / Ventas</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 mb-2">
                Se <strong>acredita al vender</strong>: representa el ingreso por la venta del producto.
                Busca una cuenta que empiece con <code class="bg-gray-200 px-1 rounded">4.1</code> (Ingresos).
            </p>
            <SearchSelect
                v-model="form.cuenta_ingreso_id"
                :options="cuentas.map(c => ({ ...c, _label: `${c.codigo} - ${c.nombre}` }))"
                label-key="_label"
                placeholder="Ej: 4.1.1.01 - Ventas"
                empty-label="Seleccione cuenta de ingreso..."
            />
            <p class="mt-1 text-xs text-gray-400">Ejemplo: <code>4.1.1.01 - Ventas</code></p>
            <div v-if="form.errors.cuenta_ingreso_id" class="text-sm text-red-600 mt-1">{{ form.errors.cuenta_ingreso_id }}</div>
            </div>

            <!-- Cuenta Variación -->
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700">
                Cuenta Variación (Ajustes)
                <span class="text-gray-400 font-normal">— Gasto o Ingreso por Variación</span>
            </label>
            <p class="mt-1 text-xs text-gray-500 mb-2">
                Para <strong>diferencias por ajustes</strong>, mermas, sobrantes o diferencias de costeo.
                Puede ser una cuenta de gasto (<code class="bg-gray-200 px-1 rounded">5.x</code>) o de ingreso (<code class="bg-gray-200 px-1 rounded">4.x</code>).
            </p>
            <SearchSelect
                v-model="form.cuenta_variacion_id"
                :options="cuentas.map(c => ({ ...c, _label: `${c.codigo} - ${c.nombre}` }))"
                label-key="_label"
                placeholder="Ej: 5.1.1.02 - Variación de Inventario"
                empty-label="Seleccione cuenta de variación..."
            />
            <p class="mt-1 text-xs text-gray-400">Ejemplo: <code>5.1.1.02 - Variación de Inventario</code></p>
            <div v-if="form.errors.cuenta_variacion_id" class="text-sm text-red-600 mt-1">{{ form.errors.cuenta_variacion_id }}</div>
            </div>

        </div>
    </div>

    <!-- STOCK Y SIAT -->
    <div class="mb-8">
      <h3 class="text-lg font-medium text-gray-900 mb-4 border-b pb-2">Stock y SIAT</h3>
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <label class="block text-sm font-medium text-gray-700">Stock Mínimo</label>
          <input v-model.number="form.stock_minimo" type="number" step="0.0001" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Stock Máximo</label>
          <input v-model.number="form.stock_maximo" type="number" step="0.0001" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="flex items-center pt-6">
          <label class="inline-flex items-center">
            <input v-model="form.controla_stock" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-sm text-gray-700">Controlar Stock</span>
          </label>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700">Código SIN</label>
          <input v-model="form.codigo_sin" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="md:col-span-2">
          <label class="block text-sm font-medium text-gray-700">Descripción SIN (para factura)</label>
          <input v-model="form.descripcion_sin" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
        </div>
        <div class="flex items-center pt-6">
          <label class="inline-flex items-center">
            <input v-model="form.activo" type="checkbox" true-value="1" false-value="0" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
            <span class="ml-2 text-sm text-gray-700">Activo</span>
          </label>
        </div>
      </div>
    </div>

    <div class="flex items-center justify-end gap-3">
      <Link :href="route('inventario.productos.index')" class="px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50">Cancelar</Link>
      <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 disabled:opacity-50">
        {{ producto ? 'Actualizar Producto' : 'Guardar Producto' }}
      </button>
    </div>
  </form>
</template>