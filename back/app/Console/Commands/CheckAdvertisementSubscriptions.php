<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Advertisment;
use App\Services\Admin\AdminService;
use Illuminate\Support\Facades\Log;

class CheckAdvertisementSubscriptions extends Command
{
    protected $signature = 'check:advertisements';

    protected $description = 'Check all advertisements subscriptions, handle expiration and notify users';

    public function handle()
    {
        $ads = Advertisment::with('clinic.user')->get();

        foreach ($ads as $ad) {
            try {
                app(AdminService::class)
                    ->checkAdvertismentSubscription($ad);
            } catch (\Exception $e) {
                Log::error("Error checking advertisement {$ad->id}: " . $e->getMessage());
            }
        }

        $this->info('Advertisements checked successfully.');
    }
}
