<?php

namespace App\Services;

use App\Models\ColaSiat;
use App\Models\ConfiguracionSiat;
use App\Models\SiatPuntoVenta;
use App\Models\Venta;
use App\Services\Siat\SiatConexionException;
use App\Services\Siat\SiatTransport;
use Illuminate\Support\Facades\DB;

class SiatService
{
    public function __construct(private SiatTransport $transport) {}

    // ══════════════════════════════════════════════════════════════
    //  EMITIR
    // ══════════════════════════════════════════════════════════════
    public function emitirFactura(Venta $venta, SiatPuntoVenta $pv): array
    {
        $venta->loadMissing('detalles.producto');

        $config = $pv->configuracion;
        if (!$config) {
            throw new \RuntimeException("El punto de venta {$pv->id} no tiene configuración SIAT.");
        }

        // 1) Asegurar CUIS + CUFD vigentes en el PUNTO DE VENTA
        $this->asegurarCodigos($pv);

        // 2) Reservar número + CUF si aún no se hizo
        if (!$venta->nro_factura) {
            $enLinea = $this->transport->ping($config);
            $this->reservarNumero($venta, $pv, $enLinea ? 1 : 2);
            $venta->refresh();
        }

        // 3) Enviar al SIN
        return $this->enviar($venta, $config);
    }

    public function reenviar(Venta $venta, SiatPuntoVenta $pv): array
    {
        $venta->loadMissing('detalles.producto');

        if (!$venta->nro_factura) {
            return $this->emitirFactura($venta, $pv);
        }

        return $this->enviar($venta, $pv->configuracion);
    }

    // ══════════════════════════════════════════════════════════════
    //  ENVÍO
    // ══════════════════════════════════════════════════════════════
    private function enviar(Venta $venta, ConfiguracionSiat $config): array
    {
        if ($venta->modalidad_factura === 'offline') {
            $this->encolar($venta);
            return ['estado_siat' => 'pendiente'];
        }

        try {
            $r = $this->transport->recepcionFactura($config, $venta);

            if ($r['aprobada']) {
                ColaSiat::where('venta_id', $venta->id)
                    ->where('estado', 'pendiente')
                    ->get()
                    ->each
                    ->aprobada();

                return [
                    'estado_siat'      => 'aprobada',
                    'cod_autorizacion' => $r['codigoRecepcion'],
                    'respuesta_siat'   => json_encode($r['cruda']),
                    'enviado_siat_at'  => now(),
                ];
            }

            return [
                'estado_siat'    => 'rechazada',
                'respuesta_siat' => json_encode($r['cruda']),
            ];
        } catch (SiatConexionException $e) {
            $this->encolar($venta);
            return ['estado_siat' => 'pendiente'];
        }
    }

    public function enviarDesdeCola(ColaSiat $item): void
    {
        // Reintenta el envío de un ítem pendiente
        $venta = $item->venta;
        if (!$venta) {
            $item->update(['estado' => 'error', 'mensaje' => 'Venta no encontrada']);
            return;
        }

        $pv = $venta->puntoVenta ?? null;
        if (!$pv) {
            $item->update(['estado' => 'error', 'mensaje' => 'Punto de venta no encontrado']);
            return;
        }

        try {
            $r = $this->transport->recepcionFactura($pv->configuracion, $venta);

            if ($r['aprobada']) {
                $item->aprobada();
                $venta->update([
                    'estado_siat'      => 'aprobada',
                    'cod_autorizacion' => $r['codigoRecepcion'],
                    'respuesta_siat'   => json_encode($r['cruda']),
                    'enviado_siat_at'  => now(),
                ]);
            } else {
                $item->update([
                    'intentos' => $item->intentos + 1,
                    'mensaje'  => json_encode($r['mensajes'] ?? $r['cruda']),
                ]);
            }
        } catch (\Throwable $e) {
            $item->update([
                'intentos' => $item->intentos + 1,
                'mensaje'  => $e->getMessage(),
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  ANULAR
    // ══════════════════════════════════════════════════════════════
    public function anularFactura(Venta $venta, SiatPuntoVenta $pv, int $codigoMotivo = 1): void
    {
        if (!$venta->cuf) {
            throw new \Exception('La venta no tiene CUF asignado.');
        }

        $this->asegurarCodigos($pv);

        $r = $this->transport->anulacionFactura($pv->configuracion, $venta, $codigoMotivo);

        if (!$r['aprobada']) {
            throw new \Exception('El SIN no aceptó la anulación: ' . json_encode($r['mensajes']));
        }

        $venta->update([
            'estado_siat'    => 'anulada',
            'respuesta_siat' => json_encode($r['cruda']),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    //  CUIS + CUFD  (a nivel PUNTO DE VENTA)
    // ══════════════════════════════════════════════════════════════
    public function asegurarCodigos(SiatPuntoVenta $pv): void
    {
        $config = $pv->configuracion;

        if (!$config) {
            throw new \RuntimeException("El punto de venta {$pv->id} no tiene configuración SIAT.");
        }

        // ── CUIS ──
        if (!$pv->cuisVigente()) {
            $r = $this->transport->solicitarCuis($pv);

            $pv->update([
                'cuis'            => $r['codigo'],
                'cuis_emitido_at' => now(),
                'cuis_expira_at'  => !empty($r['fechaVigencia'])
                    ? \Carbon\Carbon::parse($r['fechaVigencia'])
                    : now()->addMonths(12),
            ]);

            $pv->refresh();
        }

        // ── CUFD ──
        if (!$pv->cufdVigente()) {
            $r = $this->transport->solicitarCufd($pv);

            $pv->update([
                'cufd_actual'     => $r['codigo'],
                'cufd_control'    => $r['codigoControl'],
                'cufd_emitido_at' => now(),
                'cufd_expira_at'  => !empty($r['fechaVigencia'])
                    ? \Carbon\Carbon::parse($r['fechaVigencia'])
                    : now()->addDay(),
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  RESERVA DE NÚMERO + CUF
    // ══════════════════════════════════════════════════════════════
    private function reservarNumero(Venta $venta, SiatPuntoVenta $pv, int $tipoEmision): void
    {
        DB::transaction(function () use ($venta, $pv, $tipoEmision) {
            // Lock para evitar colisión de correlativos
            $pv = SiatPuntoVenta::lockForUpdate()->find($pv->id);

            $nro   = $pv->siguienteCorrelativo();
            $fecha = now();

            $venta->update([
                'nro_factura'           => (string) $nro,
                'fecha_emision_factura' => $fecha,
                'cufd'                  => $pv->cufd_actual,
                'cuf'                   => $this->generarCuf($pv, $nro, $fecha, $tipoEmision),
                'modalidad_factura'     => $tipoEmision === 1 ? 'online' : 'offline',
            ]);
        });
    }

    private function generarCuf(
        SiatPuntoVenta $pv,
        int $nro,
        \Carbon\Carbon $fecha,
        int $tipoEmision
    ): string {
        $config = $pv->configuracion;

        $base = str_pad((string) $config->empresa->nit, 13, '0', STR_PAD_LEFT)
              . $fecha->format('YmdHis')
              . str_pad($fecha->format('v'), 3, '0', STR_PAD_LEFT)
              . str_pad((string) $pv->cod_sucursal, 4, '0', STR_PAD_LEFT)
              . $config->cod_modalidad
              . $tipoEmision
              . $config->cod_tipo_factura
              . '01'  // tipo documento sector (factura)
              . str_pad((string) $nro, 10, '0', STR_PAD_LEFT)
              . str_pad((string) $pv->cod_punto_venta, 4, '0', STR_PAD_LEFT);

        $base .= $this->modulo11($base);

        return strtoupper(gmp_strval(gmp_init($base, 10), 16)) . $pv->cufd_control;
    }

    /**
     * Módulo 11 según especificación del SIN (base 11, pesos 2..9 cíclicos).
     */
    private function modulo11(string $cadena): string
    {
        $pesos  = [2, 3, 4, 5, 6, 7, 8, 9];
        $suma   = 0;
        $len    = strlen($cadena);
        $indice = 0;

        for ($i = $len - 1; $i >= 0; $i--) {
            $suma += (int) $cadena[$i] * $pesos[$indice % 8];
            $indice++;
        }

        $resto = $suma % 11;
        $dv    = 11 - $resto;

        if ($dv === 11) return '0';
        if ($dv === 10) return '1';
        return (string) $dv;
    }

    // ══════════════════════════════════════════════════════════════
    //  COLA DE REINTENTOS
    // ══════════════════════════════════════════════════════════════
    private function encolar(Venta $venta): void
    {
        ColaSiat::firstOrCreate(
            ['venta_id' => $venta->id, 'estado' => 'pendiente'],
            [
                'empresa_id' => $venta->empresa_id,
                'cuf'        => $venta->cuf,
                'intentos'   => 0,
            ]
        );
    }
}
