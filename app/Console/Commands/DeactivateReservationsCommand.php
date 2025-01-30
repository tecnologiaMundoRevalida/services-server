<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\DeactivateReservations;

class DeactivateReservationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reservations:deactivate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Desativa reserva de questões para comentar expiradas após 15 dias.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        DeactivateReservations::dispatch()->onQueue('high');
        $this->info('Job para desativar reservas disparado com sucesso!');
    }
}
