<?php
namespace App\Services;

use App\Models\{Venta, LibroVenta, Asiento, DetalleAsiento, Cuentas};
use App\Helpers\AsientoHelper;
use Illuminate\Support\Facades\{DB, Auth};

class ContabilizarVentaService
{
    public function contabilizarVenta(Venta $venta): Asiento
    {
        return DB::transaction(function () use ($venta) {
            $venta = Venta::lockForUpdate()->findOrFail($venta->id);
            $venta->load('detalles.producto', 'pagos', 'cliente', 'almacen');
            $libro = LibroVenta::where('venta_id', $venta->id)->first();

            if ($venta->asiento_id || $libro?->asiento_id) {
                throw new \DomainException('Esta venta ya está contabilizada.');
            }
            if (!$venta->estaConfirmada()) {
                throw new \DomainException('Solo se contabilizan ventas confirmadas.');
            }

            $total = round((float) $venta->total, 2);
            $iva   = round((float) $venta->iva_df, 2);
            $it    = round($total * 0.03, 2);

            $debe = [];
            $haber = [];
            $add = function (array &$arr, int $cuenta, float $monto) {
                $arr[$cuenta] = ($arr[$cuenta] ?? 0) + $monto;
            };

            // ── Ingresos, CMV e inventario por producto ──
            $sumaIngresos = 0.0;
            $primerIngreso = null;

            foreach ($venta->detalles as $det) {
                $prod = $det->producto;

                $cuentaIng = $prod->cuenta_ingreso_id ?? $libro?->cuenta_ingreso_id
                    ?? throw new \DomainException("'{$prod->descripcion}' no tiene cuenta de ingreso.");
                $base = (float) $det->subtotal;
                $add($haber, $cuentaIng, $base);
                $sumaIngresos += $base;
                $primerIngreso ??= $cuentaIng;

                if ($prod->controla_stock && (float) $det->costo_total > 0) {
                    $cuentaCmv = $prod->cuenta_costo_venta_id
                        ?? throw new \DomainException("'{$prod->descripcion}' no tiene cuenta de costo de venta.");
                    $cuentaInv = $prod->cuenta_activo_id ?? $venta->almacen?->cuenta_inventario_id ?? $venta->almacen?->cuenta_id
                        ?? throw new \DomainException("'{$prod->descripcion}' no tiene cuenta de inventario.");

                    $add($debe,  $cuentaCmv, (float) $det->costo_total);
                    $add($haber, $cuentaInv, (float) $det->costo_total);
                }
            }

            // Ajuste de redondeo (centavos) para que ingresos + IVA = total
            $dif = round(($total - $iva) - $sumaIngresos, 4);
            if (abs($dif) > 0.05) {
                throw new \DomainException("Los detalles ($sumaIngresos) no coinciden con el total de la venta sin IVA (" . ($total - $iva) . ').');
            }
            if ($primerIngreso && $dif != 0) {
                $haber[$primerIngreso] += $dif;
            }

            // ── Cobro: pagos con cuenta; el resto a CxC del cliente ──
            $restante = $total;
            foreach ($venta->pagos as $pago) {
                if ($pago->medio === 'credito') continue;
                $neto  = (float) $pago->monto - (float) ($pago->cambio ?? 0);
                $monto = min($neto, $restante);
                if ($monto <= 0) continue;

                $cuenta = $pago->cuenta_id ?? $venta->cuenta_cobro_id ?? $libro?->cuenta_cobro_id
                    ?? throw new \DomainException("Falta la cuenta de cobro del pago ({$pago->medio}).");
                $add($debe, $cuenta, $monto);
                $restante = round($restante - $monto, 2);
            }

            if ($restante > 0.01) {
                $esCredito = $venta->forma_pago === 'credito';
                $cuenta = $venta->cliente?->cuenta_por_cobrar_id
                    ?? ($esCredito ? null : ($venta->cuenta_cobro_id ?? $libro?->cuenta_cobro_id))
                    ?? throw new \DomainException($esCredito
                        ? 'El cliente no tiene cuenta por cobrar asignada.'
                        : 'Falta la cuenta de cobro.');
                $add($debe, $cuenta, $restante);
            }

            // ── IVA DF e IT ──
            if ($iva > 0) {
                $cuentaIva = $venta->cuenta_iva_df_id ?? $libro?->cuenta_iva_df_id
                    ?? Cuentas::where('nombre', 'IVA DÉBITO FISCAL')->where('permite_movimiento', true)->value('id')
                    ?? throw new \DomainException('Falta la cuenta de IVA Débito Fiscal.');
                $add($haber, $cuentaIva, $iva);
            }
            if ($it > 0) {
                $itGasto = Cuentas::where('nombre', 'Impuesto a las Transacciones')->where('permite_movimiento', true)->value('id')
                    ?? throw new \DomainException('Falta la cuenta de gasto "Impuesto a las Transacciones".');
                $itPagar = Cuentas::where('nombre', 'Impuesto a las Transacciones por Pagar')->where('permite_movimiento', true)->value('id')
                    ?? throw new \DomainException('Falta la cuenta "Impuesto a las Transacciones por Pagar".');
                $add($debe, $itGasto, $it);
                $add($haber, $itPagar, $it);
            }

            if (round(array_sum($debe), 2) !== round(array_sum($haber), 2)) {
                throw new \DomainException('Asiento desbalanceado. Debe: ' . array_sum($debe) . ' | Haber: ' . array_sum($haber));
            }

            // ── Crear asiento ──
            $asiento = Asiento::create([
                'periodo_id'     => $venta->periodo_id,
                'fecha'          => $venta->fecha,
                'numero_asiento' => AsientoHelper::generarNumeroAsiento($venta->periodo_id, 'ING'),
                'tipo'           => 'ING',
                'glosa'          => 'Venta ' . ($venta->nro_factura ?? $venta->nro_venta) . ' - ' . $venta->nombreCliente(),
                'comprobante'    => $venta->nro_factura ?? $venta->nro_venta,
                'creado_por'     => Auth::id(),
            ]);

            foreach ($debe as $cuentaId => $monto) {
                if (round($monto, 4) == 0) continue;
                DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaId,
                    'debe' => round($monto, 4), 'haber' => 0, 'descripcion' => 'Venta ' . $venta->nro_venta]);
            }
            foreach ($haber as $cuentaId => $monto) {
                if (round($monto, 4) == 0) continue;
                DetalleAsiento::create(['asiento_id' => $asiento->id, 'cuenta_id' => $cuentaId,
                    'debe' => 0, 'haber' => round($monto, 4), 'descripcion' => 'Venta ' . $venta->nro_venta]);
            }

            // ── Sincronizar AMBOS lados ──
            $venta->update([
                'asiento_id'        => $asiento->id,
                'estado_contable'   => 'contabilizada',
                'contabilizado_at'  => now(),
                'contabilizado_por' => Auth::id(),
            ]);

            $libro?->update([
                'asiento_id'        => $asiento->id,
                'estado'            => 'contabilizado',
                'cuenta_iva_df_id'  => $venta->cuenta_iva_df_id ?? $libro->cuenta_iva_df_id,
                'cuenta_cobro_id'   => $libro->cuenta_cobro_id ?? $venta->cuenta_cobro_id,
                'contabilizado_por' => Auth::id(),
                'contabilizado_at'  => now(),
            ]);

            return $asiento;
        });
    }
}