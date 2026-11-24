<?php

namespace App\Services;

use App\Models\{LibroVenta, Venta, DetalleVenta, PagoVenta, ProveedorCliente, Almacen, Producto};
use Illuminate\Support\Facades\DB;

class IngresarLibroVentaAInventarioService
{
    public function __construct(
        private InventarioService $inventario,
        private CuentaCorrienteService $cuentaCorriente,
    ) {}

    /**
     * @param array $lineas [['producto_id'=>1,'cantidad'=>2,'precio_unitario'=>50,'descuento'=>0], ...]
     *   precio_unitario = precio CON IVA (como viene en la factura)
     */
    public function ingresar(
        LibroVenta $libro,
        int $almacenId,
        array $lineas,
        int $userId,
        ?string $formaPago = null,
        ?int $diasCredito = null,
        ?int $cuentaCobroId = null,
        ?int $cuentaIvaDfId = null
    ): Venta {
        return DB::transaction(function () use (
            $libro, $almacenId, $lineas, $userId, $formaPago, $diasCredito, $cuentaCobroId, $cuentaIvaDfId
        ) {
            $libro = LibroVenta::lockForUpdate()->findOrFail($libro->id);

            if ($libro->estaIngresadoAInventario()) {
                throw new \DomainException('Esta factura ya fue ingresada a inventario.');
            }
            if ($libro->asiento_id) {
                throw new \DomainException('La factura ya está contabilizada. Anula el asiento antes de enviarla a inventario.');
            }
            if (strtolower($libro->estado_factura ?? '') === 'anulada') {
                throw new \DomainException('La factura está anulada.');
            }
            if (empty($lineas)) {
                throw new \DomainException('Debes indicar al menos un producto.');
            }

            // Las líneas (con IVA, menos descuento) deben sumar lo que se cobró en la factura
            $esperado = round((float) $libro->importe_total - (float) ($libro->descuento ?? 0), 4);
            $sumaLineas = round(collect($lineas)->sum(
                fn($l) => (float) $l['cantidad'] * (float) $l['precio_unitario'] - (float) ($l['descuento'] ?? 0)
            ), 4);
            if (abs($esperado - $sumaLineas) > 0.05) {
                throw new \DomainException("Las líneas suman $sumaLineas y la factura $esperado (total - descuento).");
            }

            $almacen = Almacen::findOrFail($almacenId);   // la cuenta del almacén es solo respaldo; la valida el contabilizador

            $empresaId = session('empresa_id') ?? ($libro->periodo->empresa_id ?? null);
            if (!$empresaId) {
                throw new \DomainException('No se pudo determinar la empresa activa.');
            }

            $cliente = ProveedorCliente::firstOrCreate(
                ['empresa_id' => $empresaId, 'nit' => $libro->nit_cliente],
                ['razon_social' => $libro->razon_social_cliente, 'es_cliente' => true]
            );
            if (!$cliente->es_cliente) {
                $cliente->update(['es_cliente' => true]);
            }

            $formaPagoFinal   = $formaPago ?: ($libro->forma_pago ?: 'contado');
            $diasCreditoFinal = $diasCredito ?? ($libro->dias_credito ?? 0);
            $esCredito        = $formaPagoFinal === 'credito';

            $cuentaCobroFinal = $cuentaCobroId ?? $libro->cuenta_cobro_id;
            $cuentaIvaFinal   = $cuentaIvaDfId ?? $libro->cuenta_iva_df_id;

            $venta = Venta::where('libro_venta_id', $libro->id)->first()
                  ?? ($libro->venta_id ? Venta::find($libro->venta_id) : null);

            if (!$venta) {
                $venta = Venta::create([
                    'empresa_id'              => $empresaId,
                    'nro_venta'               => Venta::siguienteNumero($libro->periodo_id, $empresaId),
                    'periodo_id'              => $libro->periodo_id,
                    'almacen_id'              => $almacenId,
                    'proveedor_cliente_id'    => $cliente->id,
                    'nit_cliente'             => $libro->nit_cliente,
                    'razon_social_cliente'    => $libro->razon_social_cliente,
                    'fecha'                   => $libro->fecha_factura,
                    'hora'                    => now()->format('H:i:s'),
                    'tipo'                    => 'venta',
                    'forma_pago'              => $formaPagoFinal,
                    'dias_credito'            => $diasCreditoFinal,
                    'fecha_vencimiento_cobro' => $esCredito && $diasCreditoFinal > 0
                        ? \Carbon\Carbon::parse($libro->fecha_factura)->addDays($diasCreditoFinal)->toDateString()
                        : null,
                    'con_factura'             => true,
                    'modalidad_factura'       => 'offline',
                    'nro_factura'             => $libro->nro_factura,
                    'codigo_autorizacion'     => $libro->cod_autorizacion,
                    'cuenta_cobro_id'         => $cuentaCobroFinal,
                    'cuenta_iva_df_id'        => $cuentaIvaFinal,
                    'estado'                  => 'confirmada',
                    'estado_siat'             => 'pendiente',
                    'estado_contable'         => 'pendiente',
                    'creado_por'              => $userId,
                    'libro_venta_id'          => $libro->id,
                ]);
            }

            foreach ($lineas as $l) {
                $prod     = Producto::findOrFail($l['producto_id']);
                $cantidad = (float) $l['cantidad'];
                $precio   = (float) $l['precio_unitario'];
                $desc     = (float) ($l['descuento'] ?? 0);

                $detalle = DetalleVenta::create([
                    'venta_id'        => $venta->id,
                    'producto_id'     => $prod->id,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento'       => $desc,
                ]);

                // Servicios (no controlan stock) no mueven kardex
                if (!$prod->controla_stock) continue;

                // Costo según método del producto (PROM / PEPS / ESP); valida stock internamente
                $resultado = $this->inventario->registrarSalida(
                    tipo:       'salida_venta',
                    productoId: $prod->id,
                    almacenId:  $almacenId,
                    cantidad:   $cantidad,
                    periodoId:  $libro->periodo_id,
                    extra: [
                        'precio_venta'         => $precio * $cantidad - $desc,
                        'referencia_tipo'      => 'ventas',
                        'referencia_id'        => $venta->id,
                        'fecha'                => $libro->fecha_factura,
                        'proveedor_cliente_id' => $cliente->id,
                        'motivo'               => 'Venta Fact. ' . $libro->nro_factura,
                    ]
                );

                $detalle->update([
                    'costo_unitario' => $resultado['costo_unitario'],
                    'costo_total'    => $resultado['costo_total'],
                    'movimiento_id'  => $resultado['movimiento']->id,
                ]);
            }

            // Totales (neto/IVA por línea) y luego utilidad con el neto
            $venta->recalcularTotales();
            foreach ($venta->detalles as $det) {
                $det->update(['utilidad' => round((float) $det->subtotal - (float) $det->costo_total, 4)]);
            }

            // Cobro: contado → un pago por el total; crédito → saldo a cuenta corriente
            if ($esCredito) {
                $this->cuentaCorriente->registrarCargo(
                    $cliente,
                    (float) $venta->total,
                    'Venta Fact. ' . $libro->nro_factura,
                    [
                        'fecha'             => $libro->fecha_factura,
                        'fecha_vencimiento' => $venta->fecha_vencimiento_cobro,
                        'documento_tipo'    => 'factura',
                        'documento_numero'  => $libro->nro_factura,
                        'referencia_tipo'   => 'ventas',
                        'referencia_id'     => $venta->id,
                        'creado_por'        => $userId,
                    ]
                );
            } else {
                PagoVenta::create([
                    'venta_id'  => $venta->id,
                    'medio'     => 'efectivo',
                    'monto'     => $venta->total,
                    'fecha'     => $libro->fecha_factura,
                    'cuenta_id' => $cuentaCobroFinal,
                ]);
            }

            // Libro: se completan cuentas vacías, no se pisan las del contador
            $libro->update(array_filter([
                'venta_id'                 => $venta->id,
                'va_a_inventario'          => true,
                'almacen_id'               => $almacenId,
                'estado_inventario'        => 'ingresado',
                'ingresado_inventario_at'  => now(),
                'ingresado_inventario_por' => $userId,
                'cuenta_cobro_id'          => $cuentaCobroFinal,
                'cuenta_iva_df_id'         => $cuentaIvaFinal,
                'cuenta_ingreso_id'        => $libro->cuenta_ingreso_id
                    ?: $venta->detalles->pluck('producto.cuenta_ingreso_id')->filter()->first(),
            ], fn($v) => $v !== null));

            if (!$venta->libro_venta_id) {
                $venta->update(['libro_venta_id' => $libro->id]);
            }

            return $venta;
        });
    }
}