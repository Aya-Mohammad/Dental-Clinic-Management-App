<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Checkout\Session;

use App\Traits\{Responses, RoleTrait, HasImageActions};
use App\Models\{User, Setting, Subscription, Advertisment, Clinic};
use App\Services\Manager\ClinicService;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    use RoleTrait, HasImageActions, Responses;

    protected $clinicService;
    public function __construct(ClinicService $clinicService)
    {
        $this->clinicService = $clinicService;
    }

    function paymentClinicSuccess(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = StripeSession::retrieve($request->get('session_id'));
        $user = User::find($session->metadata->user_id);
        $clinicData = json_decode($session->metadata->clinic_data, true);
        $workingHours = json_decode($session->metadata->working_hours, true);

        DB::beginTransaction();
        try {
            $clinic = $this->clinicService->createClinic($clinicData, $user);

            $duration = Setting::where('key', 'subscription_duration_days')->value('value') ?? 30;

            $clinic->update([
                'subscribed_at' => now(),
                'subscription_duration_days' => $duration
            ]);

            Subscription::create([
                'subscribable_type' => Clinic::class,
                'subscribable_id'   => $clinic->id,
                'subscribed_at'     => now(),
                'duration_days'     => $duration,
                'amount'            => $session->amount_total / 100,
            ]);

            $user->updateMainRole('manager');

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => true,
                'exception' => $e->getMessage(),
                'line' => $e->getLine()
            ], 500);
        }
        return redirect('http://localhost:8080/dashboard');
    }

    public function paymentAdvertisementSuccess(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = Session::retrieve($request->get('session_id'));

        DB::beginTransaction();

        try {
            $advertisement = Advertisment::find($session->metadata->advertisement_id);

            if (!$advertisement)  return $this->error('Advertisement not found.', 404);

            if ($advertisement->status !== 'approved')  return $this->error('Advertisement is not approved yet.', 403);

            $duration = $advertisement->subscription_duration_days ?? 15;

            $advertisement->update([
                'subscribed_at' => now(),
                'subscription_duration_days' => $duration
            ]);

            $advertisement->save();

            Subscription::create([
                'subscribable_type' => Advertisment::class,
                'subscribable_id'   => $advertisement->id,
                'subscribed_at'     => now(),
                'duration_days'     => $duration,
                'amount'            => $session->amount_total / 100,
            ]);

            $images = json_decode($session->metadata->images, true);
            if (!empty($images)) {
                foreach ($images as $image) {
                    if (is_array($image) && isset($image['path'])) {
                        $this->addImageFromPath(
                            $advertisement,
                            $image['path'],
                            \App\Models\AdvertismentImages::class,
                            'advertisment_id'
                        );
                    }
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during advertisement payment success: ' . $e->getMessage());
            return $this->error('Advertisement payment failed.', 500);
        }

        return redirect(('http://localhost.org:8080/dashboard'));
    }
}
