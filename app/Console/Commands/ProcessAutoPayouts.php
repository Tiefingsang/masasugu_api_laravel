<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payments\AutoPayoutService;

class ProcessAutoPayouts extends Command
{
    protected $signature = 'payouts:auto';
    protected $description = 'Déclenche les retraits automatiques pour les vendeurs';

    public function handle()
    {
        $service = new AutoPayoutService();
        $service->processAutoPayouts();
        $this->info('Retraits automatiques traités.');
        return Command::SUCCESS;
    }
}
