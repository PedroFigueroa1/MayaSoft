<template>
  <div class="pagina-wrap">

    <!-- Botones -->
    <div class="botones no-print">
      <button @click="imprimir" class="btn btn-azul">🖨️ Imprimir / PDF</button>
      <button @click="descargarPdf" class="btn btn-verde">⬇️ Descargar PDF</button>
    </div>

    <!-- FACTURA -->
    <div ref="factura" class="hoja">

      <!-- ENCABEZADO -->
      <div class="encabezado">
        <!-- Emisor (izquierda, centrado) -->
        <div class="emisor">
          <div class="emisor-nombre">{{ empresa?.nombre || 'NESTOR JULIO ENRIQUEZ QUIROGA' }}</div>
          <div class="emisor-sucursal">{{ empresa?.sucursal || 'SUCURSAL N. 1' }}</div>
          <div>No. Punto de Venta {{ empresa?.punto_venta || '0' }}</div>
          <div class="emisor-dir">{{ empresa?.direccion || 'ZONA: LAS CUADRAS , AVENIDA: HEROÍNAS, TIPO DE ESTABLECIMIENTO: EDIFICIO TORRE HEROINAS, NRO.: 1064, PISO: 2, NRO DPTO/LOCAL/OF/PUESTO: 2, TELEFONO: 77990202-4242427' }}</div>
          <div class="emisor-tel">Teléfono: {{ empresa?.telefono || '-' }}</div>
          <div>{{ empresa?.ciudad || 'COCHABAMBA' }}</div>
        </div>

        <!-- Datos fiscales (derecha, sin bordes) -->
        <div class="fiscal">
          <div class="fiscal-fila">
            <span class="fiscal-label">NIT</span>
            <span class="fiscal-valor">{{ empresa?.nit || '3608711019' }}</span>
          </div>
          <div class="fiscal-fila">
            <span class="fiscal-label">FACTURA N°</span>
            <span class="fiscal-valor">{{ venta.nro_factura || venta.id || '1' }}</span>
          </div>
          <div class="fiscal-fila">
            <span class="fiscal-label">CÓD. AUTORIZACIÓN</span>
            <span class="fiscal-valor cuf">{{ venta.cuf || venta.codigo_autorizacion || '—' }}</span>
          </div>
        </div>
      </div>

      <!-- TÍTULO -->
      <div class="titulo">
        <div class="titulo-main">FACTURA</div>
        <div class="titulo-sub">(Con Derecho a Crédito Fiscal)</div>
      </div>

      <!-- CLIENTE (sin bordes) -->
      <div class="cliente">
        <div class="cliente-fila">
          <div class="cl-izq"><strong>Fecha:</strong><span class="cl-val">{{ fmtDatetime }}</span></div>
          <div class="cl-der"><strong>NIT/CI/CEX:</strong><span class="cl-val">{{ nitCliente }} {{ venta.complemento_cliente }}</span></div>
        </div>
        <div class="cliente-fila">
          <div class="cl-izq"><strong>Nombre/Razón Social:</strong><span class="cl-val">{{ venta.razon_social_cliente }}</span></div>
          <div class="cl-der"><strong>Cod. Cliente:</strong><span class="cl-val">{{ venta.cliente?.codigo || nitCliente }}</span></div>
        </div>
      </div>

      <!-- DETALLE -->
      <table class="detalle">
        <thead>
          <tr>
            <th style="width:14%">CÓDIGO<br>PRODUCTO /<br>SERVICIO</th>
            <th style="width:10%">CANTIDAD</th>
            <th style="width:12%">UNIDAD DE<br>MEDIDA</th>
            <th style="width:30%">DESCRIPCIÓN</th>
            <th style="width:12%">PRECIO<br>UNITARIO</th>
            <th style="width:11%">DESCUENTO</th>
            <th style="width:11%">SUBTOTAL</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(det, i) in filasDetalle" :key="det.id || i">
            <td class="tl">{{ det.producto?.codigo_sin || det.producto?.codigo || det.codigo || '' }}</td>
            <td class="tr">{{ det.cantidad != null ? fmt(det.cantidad) : '' }}</td>
            <td class="tl">{{ det.producto?.unidad_medida?.nombre || det.unidad || '' }}</td>
            <td class="tl">{{ det.producto?.descripcion_sin || det.producto?.descripcion || det.descripcion || '' }}</td>
            <td class="tr">{{ det.precio_unitario != null ? fmt(det.precio_unitario) : '' }}</td>
            <td class="tr">{{ det.cantidad != null ? fmt(det.descuento || 0) : '' }}</td>
            <td class="tr">{{ det.cantidad != null ? fmt(subtotalDet(det)) : '' }}</td>
          </tr>
        </tbody>
      </table>

      <!-- TOTALES -->
      <div class="totales-wrap">
        <div class="son"><strong>Son: {{ literal }}</strong></div>
        <table class="totales">
          <tr><td class="lbl">SUBTOTAL Bs</td><td class="val">{{ fmt(subtotal) }}</td></tr>
          <tr><td class="lbl">DESCUENTO Bs</td><td class="val">{{ fmt(descuentoTotal) }}</td></tr>
          <tr><td class="lbl">TOTAL Bs</td><td class="val">{{ fmt(total) }}</td></tr>
          <tr><td class="lbl">MONTO GIFT CARD Bs</td><td class="val">{{ fmt(venta.monto_gift_card || 0) }}</td></tr>
          <tr class="resaltado"><td class="lbl"><strong>MONTO A PAGAR Bs</strong></td><td class="val"><strong>{{ fmt(total - (parseFloat(venta.monto_gift_card) || 0)) }}</strong></td></tr>
          <tr class="resaltado"><td class="lbl chico"><strong>IMPORTE BASE CRÉDITO FISCAL Bs</strong></td><td class="val"><strong>{{ fmt(total - (parseFloat(venta.monto_gift_card) || 0)) }}</strong></td></tr>
        </table>
      </div>

      <!-- LEYENDAS + QR -->
      <div class="pie">
        <div class="leyendas">
          <p>ESTA FACTURA CONTRIBUYE AL DESARROLLO DEL PAÍS, EL USO ILÍCITO SERÁ SANCIONADO PENALMENTE DE ACUERDO A LEY</p>
          <p>Ley N° 453: El proveedor debe brindar atención sin discriminación, con respeto, calidez y cordialidad a los usuarios y consumidores.</p>
          <p>“Este documento es la Representación Gráfica de un Documento Fiscal Digital emitido en una modalidad de facturación {{ venta.modalidad_factura === 'offline' ? 'fuera de línea' : 'en línea' }}”</p>
        </div>
        <div class="qr">
          <img v-if="qr" :src="qr" alt="QR">
          <div v-else class="qr-vacio">QR no disponible</div>
        </div>
      </div>

      <div class="paginacion">1/1</div>
    </div>
  </div>
</template>

<script>
import jsPDF from 'jspdf'
import html2canvas from 'html2canvas'

const UNIDADES = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
  'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve',
  'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve']
const DECENAS = ['', '', '', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa']
const CENTENAS = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos']

function menorMil(n) {
  if (n === 0) return ''
  if (n === 100) return 'cien'
  const c = Math.floor(n / 100), r = n % 100
  let s = c ? CENTENAS[c] : ''
  if (r) {
    if (s) s += ' '
    if (r < 30) s += UNIDADES[r]
    else {
      const d = Math.floor(r / 10), u = r % 10
      s += DECENAS[d] + (u ? ' y ' + UNIDADES[u] : '')
    }
  }
  return s
}

function numeroALetras(n) {
  if (n === 0) return 'cero'
  const millones = Math.floor(n / 1000000)
  const miles = Math.floor((n % 1000000) / 1000)
  const resto = n % 1000
  let s = ''
  if (millones) s += millones === 1 ? 'un millón' : menorMil(millones).replace(/uno$/, 'un') + ' millones'
  if (miles) s += (s ? ' ' : '') + (miles === 1 ? 'mil' : menorMil(miles).replace(/uno$/, 'un') + ' mil')
  if (resto) s += (s ? ' ' : '') + menorMil(resto)
  return s
}

export default {
  name: 'FacturaTemplate',
  props: {
    venta:    { type: Object, required: true },
    empresa:  { type: Object, default: null },
    qr:       { type: String, default: null },
    minFilas: { type: Number, default: 1 },
  },
  computed: {
    filasDetalle() {
      const filas = (this.venta.detalles || []).slice()
      while (filas.length < this.minFilas) filas.push({})
      return filas
    },
    subtotal() {
      return (this.venta.detalles || []).reduce((s, d) => s + this.subtotalDet(d), 0)
    },
    descuentoTotal() {
      const global = parseFloat(this.venta.descuento) || 0
      return global
    },
    total() {
      return this.subtotal - this.descuentoTotal
    },
    nitCliente() {
      return this.venta.nit_cliente || this.venta.ci_cliente || '0'
    },
    literal() {
      if (this.venta.monto_literal) return this.venta.monto_literal
      const monto = this.total - (parseFloat(this.venta.monto_gift_card) || 0)
      const entero = Math.floor(monto + 1e-9)
      const cent = Math.round((monto - entero) * 100)
      const txt = numeroALetras(entero)
      return `${txt.charAt(0).toUpperCase()}${txt.slice(1)} ${String(cent).padStart(2, '0')}/100 Bolivianos`
    },
    fmtDatetime() {
      const f = this.venta.fecha_emision_factura || this.venta.fecha
      if (!f) return '—'
      let d
      if (typeof f === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(f.slice(0, 10)) && f.length <= 10) {
        d = new Date(`${f}T${this.venta.hora || '00:00:00'}`)
      } else {
        d = new Date(f)
      }
      if (isNaN(d)) return String(f)
      const p = (x) => String(x).padStart(2, '0')
      let h = d.getHours()
      const ampm = h >= 12 ? 'PM' : 'AM'
      h = h % 12 || 12
      return `${p(d.getDate())}/${p(d.getMonth() + 1)}/${d.getFullYear()} ${p(h)}:${p(d.getMinutes())} ${ampm}`
    },
  },
  methods: {
    subtotalDet(d) {
      if (d.subtotal != null) return parseFloat(d.subtotal) || 0
      if (d.total != null) return parseFloat(d.total) || 0
      return (parseFloat(d.cantidad) || 0) * (parseFloat(d.precio_unitario) || 0) - (parseFloat(d.descuento) || 0)
    },
    imprimir() {
      window.print()
    },
    async descargarPdf() {
      const el = this.$refs.factura
      const canvas = await html2canvas(el, { scale: 3, useCORS: true, backgroundColor: '#ffffff' })
      const img = canvas.toDataURL('image/png')
      const pdf = new jsPDF('p', 'mm', 'letter')
      const w = pdf.internal.pageSize.getWidth()
      const h = (canvas.height * w) / canvas.width
      pdf.addImage(img, 'PNG', 0, 0, w, Math.min(h, pdf.internal.pageSize.getHeight()))
      pdf.save(`factura-${this.venta.nro_factura || this.venta.id}.pdf`)
    },
    fmt(v) {
      return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2, maximumFractionDigits: 2,
      }).format(parseFloat(v) || 0)
    },
  },
}
</script>

<style scoped>
.pagina-wrap {
  min-height: 100vh;
  background: #e5e7eb;
  padding: 24px 0;
  font-family: Arial, Helvetica, sans-serif;
  color: #000;
}
.botones { width: 816px; max-width: 100%; margin: 0 auto 12px; display: flex; gap: 8px; }
.btn { padding: 8px 16px; color: #fff; font-size: 14px; font-weight: 500; border-radius: 4px; border: 0; cursor: pointer; }
.btn-azul { background: #2563eb; }
.btn-azul:hover { background: #1d4ed8; }
.btn-verde { background: #16a34a; }
.btn-verde:hover { background: #15803d; }

/* Hoja tamaño carta (8.5in x 11in a 96dpi) */
.hoja {
  position: relative;
  width: 816px;
  min-height: 1056px;
  max-width: 100%;
  margin: 0 auto;
  background: #fff;
  box-shadow: 0 1px 6px rgba(0,0,0,.25);
  padding: 40px 50px 50px;
  font-size: 11px;
  line-height: 1.25;
  box-sizing: border-box;
}

/* Encabezado */
.encabezado { display: flex; justify-content: space-between; align-items: flex-start; }
.emisor { width: 46%; text-align: center; }
.emisor-nombre { font-weight: bold; text-transform: uppercase; }
.emisor-sucursal { font-weight: bold; text-transform: uppercase; }
.emisor-dir { margin-top: 2px; }
.emisor-tel { margin-top: 10px; }

.fiscal { width: 46%; padding-left: 40px; box-sizing: border-box; }
.fiscal-fila { display: flex; gap: 14px; margin-bottom: 1px; }
.fiscal-label { font-weight: bold; width: 125px; flex-shrink: 0; }
.fiscal-valor { flex: 1; word-break: break-all; }
.fiscal-valor.cuf { max-width: 130px; }

/* Título */
.titulo { text-align: center; margin-top: 22px; }
.titulo-main { font-size: 19px; font-weight: bold; }
.titulo-sub { font-size: 11px; margin-top: 2px; }

/* Cliente */
.cliente { margin-top: 28px; }
.cliente-fila { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 12px; }
.cl-izq { width: 66%; display: flex; }
.cl-der { width: 32%; display: flex; justify-content: flex-end; }
.cl-izq strong { width: 150px; flex-shrink: 0; }
.cl-der strong { margin-right: 14px; }
.cl-val { }

/* Detalle */
.detalle { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10.5px; }
.detalle th, .detalle td { border: 1px solid #444; padding: 3px 4px; }
.detalle th { text-align: center; font-weight: bold; font-size: 11px; padding: 5px 4px; }
.detalle td { vertical-align: top; }
.tl { text-align: left; }
.tr { text-align: right; vertical-align: middle !important; }

/* Totales */
.totales-wrap { display: flex; justify-content: space-between; align-items: center; }
.son { width: 50%; padding-left: 12px; font-size: 11px; }
.totales { width: 38%; border-collapse: collapse; font-size: 10px; }
.totales td { border: 1px solid #444; padding: 1px 4px; }
.totales .lbl { text-align: right; }
.totales .lbl.chico { font-size: 8.5px; }
.totales .val { text-align: right; width: 38%; }
.totales .resaltado td { background: #f0f0f0; }

/* Pie */
.pie { display: flex; justify-content: space-between; align-items: flex-start; margin-top: 28px; }
.leyendas { width: 80%; text-align: center; font-size: 9.5px; }
.leyendas p { margin: 0 0 8px; }
.qr { width: 100px; height: 100px; }
.qr img { width: 100px; height: 100px; object-fit: contain; }
.qr-vacio { width: 100px; height: 100px; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; font-size: 9px; color: #999; }

.paginacion { position: absolute; right: 50px; bottom: 30px; font-size: 11px; }
</style>

<style>
@media print {
  @page { margin: 0; size: letter; }
  body { background: #fff !important; }
  .no-print { display: none !important; }
  .pagina-wrap { background: #fff !important; padding: 0 !important; }
  .hoja { box-shadow: none !important; margin: 0 !important; }
}
</style>
