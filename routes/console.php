<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\SyncExchangeRatesCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
Schedule::command(SyncExchangeRatesCommand::class)
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->onOneServer();
