<?php
/**
 * Created by PhpStorm.
 * User: devert
 * Date: 5/28/20
 * Time: 8:41 PM
 */

namespace App\Console\Commands;


use App\Models\Balance;
use App\Services\Clients\ClientProvider;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateBalanceCommand extends Command
{
    use ClientProvider;
    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'balance:update {service_code}';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check balance with the service provider and update local database';
    
    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \App\Exceptions\GeneralException
     */
    public function handle()
    {
        Log::info("{$this->getCommandName()}: Running a new balance update command for service {$this->argument('service_code')}");
        
        $amount = $this->client($this->argument('service_code'))->balance();
        
        $previousBalance = Balance::where('service_code', $this->argument('service_code'))::last();
    
        Balance::create([
            'current' => $amount,
            'previous' => $previousBalance->current,
            'time' => Carbon::now(),
            'service_code' => $this->argument('service_code')
        ]);
    
        Log::info("{$this->getCommandName()}: Balance updated successfully for service {$this->argument('service_code')}");
    
    }
    
    public function getCommandName()
    {
        return class_basename($this);
    }
}