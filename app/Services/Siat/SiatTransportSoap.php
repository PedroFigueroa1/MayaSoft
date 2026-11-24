<?php

namespace App\Services\Siat;

use App\Models\SiatPuntoVenta;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SiatTransportSoap implements SiatTransport
{
    /** Cliente SOAP cacheado por config (para no recrear el WSDL en cada método) */
    private ?\SoapClient $cliente = null;
    private ?int $configCacheadaId = null;

    // ══════════════════════════════════════════════════════════════
    //  CONEXIÓN BASE
    // ══════════════════════════════════════════════════════════════
    private function cliente(SiatPuntoVenta $pv): \SoapClient
    {
        $config = $pv->configuracion;
        if (!$config) {
            throw new SiatConexionException(
                "Punto de venta {$pv->id} sin configuración SIAT."
            );
        }

        if ($this->cliente && $this->configCacheadaId === $config->id) {
            return $this->cliente;
        }

        $wsdl = rtrim($config->url_api ?: $config->urlPorDefecto(), '/')
              . '/consulta/Facturacion?WSDL';

        try {
            $this->cliente = new \SoapClient($wsdl, [
                'soap_version'   => SOAP_1_1,
                'trace'          => true,
                'exceptions'     => true,
                'cache_wsdl'     => WSDL_CACHE_NONE,
                'connection_timeout' => 30,
                'stream_context' => stream_context_create([
                    'http' => ['timeout' => 30],
                ]),
            ]);
        } catch (\Throwable $e) {
            throw new SiatConexionException(
                "No se pudo abrir el WSDL del SIN ({$wsdl}): {$e->getMessage()}",
                0, $e
            );
        }

        $this->configCacheadaId = $config->id;
        return $this->cliente;
    }

    // ══════════════════════════════════════════════════════════════
    //  PING
    // ══════════════════════════════════════════════════════════════
    public function ping(SiatPuntoVenta $pv): bool
    {
        try {
            $c = $this->cliente($pv);
            $r = $c->verificarComunicacion();
            // El SIN devuelve un objeto; basta con que no haya excepción.
            return $r !== null;
        } catch (\Throwable $e) {
            Log::warning('SIAT ping falló: ' . $e->getMessage());
            return false;
        }
    }

    // ══════════════════════════════════════════════════════════════
    //  CUIS
    // ══════════════════════════════════════════════════════════════
    public function solicitarCuis(SiatPuntoVenta $pv): array
    {
        $config = $pv->configuracion;
        $c      = $this->cliente($pv);

        $params = [
            'SolicitudCuis' => [
                'codigoAmbiente'   => (int) $config->cod_ambiente,
                'codigoModalidad'  => (int) $config->cod_modalidad,
                'codigoSistema'    => (string) $config->cod_sistema,
                'nit'              => (int) $config->empresa->nit,
                'codigoSucursal'   => (int) $pv->cod_sucursal,
                'codigoPuntoVenta' => (int) $pv->cod_punto_venta,
            ],
        ];

        try {
            $r = $c->solicitarCuis($params);
        } catch (\SoapFault $e) {
            throw new SiatConexionException(
                "SOAP solicitarCuis: {$e->getMessage()}", 0, $e
            );
        } catch (\Throwable $e) {
            throw new SiatConexionException(
                "Error de red en solicitarCuis: {$e->getMessage()}", 0, $e
            );
        }

        $resp = $r->RespuestaCuis ?? null;
        if (!$resp) {
            throw new SiatConexionException('Respuesta CUIS vacía del SIN.');
        }

        // Errores de negocio → devolver como excepción de negocio NO,
        // los propagamos al llamador como excepción dedicada
        if (!empty($resp->mensajesList)) {
            $msgs = $this->formatearMensajes($resp->mensajesList);
            throw new SiatConexionException("SIN rechazó CUIS: {$msgs}");
        }

        return [
            'codigo'        => (string) $resp->codigo,
            'fechaVigencia' => isset($resp->fechaVigencia)
                ? Carbon::parse($resp->fechaVigencia)
                : null,
            'cruda'         => $resp,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  CUFD
    // ══════════════════════════════════════════════════════════════
    public function solicitarCufd(SiatPuntoVenta $pv): array
    {
        $config = $pv->configuracion;
        $c      = $this->cliente($pv);

        if (empty($pv->cuis)) {
            throw new SiatConexionException(
                'No se puede pedir CUFD sin CUIS. Renueva CUIS primero.'
            );
        }

        $params = [
            'SolicitudCufd' => [
                'codigoAmbiente'   => (int) $config->cod_ambiente,
                'codigoModalidad'  => (int) $config->cod_modalidad,
                'codigoSistema'    => (string) $config->cod_sistema,
                'nit'              => (int) $config->empresa->nit,
                'codigoSucursal'   => (int) $pv->cod_sucursal,
                'codigoPuntoVenta' => (int) $pv->cod_punto_venta,
                'cuis'             => (string) $pv->cuis,
            ],
        ];

        try {
            $r = $c->solicitarCufd($params);
        } catch (\SoapFault $e) {
            throw new SiatConexionException(
                "SOAP solicitarCufd: {$e->getMessage()}", 0, $e
            );
        } catch (\Throwable $e) {
            throw new SiatConexionException(
                "Error de red en solicitarCufd: {$e->getMessage()}", 0, $e
            );
        }

        $resp = $r->RespuestaCufd ?? null;
        if (!$resp) {
            throw new SiatConexionException('Respuesta CUFD vacía del SIN.');
        }

        if (!empty($resp->mensajesList)) {
            $msgs = $this->formatearMensajes($resp->mensajesList);
            throw new SiatConexionException("SIN rechazó CUFD: {$msgs}");
        }

        return [
            'codigo'        => (string) $resp->codigo,
            'codigoControl' => (string) $resp->codigoControl,
            'fechaVigencia' => isset($resp->fechaVigencia)
                ? Carbon::parse($resp->fechaVigencia)
                : null,
            'cruda'         => $resp,
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  RECEPCIÓN FACTURA  (stub controlado)
    // ══════════════════════════════════════════════════════════════
    public function recepcionFactura(SiatPuntoVenta $pv, Venta $venta): array
    {
        // TODO: generar XML firmado + gzip + SHA256 + enviar por SOAP.
        // Mientras no esté implementado, devolvemos "rechazada" para que la
        // venta quede marcada y no se pierda, sin lanzar excepción (no es un
        // problema de red, es un problema de implementación).
        return [
            'aprobada'        => false,
            'codigoRecepcion' => null,
            'mensajes'        => ['Transporte SIAT no implementado todavía.'],
            'cruda'           => [
                'error'   => 'not_implemented',
                'detalle' => 'Implementa SiatTransportSoap::recepcionFactura().',
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  ANULACIÓN  (stub controlado)
    // ══════════════════════════════════════════════════════════════
    public function anulacionFactura(
        SiatPuntoVenta $pv, Venta $venta, int $codigoMotivo
    ): array {
        return [
            'aprobada' => false,
            'mensajes' => ['Transporte SIAT no implementado todavía.'],
            'cruda'    => [
                'error'   => 'not_implemented',
                'detalle' => 'Implementa SiatTransportSoap::anulacionFactura().',
            ],
        ];
    }

    // ══════════════════════════════════════════════════════════════
    //  HELPERS
    // ══════════════════════════════════════════════════════════════
    private function formatearMensajes($lista): string
    {
        if (!$lista) return 'sin detalle';
        if (!is_array($lista)) $lista = [$lista];

        return collect($lista)->map(function ($m) {
            $cod = $m->codigo      ?? '?';
            $msg = $m->descripcion ?? '?';
            return "[{$cod}] {$msg}";
        })->implode(' | ');
    }
}
