<?php

use App\Services\ReservationService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('reservas:expirar', function () {
    app(ReservationService::class)->sweep();
    $this->info('Reservas expiradas liberadas.');
})->purpose('Libera os números de reservas vencidas');
Schedule::command('reservas:expirar')->everyMinute()->withoutOverlapping();
