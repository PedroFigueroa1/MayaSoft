<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncExchangeRatesCommand extends Command
{
    protected $signature = 'exchange:sync {date?}';
    protected $description = 'Sincroniza todos los indicadores del BCB usando la API de cucu.bo';

    public function handle()
    {
        $consultedAt = now(); // misma marca de tiempo para todo el bloque

        // 1. Obtener datos de la API
        $response = Http::get('https://bcb.cucu.bo/api/v1/tc');
        if (!$response->successful()) {
            $this->error("Error al consultar API: " . $response->status());
            return 1;
        }

        $data = $response->json();

        // Fecha base (normalmente la fecha de la UFV)
        $baseDate = Carbon::parse($data['ufv']['fecha'] ?? now());

        // 2. UFV
        if (isset($data['ufv']['valor'])) {
            ExchangeRate::updateOrCreate(
                ['type' => 'ufv', 'date' => $baseDate],
                ['value' => $data['ufv']['valor'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ UFV: {$data['ufv']['valor']}");
        }

        // 3. Dólar oficial (compra y venta)
        if (isset($data['tc_oficial']['detalle'])) {
            foreach ($data['tc_oficial']['detalle'] as $item) {
                $type = $item['fecha_label'] === 'Compra' ? 'dolar_oficial_compra' : 'dolar_oficial_venta';
                ExchangeRate::updateOrCreate(
                    ['type' => $type, 'date' => $baseDate],
                    ['value' => $item['valor'], 'consulted_at' => $consultedAt]
                );
            }
            $this->info("✓ Dólar oficial (venta): {$data['tc_oficial']['detalle'][1]['valor']}");
        }

        // 4. Dólar referencial (tiene su propia fecha en el JSON)
        if (isset($data['tc_referencial_usd']['compra'])) {
            $refDate = Carbon::parse($data['tc_referencial_usd']['fecha']);
            ExchangeRate::updateOrCreate(
                ['type' => 'dolar_referencial_compra', 'date' => $refDate],
                ['value' => $data['tc_referencial_usd']['compra'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'dolar_referencial_venta', 'date' => $refDate],
                ['value' => $data['tc_referencial_usd']['venta'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ Dólar referencial (venta): {$data['tc_referencial_usd']['venta']}");
        }

        // 5. Monedas internacionales
        if (isset($data['monedas']['cotizaciones'])) {
            $currenciesDate = Carbon::parse($data['monedas']['fecha']);
            foreach ($data['monedas']['cotizaciones'] as $coin) {
                ExchangeRate::updateOrCreate(
                    ['type' => $coin['codigo'], 'date' => $currenciesDate],
                    ['value' => $coin['bob_por_unidad'], 'consulted_at' => $consultedAt]
                );
            }
            $this->info("✓ Monedas: " . count($data['monedas']['cotizaciones']) . " registros");
        }

        // 6. Inflación (IPC, variación mensual, anual)
        if (isset($data['inflacion']['fecha'])) {
            $infDate = $data['inflacion']['fecha']; // string "2026-03"
            // Convertir a primer día del mes para guardar como fecha (ej. 2026-03-01)
            $infDateObj = Carbon::createFromFormat('Y-m', $infDate)->startOfMonth();

            ExchangeRate::updateOrCreate(
                ['type' => 'inflacion_ipc', 'date' => $infDateObj],
                ['value' => $data['inflacion']['ipc'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'inflacion_mensual', 'date' => $infDateObj],
                ['value' => $data['inflacion']['mensual_pct'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'inflacion_anual', 'date' => $infDateObj],
                ['value' => $data['inflacion']['anual_pct'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ Inflación mensual: {$data['inflacion']['mensual_pct']}%");
        }

        // 7. Oro
        if (isset($data['oro']['usd_otf'])) {
            $oroDate = Carbon::parse($data['oro']['fecha']);
            ExchangeRate::updateOrCreate(
                ['type' => 'oro_usd_otf', 'date' => $oroDate],
                ['value' => $data['oro']['usd_otf'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ Oro: {$data['oro']['usd_otf']} USD/OTF");
        }

        // 8. TRE (Tasa de Referencia)
        if (isset($data['tre']['mn'])) {
            $treDate = $data['tre']['fecha']; // ej. "01/05/2026 al 31/05/2026"
            // Guardamos como fecha de inicio para simplificar
            $treStart = explode(' al ', $treDate)[0];
            $treDateObj = Carbon::createFromFormat('d/m/Y', $treStart);
            ExchangeRate::updateOrCreate(
                ['type' => 'tre_mn', 'date' => $treDateObj],
                ['value' => $data['tre']['mn'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'tre_mvdol', 'date' => $treDateObj],
                ['value' => $data['tre']['mvdol'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'tre_mn_ufv', 'date' => $treDateObj],
                ['value' => $data['tre']['mn_ufv'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'tre_me', 'date' => $treDateObj],
                ['value' => $data['tre']['me'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ TRE registrada");
        }

        // 9. OMA (Opcional)
        if (isset($data['oma']['tasa_reporto_10d'])) {
            $omaDate = $data['oma']['fecha']; // "Mayo 2026"
            $omaDateObj = Carbon::createFromFormat('F Y', $omaDate)->startOfMonth();
            ExchangeRate::updateOrCreate(
                ['type' => 'oma_reporto_10d', 'date' => $omaDateObj],
                ['value' => $data['oma']['tasa_reporto_10d'], 'consulted_at' => $consultedAt]
            );
            ExchangeRate::updateOrCreate(
                ['type' => 'oma_subasta_273d', 'date' => $omaDateObj],
                ['value' => $data['oma']['tasa_subasta_273d'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ OMA registrada");
        }

        $this->info("✅ Sincronización completada a las {$consultedAt}");
        return 0;
    }
}
