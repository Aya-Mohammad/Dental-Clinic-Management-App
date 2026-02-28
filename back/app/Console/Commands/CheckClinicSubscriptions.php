<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Clinic;
use App\Services\Admin\AdminService;
use Illuminate\Support\Facades\Log;

class CheckClinicSubscriptions extends Command
{
    protected $signature = 'check:clinics';

    protected $description = 'Check all clinic subscriptions, handle expiration and notify users';

    public function handle()
    {
        $clinics = Clinic::with('user')->get();

        foreach ($clinics as $clinic) {
            try {
                app(AdminService::class)
                    ->checkClinicSubscription($clinic);
            } catch (\Exception $e) {
                Log::error("Error checking clinic {$clinic->id}: " . $e->getMessage());
            }
        }

        $this->info('Clinics checked successfully.');
    }
}
