<?php

namespace App\Services\Siat;

use App\Models\SiatPuntoVenta;
use App\Models\Venta;

/**
 * Contrato con el SIN. La implementación real habla SOAP:
 *   - header  apiKey: "TokenApi {token_delegado}"
 *   - servicios de códigos (cuis, cufd), operaciones (eventos significativos)
 *     y compra-venta (recepcionFactura, anulacionFactura)
 *   - factura = XML (firmado si modalidad electrónica) → gzip → SHA256 del gzip → base64
 *     enviados en las etiquetas `archivo` y `hashArchivo`
 *
 * Reglas del contrato:
 *   - Lanzar SiatConexionException SOLO ante fallas de red/timeout.
 *   - Un rechazo del SIN NO es excepción: se devuelve
 *     ['aprobada' => false, 'mensajes' => [...]].
 *   - Los errores de negocio (token inválido, CUIS vencido, NIT incorrecto)
 *     también se lanzan como SiatConexionException, porque el llamador
 *     (SiatService::asegurarCodigos) necesita abortar y reportar al usuario.
 */
interface SiatTransport
{
    /**
     * Verifica conectividad con el SIN.
     * No debe lanzar excepciones: devuelve false si no hay conexión.
     */
    public function ping(SiatPuntoVenta $pv): bool;

    /**
     * Solicita el CUIS para un punto de venta.
     *
     * @return array{
     *     codigo: string,
     *     fechaVigencia: \Carbon\Carbon|null,
     *     cruda: mixed
     * }
     * @throws SiatConexionException  Fallas de red o rechazo del SIN.
     */
    public function solicitarCuis(SiatPuntoVenta $pv): array;

    /**
     * Solicita el CUFD para un punto de venta (requiere CUIS vigente).
     *
     * @return array{
     *     codigo: string,
     *     codigoControl: string,
     *     fechaVigencia: \Carbon\Carbon|null,
     *     cruda: mixed
     * }
     * @throws SiatConexionException
     */
    public function solicitarCufd(SiatPuntoVenta $pv): array;

    /**
     * Envía una factura al SIN.
     *
     * @return array{
     *     aprobada: bool,
     *     codigoRecepcion: ?string,
     *     mensajes: array,
     *     cruda: array
     * }
     * @throws SiatConexionException  Solo si fue falla de red/timeout.
     */
    public function recepcionFactura(SiatPuntoVenta $pv, Venta $venta): array;

    /**
     * Anula una factura previamente aprobada.
     *
     * @param  int  $codigoMotivo  1=Error de emisión, 2=Error de datos, 3=Otros
     * @return array{
     *     aprobada: bool,
     *     mensajes: array,
     *     cruda: array
     * }
     * @throws SiatConexionException  Solo si fue falla de red/timeout.
     */
    public function anulacionFactura(
        SiatPuntoVenta $pv,
        Venta $venta,
        int $codigoMotivo
    ): array;



}
