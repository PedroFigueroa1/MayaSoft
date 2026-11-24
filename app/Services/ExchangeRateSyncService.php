<?php
// app/Services/ExchangeRateSyncService.php
namespace App\Services;

use App\Connectors\CucuBcApiConnector;
use App\Connectors\DolarApiConnector;
use App\Models\ExchangeRate;
use Carbon\Carbon;

class ExchangeRateSyncService
{
    public function __construct(
        protected CucuBcApiConnector $bcbApi,
        protected DolarApiConnector $dolarApi
    ) {}

    public function syncAll(Carbon $date = null): void
    {
        $date = $date ?? Carbon::today();

        // 1. UFV
        $ufv = $this->bcbApi->getUfv();
        if ($ufv) {
            ExchangeRate::updateOrCreate(
                ['type' => 'ufv', 'date' => $date],
                ['value' => $ufv]
            );
        }

        // 2. Dólar oficial (compra/venta)
        $oficial = $this->bcbApi->getDolarOficial();
        if ($oficial && isset($oficial['venta'])) {
            ExchangeRate::updateOrCreate(
                ['type' => 'dolar_oficial', 'date' => $date],
                [
                    'value'     => $oficial['venta'],
                    'buy_value' => $oficial['compra'] ?? null,
                ]
            );
        }

        // 3. Dólar paralelo (si falla, simplemente no se guarda)
        $paralelo = $this->dolarApi->getParalelo();
        if ($paralelo && isset($paralelo['venta'])) {
            ExchangeRate::updateOrCreate(
                ['type' => 'dolar_paralelo', 'date' => $date],
                [
                    'value'     => $paralelo['venta'],
                    'buy_value' => $paralelo['compra'] ?? null,
                ]
            );
        }

        // 4. (Opcional) Guardar otras monedas: Euro, Real, etc.
        $euro = $this->bcbApi->getMoneda('EUR');
        if ($euro) {
            ExchangeRate::updateOrCreate(
                ['type' => 'euro', 'date' => $date],
                ['value' => $euro]
            );
        }
        $real = $this->bcbApi->getMoneda('BRL');
        if ($real) {
            ExchangeRate::updateOrCreate(
                ['type' => 'real', 'date' => $date],
                ['value' => $real]
            );
        }
        // Añade más monedas si tu contabilidad lo requiere.
    }
}
