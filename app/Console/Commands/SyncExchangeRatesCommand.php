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
        $consultedAt = now();

        $this->info('Consultando https://bcb.cucu.bo/api/v1/tc ...');
        $response = Http::get('https://bcb.cucu.bo/api/v1/tc');

        if (!$response->successful()) {
            $this->error("Error HTTP: " . $response->status());
            return 1;
        }

        $data = $response->json();
        $baseDate = Carbon::parse($data['ufv']['fecha'] ?? now());

        // 1. UFV
        if (isset($data['ufv']['valor'])) {
            ExchangeRate::updateOrCreate(
                ['type' => 'ufv', 'date' => $baseDate],
                ['value' => $data['ufv']['valor'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ UFV: {$data['ufv']['valor']}");
        }

        // 2. Dólar oficial (un solo registro con compra/venta)
        if (isset($data['tc_oficial']['detalle'])) {
            $compra = null;
            $venta = null;
            foreach ($data['tc_oficial']['detalle'] as $item) {
                if ($item['fecha_label'] === 'Compra') $compra = $item['valor'];
                if ($item['fecha_label'] === 'Venta') $venta = $item['valor'];
            }
            if ($venta) {
                ExchangeRate::updateOrCreate(
                    ['type' => 'dolar_oficial', 'date' => $baseDate],
                    [
                        'value' => $venta,
                        'buy_value' => $compra,
                        'consulted_at' => $consultedAt
                    ]
                );
                $this->info("✓ Dólar oficial (compra: $compra, venta: $venta)");
            }
        }

        // 3. Dólar referencial (un solo registro con compra/venta)
        if (isset($data['tc_referencial_usd']['compra'])) {
            $refDate = Carbon::parse($data['tc_referencial_usd']['fecha']);
            ExchangeRate::updateOrCreate(
                ['type' => 'dolar_referencial', 'date' => $refDate],
                [
                    'value' => $data['tc_referencial_usd']['venta'],
                    'buy_value' => $data['tc_referencial_usd']['compra'],
                    'consulted_at' => $consultedAt
                ]
            );
            $this->info("✓ Dólar referencial (compra: {$data['tc_referencial_usd']['compra']}, venta: {$data['tc_referencial_usd']['venta']})");
        }

        // 4. Monedas internacionales (solo value)
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

        // 5. Inflación
        if (isset($data['inflacion']['fecha'])) {
            $infDateObj = Carbon::createFromFormat('Y-m', $data['inflacion']['fecha'])->startOfMonth();
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

        // 6. Oro
        if (isset($data['oro']['usd_otf'])) {
            $oroDate = Carbon::parse($data['oro']['fecha']);
            ExchangeRate::updateOrCreate(
                ['type' => 'oro_usd_otf', 'date' => $oroDate],
                ['value' => $data['oro']['usd_otf'], 'consulted_at' => $consultedAt]
            );
            $this->info("✓ Oro: {$data['oro']['usd_otf']} USD/OTF");
        }

        // 7. TRE
        if (isset($data['tre']['mn'])) {
            $treStart = explode(' al ', $data['tre']['fecha'])[0];
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

        // 8. OMA (con manejo de error por fecha en español)
        if (isset($data['oma']['tasa_reporto_10d'])) {
            try {
                // Intentar parsear fecha tipo "Mayo 2026" configurando locale a español
                $currentLocale = setlocale(LC_TIME, '0');
                setlocale(LC_TIME, 'es_ES.utf8', 'Spanish_Spain', 'Spanish');
                $omaDateObj = Carbon::createFromFormat('F Y', $data['oma']['fecha'])->startOfMonth();
                setlocale(LC_TIME, $currentLocale);

                ExchangeRate::updateOrCreate(
                    ['type' => 'oma_reporto_10d', 'date' => $omaDateObj],
                    ['value' => $data['oma']['tasa_reporto_10d'], 'consulted_at' => $consultedAt]
                );
                ExchangeRate::updateOrCreate(
                    ['type' => 'oma_subasta_273d', 'date' => $omaDateObj],
                    ['value' => $data['oma']['tasa_subasta_273d'], 'consulted_at' => $consultedAt]
                );
                $this->info("✓ OMA registrada");
            } catch (\Exception $e) {
                $this->warn("⚠ OMA no se pudo guardar (fecha: {$data['oma']['fecha']}) - " . $e->getMessage());
            }
        }

        $this->info("✅ Sincronización completada a las {$consultedAt}");
        return 0;
    }
}
