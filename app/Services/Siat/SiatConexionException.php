<?php
namespace App\Services\Siat;
 
/** Falla de red/timeout. Distinta de un rechazo de negocio del SIN. */
class SiatConexionException extends \RuntimeException {}