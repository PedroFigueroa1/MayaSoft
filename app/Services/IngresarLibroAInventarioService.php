<?php

namespace App\Services;

use App\Models\{LibroCompra, Compra, DetalleCompra, ProveedorCliente, Almacen};
use Illuminate\Support\Facades\DB;

class IngresarLibroAInventarioService
{
    public function __construct(
        private InventarioService $inventario,
        private CuentaCorrienteService $cuentaCorriente,
    ) {}

    /**
     * @param array $lineas [['producto_id'=>1,'cantidad'=>10,'costo_total'=>500.00], ...]
     *   costo_total = costo NETO (sin IVA) de la línea
     */
    public function ingresar(
        LibroCompra $libro,
        int $almacenId,
        array $lineas,
        int $userId,
        ?string $formaPago = null,
        ?int $diasCredito = null,
        ?int $cuentaIvaCfId = null,
        ?int $cuentaPagoId = null
    ): Compra {
        return DB::transaction(function () use (
            $libro, $almacenId, $lineas, $userId,
            $formaPago, $diasCredito, $cuentaIvaCfId, $cuentaPagoId
        ) {
            $libro = LibroCompra::lockForUpdate()->findOrFail($libro->id);

            if ($libro->estaIngresadoAInventario()) {
                throw new \DomainException('Esta factura ya fue ingresada a inventario.');
            }
            if ($libro->asiento_id) {
                throw new \DomainException('La factura ya está contabilizada como gasto. Anula el asiento antes de enviarla a inventario.');
            }
            if (empty($lineas)) {
                throw new \DomainException('Debes indicar al menos un producto.');
            }

            $baseNeta   = round($libro->importe_total - $libro->cf_iva_contable - $libro->descuento, 4);
            $sumaLineas = round(collect($lineas)->sum('costo_total'), 4);

            if (abs($baseNeta - $sumaLineas) > 0.05) {
                throw new \DomainException("El costo de las líneas ($sumaLineas) no coincide con la base de la factura ($baseNeta).");
            }

            $almacen = Almacen::findOrFail($almacenId);

            // Solo FALLBACK: la cuenta real sale del producto. Ya no bloquea.
            $cuentaInventarioId = $almacen->cuenta_inventario_id ?? $almacen->cuenta_id ?? null;

            $empresaId = session('empresa_id') ?? ($libro->periodo->empresa_id ?? null);
            if (!$empresaId) {
                throw new \DomainException('No se pudo determinar la empresa activa.');
            }

            $nit = trim((string) $libro->nit_proveedor);

            if ($nit === '') {
                throw new \DomainException('El proveedor no tiene un NIT registrado.');
            }

            $proveedor = ProveedorCliente::firstOrNew([
                'empresa_id' => $empresaId,
                'nit' => $nit,
            ]);

            // Si ya existe, conservamos sus datos y activamos su condición de proveedor.
            $proveedor->es_proveedor = true;

            if (empty($proveedor->razon_social)) {
                $proveedor->razon_social = $libro->razon_social;
            }

            if (!$proveedor->exists) {
                $proveedor->codigo = ProveedorCliente::siguienteCodigo($empresaId);
            }

            $proveedor->save();

            $formaPagoFinal   = $formaPago ?: ($libro->forma_pago ?: 'contado');
            $diasCreditoFinal = $diasCredito ?? ($libro->dias_credito ?? 0);

            // Cuentas: lo del formulario manda; si no vino, lo que ya tenía el libro
            $cuentaIvaCfFinal = $cuentaIvaCfId ?? $libro->cuenta_iva_cf_id;
            $cuentaPagoFinal  = $cuentaPagoId  ?? $libro->cuenta_pago_id;

            $compra = Compra::firstOrCreate(
                ['libro_compra_id' => $libro->id],
                [
                    'empresa_id'       => $empresaId,
                    'nro_compra'       => Compra::siguienteNumero($libro->periodo_id),
                    'periodo_id'       => $libro->periodo_id,
                    'proveedor_id'     => $proveedor->id,
                    'almacen_id'       => $almacenId,
                    'nro_factura'      => $libro->nro_factura,
                    'cod_autorizacion' => $libro->cod_autorizacion,
                    'fecha_factura'    => $libro->fecha_factura,
                    'fecha_recepcion'  => $libro->fecha_factura,
                    'subtotal'         => round($libro->importe_total - $libro->cf_iva_contable, 4),
                    'descuento'        => $libro->descuento ?? 0,
                    'iva_cf'           => $libro->cf_iva_contable ?? 0,
                    'total'            => $libro->importe_total,
                    'forma_pago'       => $formaPagoFinal,
                    'dias_credito'     => $diasCreditoFinal,
                    'cuenta_iva_cf_id' => $cuentaIvaCfFinal,   // nuevo
                    'cuenta_pago_id'   => $cuentaPagoFinal,    // nuevo
                    'estado'           => 'confirmada',
                    'observacion'      => 'Generada desde Libro de Compras (SIAT)',
                    'creado_por'       => $userId,
                    'confirmado_por'   => $userId,
                    'confirmado_at'    => now(),
                ]
            );

            foreach ($lineas as $l) {
                $cantidad      = (float) $l['cantidad'];
                $costoTotal    = round((float) $l['costo_total'], 4);
                $costoUnitario = round($costoTotal / $cantidad, 4);

                $detalle = DetalleCompra::create([
                    'compra_id'      => $compra->id,
                    'producto_id'    => $l['producto_id'],
                    'cantidad'       => $cantidad,
                    'costo_unitario' => $costoUnitario,
                    'subtotal'       => $costoTotal,
                ]);

                // Servicio real: actualiza stock_actual, saldo_valorado y lotes PEPS
                $mov = $this->inventario->registrarEntrada(
                    tipo:          'entrada_compra',
                    productoId:    (int) $l['producto_id'],
                    almacenId:     $almacenId,
                    cantidad:      $cantidad,
                    costoUnitario: $costoUnitario,
                    periodoId:     $libro->periodo_id,
                    extra: [
                        'referencia_tipo'      => 'compras',
                        'referencia_id'        => $compra->id,
                        'proveedor_cliente_id' => $proveedor->id,
                        'fecha'                => $libro->fecha_factura,
                        'motivo'               => 'Compra Fact. ' . $libro->nro_factura,
                    ]
                );

                $detalle->update(['movimiento_id' => $mov->id]);
            }

            // NO se toca cuenta_gasto_id con la del almacén; solo se usa de respaldo si estaba vacía
            $libro->update(array_filter([
                'va_a_inventario'          => true,
                'almacen_id'               => $almacenId,
                'cuenta_gasto_id'          => $libro->cuenta_gasto_id ?? $cuentaInventarioId,
                'cuenta_iva_cf_id'         => $cuentaIvaCfFinal,
                'cuenta_pago_id'           => $cuentaPagoFinal,
                'compra_id'                => $compra->id,
                'estado_inventario'        => 'ingresado',
                'ingresado_inventario_at'  => now(),
                'ingresado_inventario_por' => $userId,
                'forma_pago'               => $formaPagoFinal,
                'dias_credito'             => $diasCreditoFinal,
            ], fn ($v) => $v !== null));

            if ($formaPagoFinal === 'credito') {
                $this->cuentaCorriente->registrarCargo(
                    $proveedor,
                    (float) $libro->importe_total,
                    'Compra Fact. ' . $libro->nro_factura,
                    [
                        'fecha'             => $libro->fecha_factura,
                        'fecha_vencimiento' => $libro->fecha_factura->copy()->addDays($diasCreditoFinal),
                        'documento_tipo'    => 'factura_compra',
                        'documento_numero'  => $libro->nro_factura,
                        'referencia_tipo'   => Compra::class,
                        'referencia_id'     => $compra->id,
                        'creado_por'        => $userId,
                    ]
                );
            }

            return $compra;
        });
    }
}
