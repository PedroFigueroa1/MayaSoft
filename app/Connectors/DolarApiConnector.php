<?php
// app/Connectors/DolarApiConnector.php
namespace App\Connectors;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class DolarApiConnector
{
    protected string $baseUrl = 'https://bo.dolarapi.com';

    public function getParalelo(): ?array
    {
        $data = $this->get('/v1/dolares/paralelo');
        if (!$data) return null;
        return [
            'compra' => $data['compra'] ?? null,
            'venta'  => $data['venta'] ?? null,
        ];
    }

    protected function get(string $endpoint): ?array
    {
        $cacheKey = 'dolarapi_' . md5($endpoint);
        return Cache::remember($cacheKey, 3600, function () use ($endpoint) {
            $response = Http::get($this->baseUrl . $endpoint);
            return $response->successful() ? $response->json() : null;
        });
    }
}
