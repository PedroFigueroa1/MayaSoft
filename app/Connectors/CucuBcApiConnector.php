<?php
// app/Connectors/CucuBcApiConnector.php
namespace App\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class CucuBcApiConnector
{
    protected string $baseUrl = 'https://bcb.cucu.bo';

    public function getUfv(): ?float
    {
        $data = $this->get('/api/v1/tc/ufv');
        return $data['ufv'] ?? null;
    }

    public function getDolarOficial(): ?array
    {
        $data = $this->get('/api/v1/tc/oficial');
        if (!$data) return null;
        return [
            'compra' => $data['compra'] ?? null,
            'venta'  => $data['venta'] ?? null,
        ];
    }

    public function getMoneda(string $code): ?float
    {
        $monedas = $this->get('/api/v1/tc/monedas');
        return $monedas[$code] ?? null;
    }

    protected function get(string $endpoint): ?array
    {
        $cacheKey = 'cucu_api_' . md5($endpoint);
        return Cache::remember($cacheKey, 3600, function () use ($endpoint) {
            $response = Http::get($this->baseUrl . $endpoint);
            return $response->successful() ? $response->json() : null;
        });
    }
}
