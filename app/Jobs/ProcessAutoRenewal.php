<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAutoRenewal implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function handle(): void
    {
        // Clientes con fecha_inicio + 30 días = hoy → renovar
        $expiring = User::where('role', 'client')
            ->where('status', 'activo')
            ->whereNotNull('fecha_inicio')
            ->whereRaw('DATE_ADD(fecha_inicio, INTERVAL 30 DAY) = CURDATE()')
            ->get();

        foreach ($expiring as $client) {
            Log::info("Auto-renewal check for user {$client->id}");
            // En producción: procesar pago con Wompi
        }
    }
}
