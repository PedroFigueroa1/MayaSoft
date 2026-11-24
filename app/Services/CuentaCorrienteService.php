<?php

namespace App\Services;

use App\Models\{MovimientoCuentaCorriente, ProveedorCliente};
use Illuminate\Support\Facades\DB;

class CuentaCorrienteService
{
    /**
     * Registra un cargo (factura, nota débito).
     * IDEMPOTENTE por referencia_tipo + referencia_id: si ya existe, lo devuelve.
     */
    public function registrarCargo(
        ProveedorCliente $pc,
        float $monto,
        string $concepto,
        array $opts = []
    ): MovimientoCuentaCorriente {
        // Idempotencia: evita duplicar si el flujo se reintenta
        if (!empty($opts['referencia_tipo']) && !empty($opts['referencia_id'])) {
            $existente = MovimientoCuentaCorriente::where('proveedor_cliente_id', $pc->id)
                ->where('tipo', 'cargo')
                ->where('referencia_tipo', $opts['referencia_tipo'])
                ->where('referencia_id', $opts['referencia_id'])
                ->first();
            if ($existente) return $existente;
        }

        return MovimientoCuentaCorriente::create([
            'empresa_id'           => $pc->empresa_id,
            'proveedor_cliente_id' => $pc->id,
            'fecha'                => $opts['fecha'] ?? now()->toDateString(),
            'fecha_vencimiento'    => $opts['fecha_vencimiento'] ?? null,
            'tipo'                 => 'cargo',
            'concepto'             => $concepto,
            'documento_tipo'       => $opts['documento_tipo'] ?? null,
            'documento_numero'     => $opts['documento_numero'] ?? null,
            'monto'                => round($monto, 2),
            'monto_pendiente'      => round($monto, 2),
            'monto_aplicado'       => 0,
            'moneda'               => $opts['moneda'] ?? 'BOB',
            'estado'               => 'pendiente',
            'referencia_tipo'      => $opts['referencia_tipo'] ?? null,
            'referencia_id'        => $opts['referencia_id'] ?? null,
            'creado_por'           => $opts['creado_por'] ?? auth()->id(),
        ]);
    }

    /**
     * Registra un abono y lo aplica FIFO contra cargos pendientes.
     * Si sobra monto, queda como saldo a favor (monto_pendiente del abono > 0).
     */
    public function registrarAbono(
        ProveedorCliente $pc,
        float $monto,
        string $concepto,
        array $opts = []
    ): MovimientoCuentaCorriente {
        return DB::transaction(function () use ($pc, $monto, $concepto, $opts) {
            $abono = MovimientoCuentaCorriente::create([
                'empresa_id'           => $pc->empresa_id,
                'proveedor_cliente_id' => $pc->id,
                'fecha'                => $opts['fecha'] ?? now()->toDateString(),
                'tipo'                 => 'abono',
                'concepto'             => $concepto,
                'documento_tipo'       => $opts['documento_tipo'] ?? null,
                'documento_numero'     => $opts['documento_numero'] ?? null,
                'monto'                => round($monto, 2),
                'monto_pendiente'      => round($monto, 2), // se reduce al aplicar
                'monto_aplicado'       => 0,
                'moneda'               => $opts['moneda'] ?? 'BOB',
                'estado'               => 'pagado',
                'referencia_tipo'      => $opts['referencia_tipo'] ?? null,
                'referencia_id'        => $opts['referencia_id'] ?? null,
                'creado_por'           => $opts['creado_por'] ?? auth()->id(),
            ]);

            $this->aplicarFifo($pc, (float) $monto, $abono->id);

            return $abono->fresh();
        });
    }

    /**
     * Aplica un monto contra los cargos pendientes del PC, del más antiguo al más nuevo.
     * Actualiza monto_aplicado y monto_pendiente de cada cargo y del abono.
     */
    public function aplicarFifo(ProveedorCliente $pc, float $monto, ?int $abonoId = null): float
    {
        $restante = $monto;

        $pendientes = MovimientoCuentaCorriente::where('proveedor_cliente_id', $pc->id)
            ->where('tipo', 'cargo')
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->where('monto_pendiente', '>', 0)
            ->orderBy('fecha')->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($pendientes as $cargo) {
            if ($restante <= 0) break;

            $aplicar = min($restante, (float) $cargo->monto_pendiente);

            $cargo->monto_aplicado = round((float) $cargo->monto_aplicado + $aplicar, 2);
            $cargo->monto_pendiente = round((float) $cargo->monto_pendiente - $aplicar, 2);
            $cargo->estado = $cargo->monto_pendiente > 0 ? 'parcial' : 'pagado';
            $cargo->save();

            $restante -= $aplicar;
        }

        // Lo aplicado al abono = monto original - restante
        if ($abonoId) {
            $abono = MovimientoCuentaCorriente::find($abonoId);
            if ($abono) {
                $aplicado = round($monto - $restante, 2);
                $abono->update([
                    'monto_aplicado'  => $aplicado,
                    'monto_pendiente' => round($restante, 2), // saldo a favor si > 0
                ]);
            }
        }

        return $restante;
    }

    /**
     * Saldo actual del PC.
     *  - Cliente: positivo = nos debe.
     *  - Proveedor: positivo = le debemos.
     */
    public function saldo(ProveedorCliente $pc): float
    {
        $cargos = (float) MovimientoCuentaCorriente::where('proveedor_cliente_id', $pc->id)
            ->vigentes()->where('tipo', 'cargo')->sum('monto_pendiente');
        $abonos = (float) MovimientoCuentaCorriente::where('proveedor_cliente_id', $pc->id)
            ->vigentes()->where('tipo', 'abono')->sum('monto_pendiente');

        return round($cargos - $abonos, 2);
    }

    /**
     * Anula un movimiento y revierte aplicaciones (FIFO inverso).
     */
    public function anular(MovimientoCuentaCorriente $m): void
    {
        DB::transaction(function () use ($m) {
            $m = MovimientoCuentaCorriente::lockForUpdate()->findOrFail($m->id);

            if ($m->estaAnulado()) return;

            if ($m->esAbono() && (float) $m->monto_aplicado > 0) {
                // Revertir FIFO: liberar montos de los cargos que este abono pagó
                $restante = (float) $m->monto_aplicado;
                $cargos = MovimientoCuentaCorriente::where('proveedor_cliente_id', $m->proveedor_cliente_id)
                    ->where('tipo', 'cargo')
                    ->whereIn('estado', ['parcial', 'pagado'])
                    ->where('monto_aplicado', '>', 0)
                    ->orderByDesc('fecha')->orderByDesc('id')
                    ->lockForUpdate()->get();

                foreach ($cargos as $c) {
                    if ($restante <= 0) break;
                    $devolver = min($restante, (float) $c->monto_aplicado);
                    $c->monto_aplicado = round((float) $c->monto_aplicado - $devolver, 2);
                    $c->monto_pendiente = round((float) $c->monto_pendiente + $devolver, 2);
                    $c->estado = $c->monto_pendiente > 0 ? 'parcial' : 'pendiente';
                    $c->save();
                    $restante -= $devolver;
                }
            }

            $m->update([
                'estado'          => 'anulado',
                'monto_pendiente' => 0,
                'monto_aplicado'  => $m->esCargo() ? (float) $m->monto_aplicado : 0,
            ]);
        });
    }
}
