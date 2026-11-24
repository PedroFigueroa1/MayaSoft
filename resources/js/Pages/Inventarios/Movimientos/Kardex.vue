<template>
  <AppLayoutAdmin>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-xl font-semibold text-gray-800 leading-tight">Kardex de inventario</h2>
          <p class="text-xs text-gray-500 mt-0.5">{{ periodo.nombre }}</p>
        </div>
        <div class="flex gap-2">
          <Link :href="route('inventario.reportes.fisico', periodo.id)"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-500 text-white text-xs font-semibold rounded-lg hover:bg-amber-600 transition">
            Físico valorado
          </Link>
          <Link v-if="filtros.producto_id"
            :href="route('inventario.reportes.kardex.producto', [periodo.id, filtros.producto_id])"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">
            Kardex del producto
          </Link>
          <Link :href="route('inventario.movimientos.entrada.create', periodo.id)"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva entrada
          </Link>
          <Link :href="route('inventario.movimientos.salida.create', periodo.id)"
            class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
            Nueva salida
          </Link>
        </div>
      </div>
    </template>

    <div class=" max-w-full mx-auto sm:px-6  space-y-4">

      <!-- Flash -->
      <div v-if="$page.props.flash?.success"
        class="flex items-center gap-2 p-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
        <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
        </svg>
        {{ $page.props.flash.success }}
      </div>

      <!-- Resumen del período -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div class="bg-white rounded-xl border p-4">
          <div class="text-xs text-gray-500 mb-1">Total movimientos</div>
          <div class="text-2xl font-bold text-gray-800">{{ resumen?.total_movimientos ?? 0 }}</div>
        </div>
        <div class="bg-green-50 rounded-xl border border-green-200 p-4">
          <div class="text-xs text-green-700 mb-1">Total entradas (costo)</div>
          <div class="text-xl font-bold text-green-800">{{ fmt(resumen?.total_entradas) }}</div>
        </div>
        <div class="bg-red-50 rounded-xl border border-red-200 p-4">
          <div class="text-xs text-red-700 mb-1">Total salidas (costo)</div>
          <div class="text-xl font-bold text-red-800">{{ fmt(resumen?.total_salidas) }}</div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-white rounded-xl border p-3 flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-44">
          <label class="block text-xs font-medium text-gray-600 mb-1">Producto</label>
          <select v-model="filtros.producto_id" @change="aplicar"
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos los productos</option>
            <option v-for="p in productos" :key="p.id" :value="p.id">
              {{ p.codigo }} — {{ p.descripcion }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Almacén</label>
          <select v-model="filtros.almacen_id" @change="aplicar"
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option value="">Todos</option>
            <option v-for="a in almacenes" :key="a.id" :value="a.id">{{ a.nombre }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Tipo</label>
          <select v-model="filtros.tipo" @change="aplicar"
            class="w-full border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
            <option v-for="t in tiposOpciones" :key="t.value" :value="t.value">{{ t.label }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Desde</label>
          <input type="date" v-model="filtros.desde" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1">Hasta</label>
          <input type="date" v-model="filtros.hasta" @change="aplicar"
            class="border border-gray-200 rounded-lg text-sm px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400"/>
        </div>
        <button @click="limpiar" v-if="hayFiltros"
          class="px-3 py-1.5 text-sm text-gray-500 hover:text-gray-700 transition">
          Limpiar
        </button>
      </div>

        <!-- Tabla kardex -->
        <div class="bg-white rounded-xl border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm whitespace-nowrap">
            <thead>
                <tr class="bg-gray-50 border-b text-xs text-gray-600">
                <th class="px-3 py-2 text-left">Fecha</th>
                <th class="px-3 py-2 text-left">Operación</th>
                <th class="px-3 py-2 text-left">Comprobante / Ref</th>
                <th class="px-3 py-2 text-left">Código</th>
                <th class="px-3 py-2 text-left">Producto</th>
                <!-- Columnas de tu Excel -->
                <th class="px-3 py-2 text-right">P. Compra</th>
                <th class="px-3 py-2 text-right">Inv. Ini. Fis</th>
                <th class="px-3 py-2 text-right">Entrada F</th>
                <th class="px-3 py-2 text-right">Salida F</th>
                <th class="px-3 py-2 text-right">Saldo F</th>
                <th class="px-3 py-2 text-right">Costo Unit</th>
                <th class="px-3 py-2 text-right">Inv. Ini. Val</th>
                <th class="px-3 py-2 text-right">Entrada V</th>
                <th class="px-3 py-2 text-right">Salida V</th>
                <th class="px-3 py-2 text-right">Saldo V</th>
                <th class="px-3 py-2 text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-if="!movimientos.data.length">
                <td colspan="16" class="px-6 py-12 text-center text-gray-400">
                    No hay movimientos para mostrar.
                </td>
                </tr>
                
                <tr v-for="(m, index) in movimientos.data" :key="m.id"
                :class="['hover:bg-gray-50 transition-colors', m.estado === 'anulado' ? 'opacity-40 line-through bg-red-50' : '']">

                <!-- Fecha -->
                <td class="px-3 py-2 text-xs">{{ fmtFecha(m.fecha) }}</td>

                <!-- Operación -->
                <td class="px-3 py-2 text-xs font-medium">{{ tipoLabel(m.tipo) }}</td>

                <!-- Comprobante / Motivo -->
                <td class="px-3 py-2 text-xs text-gray-500">{{ m.motivo || m.referencia_id || '—' }}</td>

                <!-- Código -->
                <td class="px-3 py-2 text-xs font-mono text-gray-500">{{ m.producto?.codigo }}</td>

                <!-- Producto -->
                <td class="px-3 py-2 text-xs">{{ m.producto?.descripcion }}</td>

                <!-- P. Compra (Costo unitario original de entrada) -->
                <td class="px-3 py-2 text-right text-xs">
                    {{ esEntrada(m.tipo) ? fmt(m.costo_unitario) : '' }}
                </td>

                <!-- Inv. Ini. Fis (Solo si es la primera fila del período para ese producto) -->
                <td class="px-3 py-2 text-right text-xs font-semibold">
                    {{ index === 0 && m.tipo === 'entrada_manual' ? fmtCant(m.cantidad) : '' }}
                </td>

                <!-- Entrada F -->
                <td class="px-3 py-2 text-right text-xs text-green-700 font-semibold">
                    {{ esEntrada(m.tipo) ? fmtCant(m.cantidad) : '' }}
                </td>

                <!-- Salida F -->
                <td class="px-3 py-2 text-right text-xs text-red-600 font-semibold">
                    {{ !esEntrada(m.tipo) ? fmtCant(m.cantidad) : '' }}
                </td>

                <!-- Saldo F (Trae el stock resultante histórico del backend) -->
                <td class="px-3 py-2 text-right text-xs font-bold text-gray-800">
                    {{ fmtCant(m.stock_resultante) }}
                </td>

                <!-- Costo Unitario -->
                <td class="px-3 py-2 text-right text-xs">
                    {{ fmt(m.costo_unitario) }}
                </td>

                <!-- Inv. Ini. Val (Valor del inventario inicial) -->
                <td class="px-3 py-2 text-right text-xs font-semibold">
                    {{ index === 0 && m.tipo === 'entrada_manual' ? fmt(m.costo_total) : '' }}
                </td>

                <!-- Entrada V -->
                <td class="px-3 py-2 text-right text-xs text-green-700">
                    {{ esEntrada(m.tipo) ? fmt(m.costo_total) : '' }}
                </td>

                <!-- Salida V -->
                <td class="px-3 py-2 text-right text-xs text-red-600">
                    {{ !esEntrada(m.tipo) ? fmt(m.costo_total) : '' }}
                </td>

                <!-- Saldo V (Saldo valorado histórico) -->
                <td class="px-3 py-2 text-right text-xs font-bold text-gray-800">
                    {{ fmt(m.saldo_valorado || (m.stock_resultante * m.costo_unitario)) }}
                </td>

                <!-- Estado / Acciones -->
                <td class="px-3 py-2 text-center">
                    <span v-if="m.estado === 'activo'" class="text-[10px] bg-green-100 text-green-700 px-2 py-0.5 rounded-full">OK</span>
                    <span v-else class="text-[10px] bg-red-100 text-red-700 px-2 py-0.5 rounded-full">Anulado</span>
                </td>
                </tr>
            </tbody>
            </table>
        </div>
        
        <!-- Paginación (se mantiene igual) -->
        <div v-if="movimientos.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t bg-gray-50">
            <!-- ... tu paginación actual ... -->
        </div>
        </div>

    </div>
  </AppLayoutAdmin>
</template>

<script>
import AppLayoutAdmin from '@/Layouts/AppLayoutAdmin.vue'
import { Link, router } from '@inertiajs/vue3'

const ENTRADAS = [
  'entrada_compra','entrada_devolucion','entrada_produccion',
  'entrada_manual','entrada_traspaso','ajuste_positivo',
]

export default {
  components: { AppLayoutAdmin, Link },
  props: {
    periodo:      { type: Object, required: true },
    movimientos:  { type: Object, required: true },
    resumen:      { type: Object, default: () => ({}) },
    productos:    { type: Array, default: () => [] },
    almacenes:    { type: Array, default: () => [] },
    tiposOpciones:{ type: Array, default: () => [] },
    filters:      { type: Object, default: () => ({}) },
  },
  data() {
    return {
      filtros: {
        producto_id: this.filters.producto_id || '',
        almacen_id:  this.filters.almacen_id  || '',
        tipo:        this.filters.tipo         || '',
        desde:       this.filters.desde        || '',
        hasta:       this.filters.hasta        || '',
      },
    }
  },
  computed: {
    hayFiltros() {
      return Object.values(this.filtros).some(v => v !== '')
    },
  },
  methods: {
    aplicar() {
      router.get(route('inventario.movimientos.kardex', this.periodo.id), this.filtros, {
        preserveState: true, replace: true,
      })
    },
    limpiar() {
      this.filtros = { producto_id:'', almacen_id:'', tipo:'', desde:'', hasta:'' }
      this.aplicar()
    },
    esEntrada(tipo) { return ENTRADAS.includes(tipo) },
    anular(m) {
      const motivo = prompt(`¿Anular este movimiento?\n${m.tipo} — ${m.producto?.descripcion}\n\nMotivo de anulación:`)
      if (!motivo) return
      router.post(route('inventario.movimientos.anular', m.id), { motivo })
    },
    // Solo se pueden anular movimientos manuales o de ajuste desde aquí
    // Los generados por ventas/compras se anulan desde su origen
    puedeAnular(tipo) {
      return ['entrada_manual','salida_consumo','salida_merma',
              'salida_devolucion','ajuste_positivo','ajuste_negativo'].includes(tipo)
    },
    fmt(v) {
      return new Intl.NumberFormat('es-BO', {
        style: 'currency', currency: 'BOB', minimumFractionDigits: 2,
      }).format(v ?? 0)
    },
    fmtCant(v) {
      return new Intl.NumberFormat('es-BO', {
        minimumFractionDigits: 0, maximumFractionDigits: 4,
      }).format(v ?? 0)
    },
    fmtFecha(f) {
    if (!f) return '—'

    // Si ya es un Date, úsalo directo
    let d = f instanceof Date ? f : null

    if (!d) {
        // Normaliza: si es string con "YYYY-MM-DD" o ISO completo
        const s = String(f).trim()

        // Caso "YYYY-MM-DD" puro → forzamos medianoche local
        if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const [y, m, day] = s.split('-').map(Number)
        d = new Date(y, m - 1, day)
        } else {
        // ISO con hora, o "YYYY-MM-DD HH:mm:ss" → Date lo interpreta
        d = new Date(s.replace(' ', 'T'))
        }
    }

    if (isNaN(d.getTime())) return String(f)

    return d.toLocaleDateString('es-BO', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    })
    },
    tipoLabel(t) {
      return {
        entrada_compra:     'Entrada compra',
        entrada_manual:     'Entrada manual',
        entrada_devolucion: 'Dev. cliente',
        entrada_produccion: 'Producción',
        entrada_traspaso:   'Traspaso in',
        salida_venta:       'Venta',
        salida_consumo:     'Consumo',
        salida_merma:       'Merma',
        salida_devolucion:  'Dev. proveedor',
        salida_produccion:  'Prod. materia',
        salida_traspaso:    'Traspaso out',
        ajuste_positivo:    'Ajuste +',
        ajuste_negativo:    'Ajuste −',
      }[t] ?? t
    },
    tipoBadge(t) {
      if (ENTRADAS.includes(t)) return 'bg-green-100 text-green-800'
      if (t === 'salida_venta')  return 'bg-blue-100 text-blue-800'
      if (t === 'salida_merma')  return 'bg-red-100 text-red-800'
      if (t.startsWith('salida'))return 'bg-orange-100 text-orange-800'
      return 'bg-gray-100 text-gray-700'
    },
  },
}
</script>
