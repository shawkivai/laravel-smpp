<?php

namespace App\Console\Commands;

use App\Services\SMPPService;
use Illuminate\Console\Command;

class SmppTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smpp-test {phone}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $SmppService = New SMPPService();
        
        // Get the phone number from command line argument
        $phoneNumber = $this->argument('phone');
        
        $SmppService->sendSms($phoneNumber, 'Testing Smpp Message Sending ability in PHP 8.3');

        $this->info('SMPP Test Command Executed');
    }
}
