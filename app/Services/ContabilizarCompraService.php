<?php
namespace App\Services;

use App\Models\{Compra, LibroCompra, Asiento, DetalleAsiento, Cuentas};
use App\Helpers\AsientoHelper;
use Illuminate\Support\Facades\{DB, Auth};

class ContabilizarCompraService
{
    /** Contabiliza una compra con inventario y sincroniza el libro. */
    public function contabilizarCompra(Compra $compra): Asiento
    {
        return DB::transaction(function () use ($compra) {
            $compra = Compra::lockForUpdate()->findOrFail($compra->id);
            $compra->load('detalles.producto', 'proveedor', 'almacen', 'libroCompra');
            $libro = $compra->libroCompra ?? LibroCompra::where('compra_id', $compra->id)->first();

            if ($compra->asiento_id || $libro?->asiento_id) {
                throw new \DomainException('Esta compra ya está contabilizada.');
            }
            if ($compra->estado !== 'confirmada') {
                throw new \DomainException('Solo se contabilizan compras confirmadas.');
            }

            // ── Resolver cuentas con prioridad ──
            $cuentaIvaCf = $compra->cuenta_iva_cf_id
                ?? $libro?->cuenta_iva_cf_id
                ?? Cuentas::where('nombre', 'IVA CRÉDITO FISCAL')->where('permite_movimiento', true)->value('id');

            $cuentaHaber = ($compra->forma_pago === 'credito')
                ? $compra->proveedor?->cuenta_por_pagar_id
                : ($compra->cuenta_pago_id ?? $libro?->cuenta_pago_id);

            if (!$cuentaHaber) {
                throw new \DomainException($compra->forma_pago === 'credito'
                    ? 'El proveedor no tiene cuenta por pagar asignada.'
                    : 'Falta la cuenta de pago.');
            }

            // ── DEBE: mercadería agrupada por cuenta (líneas ya netas) ──
            $debe = [];
            foreach ($compra->detalles as $det) {
                $cuenta = $det->producto->cuenta_activo_id
                    ?? $compra->almacen?->cuenta_inventario_id;
                if (!$cuenta) {
                    throw new \DomainException("'{$det->producto->descripcion}' no tiene cuenta de inventario.");
                }
                $debe[$cuenta] = ($debe[$cuenta] ?? 0) + (float) $det->subtotal;
            }

            $iva   = (float) $compra->iva_cf;
            $total = round(array_sum($debe) + $iva, 2);

            if ($iva > 0 && !$cuentaIvaCf) {
                throw new \DomainException('Falta la cuenta de IVA Crédito Fiscal.');
            }

            $asiento = Asiento::create([
                'periodo_id'     => $compra->periodo_id,
                'fecha'          => $compra->fecha_recepcion,
                'numero_asiento' => AsientoHelper::generarNumeroAsiento($compra->periodo_id, 'EGR'),
                'tipo'           => 'EGR',
                'glosa'          => 'Compra ' . ($compra->nro_factura ?? $compra->nro_compra) . ' - ' . $compra->proveedor->razon_social,
                'comprobante'    => $compra->nro_factura ?? $compra->nro_compra,
                'creado_por'     => Auth::id(),
            ]);

            foreach ($debe as $cuentaId => $monto) {
                DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaId,
                    'debe' => round($monto, 4), 'haber' => 0, 'descripcion' => 'Ingreso a inventario']);
            }
            if ($iva > 0) {
                DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaIvaCf,
                    'debe' => round($iva, 4), 'haber' => 0, 'descripcion' => 'Crédito fiscal IVA']);
            }
            DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaHaber,
                'debe' => 0, 'haber' => $total,
                'descripcion' => $compra->forma_pago === 'credito' ? 'CxP compra' : 'Pago compra']);

            // Validar balance
            $asiento->load('detalles');
            if (round($asiento->detalles->sum('debe'), 2) !== round($asiento->detalles->sum('haber'), 2)) {
                throw new \DomainException('Asiento desbalanceado.');
            }

            // ── Sincronizar AMBOS lados ──
            $compra->update(['asiento_id' => $asiento->id, 'estado' => 'contabilizada']);
            $libro?->update([
                'asiento_id'        => $asiento->id,
                'estado'            => 'contabilizado',
                'cuenta_iva_cf_id'  => $cuentaIvaCf,
                'cuenta_pago_id'    => $compra->cuenta_pago_id ?? $libro->cuenta_pago_id,
                'contabilizado_por' => Auth::id(),
                'contabilizado_at'  => now(),
            ]);

            return $asiento;
        });
    }
}
