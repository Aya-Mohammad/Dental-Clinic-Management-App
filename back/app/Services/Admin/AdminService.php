<?php

namespace App\Services\Admin;

use App\Models\{
    Clinic, Advertisment,
    Setting, Subscription,
     UserNotification,
};

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Services\FirebaseService;
use App\Services\Manager\AdvertismentService;

use App\Notifications\MailNotification;

class AdminService
{

    protected $service;
    public function __construct(AdvertismentService $service)
    {
        $this->service = $service;
    }

    /** Check if an advertisement subscription is active, handle expiration and notify. */
    public function checkAdvertismentSubscription(Advertisment $advertisment): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isManager = $user->roles()->where('name', 'manager')->exists();

        if ($isManager && $advertisment->clinic->user_id !== $user->id) {
            return [ 'status' => false, 'message' => 'Unauthorized', 'data' => null ];
        }

        if (!$advertisment->subscribed_at || !$advertisment->subscription_duration_days) {
            return ['status' => false, 'message' => __('Subscription not found'), 'data' => null];
        }

        $expiresAt = $advertisment->subscribed_at->copy()->addDays($advertisment->subscription_duration_days);
        $remainingDays = max(now()->diffInDays($expiresAt, false), 0);
        $isActive = now()->lessThan($expiresAt);

        if (now()->greaterThanOrEqualTo($expiresAt) && $advertisment->status !== 'expired') {
            $advertisment->status = 'expired';
            $advertisment->save();
        }

        // $notificationSent = false;

        if ($remainingDays > 0 && $remainingDays <= 3) {
            $user = $advertisment->clinic->user;
            $token = $user->fcm_token;
            $title = 'Advertisement Subscription Expiry Alert';
            $body = 'The advertisement subscription will expire in few days';
            $data = ['advertisment_id' => $advertisment->id, 'type' => 'advertisement_subscription_expiry'];

            if (empty($token)) {
                $user->notify(new MailNotification("Subscription", $body, $title));
                Log::info("Email notification sent to user ID {$user->id}");
            } else {
                try {
                    resolve(FirebaseService::class)->sendNotification($token, $title, $body, $data);
                    Log::info("FCM sent successfully for advertisement ID {$advertisment->id}");
                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                    Log::error("Invalid FCM token for advertisement ID {$advertisment->id}: " . $e->getMessage());
                    $user->notify(new MailNotification("Subscription", $body, $title));
                    Log::info("Email fallback sent to user ID {$user->id}");
                }
            }

            UserNotification::create([
                'type' => 'advertisement',
                'title' => $title,
                'messages' => $body,
                'is_read' => false,
                'data' => null,
                'user_id' => $user->id,
            ]);

            // $notificationSent = true;
        }

        return [
            'status' => true,
            'message' => 'Advertisement subscription status fetched successfully',
            'data' => [
                'active' => $isActive,
                'status' => $advertisment->status,
                'subscription_duration_days' => $advertisment->subscription_duration_days,
                'remaining_days' => $remainingDays,
                // 'notification_sent' => $notificationSent,
            ],
        ];
    }

    /** Check if a clinic subscription is active, handle notification if nearing expiration. */
    public function checkClinicSubscription(Clinic $clinic): array
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isManager = $user->roles()->where('name', 'manager')->exists();

        if ($isManager && $clinic->user_id !== $user->id()) {
            return [ 'status' => false, 'message' => 'Unauthorized', 'data' => null ];
        }

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
        }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
        $remainingDays = max(now()->diffInDays($expiresAt, false), 0);
        $isActive = now()->lessThan($expiresAt);

        // $notificationSent = false;

        if ($remainingDays > 0 && $remainingDays <= 5) {
            $user = $clinic->user;
            $token = $user->fcm_token;
            $title = 'Subscription Expiry Alert';
            $body = 'The clinic subscription will expire in few days';
            $data = ['clinic_id' => $clinic->id, 'type' => 'subscription_expiry'];

            if (empty($token)) {
                $user->notify(new MailNotification("Subscription", $body, $title));
                Log::info("Email notification sent to user ID {$user->id}");
            } else {
                try {
                    resolve(FirebaseService::class)->sendNotification($token, $title, $body, $data);
                    Log::info("FCM sent successfully for clinic ID {$clinic->id}");
                } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                    Log::error("Invalid FCM token for clinic ID {$clinic->id}: " . $e->getMessage());
                    $user->notify(new MailNotification("Subscription", $body, $title));
                    Log::info("Email fallback sent to user ID {$user->id}");
                }
            }

            UserNotification::create([
                'type' => 'advertisement',
                'title' => $title,
                'messages' => $body,
                'is_read' => false,
                'data' => null,
                'user_id' => $user->id,
            ]);

            // $notificationSent = true;
        }

        return [
            'status' => true,
            'message' => __('Clinic subscription status fetched successfully'),
            'data' => [
                'active' => $isActive,
                'subscription_duration_days' => $clinic->subscription_duration_days,
                'remaining_days' => $remainingDays,
                // 'notification_sent' => $notificationSent,
            ],
        ];
    }

    /** Set advertisement subscription price and duration (admin panel). */
    public function setAdvertismentSubscription(int $price, int $days)
    {
        Setting::updateOrCreate(
            ['key' => 'advertisement_price'], ['value' => $price]
        );

        Setting::updateOrCreate(
            ['key' => 'advertisement_duration_days'], ['value' => $days]
        );

        return [
            'status' => true,
            'messages' => 'Advertisement subscription price and duration set successfully',
            'data' => [
                'price' => $price,
                'duration_days' => $days
            ]
        ];
    }

    /** Set clinic subscription price and duration (admin panel). */
    public function setClinicSubscription(int $price, int $days)
    {
        Setting::updateOrCreate(
            ['key' => 'subscription_price'], ['value' => $price]
        );

        Setting::updateOrCreate(
            ['key' => 'subscription_duration_days'], ['value' => $days]
        );

        return [
            'status' => true,
            'messages' => 'CLinic subscription price and duration set successfully',
            'data' => [
                'price' => $price,
                'duration_days' => $days
            ]
        ];
    }

    /** Get all subscription records of a specific clinic (admin or owner). */
    public function getClinicSubscription($clinicId, $user): array
    {
        $clinic = Clinic::find($clinicId);
        if (!$clinic) {
            return [
                'status' => false, 'messages' => 'Clinic not found', 'data' => null
            ];
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isManager = $user->roles()->where('name', 'manager')->exists();

        if ($isManager && $clinic->user_id !== $user->id()) {
            return [ 'status' => false, 'message' => 'Unauthorized', 'data' => null ];
        }

        $subscriptions = Subscription::where([
            'subscribable_id'   => $clinicId,
            'subscribable_type' => Clinic::class,
        ])
            ->with('subscribable')
            ->get();

        if ($subscriptions->isEmpty()) {
            return [
                'status' => false, 'messages' => 'No subscriptions found for this clinic', 'data' => null
            ];
        }

        foreach ($subscriptions as $subscription) {
            $expiresAt = Carbon::parse($subscription->subscribed_at)->addDays($subscription->duration_days);
            $subscription->status = now()->greaterThanOrEqualTo($expiresAt) ? 'disactive' : 'active';
            $subscription->save();
        }

        return [
            'status' => true,
            'messages' => 'Clinic subscriptions fetched successfully',
            'data' => $subscriptions
        ];
    }

    /** Get all advertisement subscriptions for a clinic (admin or owner). */
    public function getAdvertisementSubscriptions($clinicId, $user): array
    {
        $clinic = Clinic::find($clinicId);
        if (!$clinic) {
            return [
                'status' => false, 'messages' => 'Clinic not found', 'data' => null
            ];
        }

        if (!$user->hasRole('admin') && $clinic->user_id !== $user->id) {
            return [
                'status' => false, 'messages' => 'Unauthorized', 'data' => null
            ];
        }

        $advertisementSubscriptions = Subscription::whereHasMorph(
            'subscribable',
            [Advertisment::class],
            function ($query) use ($clinicId) {
                $query->where('clinic_id', $clinicId);
            }
        )->with('subscribable.clinic')->get();

        if ($advertisementSubscriptions->isEmpty()) {
            return [
                'status' => false, 'messages' => 'No advertisement subscriptions found for this clinic', 'data' => null
            ];
        }

        foreach ($advertisementSubscriptions as $subscription) {
            $expiresAt = Carbon::parse($subscription->subscribed_at)->addDays($subscription->duration_days);
            $subscription->status = now()->greaterThanOrEqualTo($expiresAt) ? 'disactive' : 'active';
            $subscription->save();
        }

        return [
            'status' => true,
            'messages' => 'Advertisement subscriptions fetched successfully',
            'data' => $advertisementSubscriptions
        ];
    }

    /** Approve an advertisement (admin panel). */
    public function approveAdvertisment(int $id): array
    {
        $ad = Advertisment::find($id);

        if (!$ad)  return ['status' => false, 'messages' => 'Advertisement not found', 'data' => null];

        if ($ad->status !== 'pending')  return ['status' => false, 'messages' => 'Already handled', 'data' => null];

        $ad->status = 'approved';
        $ad->save();

        $user = $ad->clinic->user;

        $paymentSession = $this->service->createPaymentSession(request(), $ad->id);

        $title = 'Advertisement Approved';
        $body = 'Your advertisement has been approved. Please complete payment to activate it';

        if (!empty($user->fcm_token)) {
            try {
                resolve(FirebaseService::class)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    [
                        'advertisement_id' => $ad->id,
                        'type' => 'advertisement_payment',
                        'checkout_url' => $paymentSession['data']['checkout_url'] ?? null
                    ]
                );
            } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                Log::error("Invalid FCM token for user ID {$user->id}: " . $e->getMessage());
                $user->notify(new MailNotification("Advertisement", $body, $title));
            }
        } else {
            $user->notify(new MailNotification("Advertisement", $body, $title));
        }

        UserNotification::create([
            'type' => 'advertisement_payment',
            'title' => $title,
            'messages' => $body,
            'is_read' => false,
            'data' => json_encode([
                'advertisement_id' => $ad->id,
                'checkout_url' => $paymentSession['data']['checkout_url'] ?? null
            ]),
            'user_id' => $user->id,
        ]);

        return [
            'status' => true,
            'messages' => 'Advertisement approved',
            'data' => [
                'id' => $ad->id,
                'checkout_url' => $paymentSession['data']['checkout_url'] ?? null
            ]
        ];
    }

    /** Reject an advertisement (admin panel). */
    public function rejectAdvertisment(int $id): array
    {
        $ad = Advertisment::find($id);

        if (!$ad)   return ['status' => false, 'messages' => 'Advertisement not found', 'data' => null];

        if ($ad->status !== 'pending')  return ['status' => false, 'messages' => 'Already handled', 'data' => null];

        $ad->status = 'rejected';
        $ad->save();

        $user = $ad->clinic->user;

        $title = 'Advertisement Rejected';
        $body = 'Your advertisement has been rejected. Please try again later';

        if (!empty($user->fcm_token)) {
            try {
                resolve(FirebaseService::class)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    [
                        'advertisement_id' => $ad->id,
                        'type' => 'advertisement_rejected'
                    ]
                );
            } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                $user->notify(new \App\Notifications\MailNotification("Advertisement", $body, $title));
            }
        } else {
            $user->notify(new MailNotification("Advertisement", $body, $title));
        }

        UserNotification::create([
            'type' => 'advertisement_rejected',
            'title' => $title,
            'messages' => $body,
            'is_read' => false,
            'data' => null,
            'user_id' => $user->id,
        ]);

        return [
            'status' => true,
            'messages' => 'Advertisement rejected',
            'data' => ['id' => $ad->id]
        ];
    }

    /** Get current settings (price & duration) for clinic and advertisement subscriptions. */
    public function getAllSubscriptionSettings(): array
    {
        $advertisementPrice = Setting::where('key', 'advertisement_price')->value('value') ?? 0;
        $advertisementDuration = Setting::where('key', 'advertisement_duration_days')->value('value') ?? 0;

        $clinicPrice = Setting::where('key', 'subscription_price')->value('value') ?? 0;
        $clinicDuration = Setting::where('key', 'subscription_duration_days')->value('value') ?? 0;

        return [
            'status' => true,
            'messages' => 'Subscription settings fetched successfully',
            'data' => [
                'advertisement' => [
                    'price' => (int) $advertisementPrice,
                    'duration_days' => (int) $advertisementDuration,
                ],
                'clinic' => [
                    'price' => (int) $clinicPrice,
                    'duration_days' => (int) $clinicDuration,
                ],
            ],
        ];
    }

    /** statistics */
    public function getAllClinicsStatsAndServices($year = null): array
    {
        $year = $year ?? now()->year;

        $appointmentsData = DB::table('appointments')
            ->select(
                'clinic_id',
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(DISTINCT user_id) as total_patients')
            )
            ->whereYear('created_at', $year)
            ->groupBy('clinic_id', 'year', 'month')
            ->orderBy('clinic_id')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $clinicsPatientsStats = [];

        foreach ($appointmentsData as $row) {
            $monthLabel = Carbon::create($row->year, $row->month)->format('M Y');

            if (!isset($clinicsPatientsStats[$row->clinic_id])) {
                $clinicsPatientsStats[$row->clinic_id] = [
                    'stats' => [],
                    'previousCount' => 0
                ];
            }

            $previousCount = $clinicsPatientsStats[$row->clinic_id]['previousCount'];
            $change = 0;
            $percentChange = 0;

            if ($previousCount !== 0) {
                $change = $row->total_patients - $previousCount;
                $percentChange = $previousCount > 0
                    ? round(($change / $previousCount) * 100, 2)
                    : 100;
            }

            $clinicsPatientsStats[$row->clinic_id]['stats'][] = [
                'month' => $monthLabel,
                'patients_count' => $row->total_patients,
                'change' => $change,
                'percentage_change' => $percentChange
            ];

            $clinicsPatientsStats[$row->clinic_id]['previousCount'] = $row->total_patients;
        }

        $clinics = Clinic::with(['treatments.services'])->get();
        $result = [];

        foreach ($clinics as $clinic) {
            $servicesUsage = [];

            $serviceCounts = DB::table('appointments')
                ->join('treatments', 'appointments.treatment_id', '=', 'treatments.id')
                ->join('service_treatment', 'treatments.id', '=', 'service_treatment.treatment_id')
                ->join('services', 'service_treatment.service_id', '=', 'services.id')
                ->where('treatments.clinic_id', $clinic->id)
                ->whereYear('appointments.created_at', $year)
                ->select(
                    'services.id as service_id',
                    'services.name as service_name',
                    DB::raw('COUNT(appointments.id) as usage_count')
                )
                ->groupBy('services.id', 'services.name')
                ->get();

            $totalUsage = $serviceCounts->sum('usage_count');

            foreach ($serviceCounts as $service) {
                $usagePercentage = $totalUsage > 0 ? round(($service->usage_count / $totalUsage) * 100, 2) : 0;
                $servicesUsage[] = [
                    'service_id' => $service->service_id,
                    'service_name' => $service->service_name,
                    'usage_count' => $service->usage_count,
                    'usage_percentage' => $usagePercentage
                ];
            }

            $result[] = [
                'clinic_id' => $clinic->id,
                'clinic_name' => $clinic->name,
                'patients_stats' => $clinicsPatientsStats[$clinic->id]['stats'] ?? [],
                'services' => $servicesUsage
            ];
        }

        return [
            'status' => true,
            'messages' => "Clinics statistics & services usage fetched successfully for year {$year}",
            'data' => $result
        ];
    }

    public function getAllSubscriptionsStats($year = null): array
    {
        $year = $year ?? now()->year;

        $types = [
            'clinics' => (new Clinic)->getMorphClass(),
            'ads' => (new Advertisment)->getMorphClass(),
        ];

        $result = [];

        foreach ($types as $key => $modelClass) {
            $subscriptions = DB::table('subscriptions')
                ->where('subscribable_type', $modelClass)
                ->whereYear('subscribed_at', $year)
                ->select(
                    DB::raw('YEAR(subscribed_at) as year'),
                    DB::raw('MONTH(subscribed_at) as month'),
                    DB::raw('COUNT(id) as total_subscriptions')
                )
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();

            $stats = [];
            $previousCount = 0;

            foreach ($subscriptions as $row) {
                $monthLabel = Carbon::create($row->year, $row->month)->format('M Y');

                $change = 0;
                $percentChange = 0;

                if ($previousCount !== 0) {
                    $change = $row->total_subscriptions - $previousCount;
                    $percentChange = $previousCount > 0
                        ? round(($change / $previousCount) * 100, 2)
                        : 100;
                }

                $stats[] = [
                    'month' => $monthLabel,
                    'total_subscriptions' => $row->total_subscriptions,
                    'change' => $change,
                    'percentage_change' => $percentChange,
                ];

                $previousCount = $row->total_subscriptions;
            }

            $result[$key] = $stats;
        }

        return [
            'status' => true,
            'messages' => "Clinics & Ads subscriptions statistics for year {$year} fetched successfully",
            'data' => $result,
        ];
    }

    public function getAnnualPaymentsStats(): array
    {
        $subscriptions = DB::table('subscriptions')
            ->select(
                DB::raw('YEAR(subscribed_at) as year'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(id) as total_subscriptions')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        if ($subscriptions->isEmpty()) {
            return [
                'status' => true, 'messages' => 'No payments found', 'data' => [],
            ];
        }

        $stats = [];
        $previousAmount = 0;

        foreach ($subscriptions as $row) {
            $change = 0;
            $percentChange = 0;

            if ($previousAmount !== 0) {
                $change = $row->total_amount - $previousAmount;
                $percentChange = $previousAmount > 0
                    ? round(($change / $previousAmount) * 100, 2)
                    : 100;
            }

            $stats[] = [
                'year' => $row->year,
                'total_amount' => round($row->total_amount, 2),
                'total_subscriptions' => $row->total_subscriptions,
                'change' => $change,
                'percentage_change' => $percentChange,
            ];

            $previousAmount = $row->total_amount;
        }

        return [
            'status' => true,
            'messages' => 'Annual payments statistics fetched successfully',
            'data' => $stats,
        ];
    }

    public function getUsersAnnualStats(): array
    {
        $users = DB::table('users')
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('COUNT(id) as total_users')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get();

        $stats = [];
        $previousCount = 0;

        foreach ($users as $row) {
            $change = 0;
            $percentChange = 0;

            if ($previousCount !== 0) {
                $change = $row->total_users - $previousCount;
                $percentChange = $previousCount > 0
                    ? round(($change / $previousCount) * 100, 2)
                    : 100;
            }

            $stats[] = [
                'year' => $row->year,
                'total_users' => $row->total_users,
                'change' => $change,
                'percentage_change' => $percentChange,
            ];

            $previousCount = $row->total_users;
        }

        return [
            'status' => true,
            'messages' => 'Users statistics fetched successfully',
            'data' => $stats,
        ];
    }

}
