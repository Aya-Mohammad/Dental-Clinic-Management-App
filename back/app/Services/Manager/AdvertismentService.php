<?php

namespace App\Services\Manager;

use App\Models\{
    User,
    Clinic, Advertisment, AdvertismentImages,
    Setting, Subscription,
    UserNotification
};

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Stripe\Stripe;
use Stripe\Checkout\Session;

use App\Traits\{Responses, RoleTrait, HasImageActions};

use App\Services\FirebaseService;
use App\Notifications\MailNotification;

class AdvertismentService
{
    use Responses, RoleTrait, HasImageActions;

    /** Store a new advertisement and its images in the database. */
    public function addAdvertisment($request, $data): array
    {
        $data = $request->validated();

        $clinic = Clinic::find($data['clinic_id']);

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return ['status' => false, 'message' => 'Clinic subscription not found', 'data' => null];
        }

        /** @var \App\Models\User $user */
        $user = auth()->user();
        $isManager = $user->roles()->where('name', 'manager')->exists();

        if ($isManager && $clinic->user_id !== $user->id) {
            return [ 'status' => false, 'message' => 'Unauthorized', 'data' => null ];
        }

        $duration = Setting::where('key', 'advertisement_duration_days')->value('value') ?? 15;
        $advertisementSetting = Setting::where('key', 'advertisement_price')->first();
        $amount = $advertisementSetting ? $advertisementSetting->value : 0;

        $uploadedImagePaths = [];
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('temp_ad_images', 'public');
                $uploadedImagePaths[] = ['path' => $path];
            }
        }

        $advertisement = Advertisment::create([
            'clinic_id' => $clinic->id,
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => 'pending',
            'subscription_duration_days' => $duration,
        ]);

        foreach ($uploadedImagePaths as $image) {
            $this->addImageFromPath($advertisement, $image['path'], AdvertismentImages::class, 'advertisment_id');
        }

        return [
            'status' => true,
            'message' => 'Advertisement submitted for approval',
            'data' => ['id' => $advertisement->id],
        ];
    }

    /** Create a Stripe checkout session for an advertisement payment. */
    public function createPaymentSession($request, $advertisement_id): array
    {
        $advertisement = Advertisment::find($advertisement_id);

        if (!$advertisement) {
            return [
                'status' => false,
                'message' => 'Advertisement not found',
                'data' => null,
            ];
        }

        if ($advertisement->status !== 'approved') {
            return [
                'status' => false,
                'message' => 'Advertisement must be approved first',
                'data' => null,
            ];
        }

        $amount = Setting::where('key', 'advertisement_price')->value('value') ?? 0;

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'sar',
                        'product_data' => ['name' => 'Clinic Advertisement Subscription'],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('advertisement.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'metadata' => [
                    'advertisement_id' => $advertisement->id,
                    'user_id' => $request->user()->id,
                ],
            ]);

            return [
                'status' => true,
                'message' => 'Checkout session created',
                'data' => ['checkout_url' => $session->url],
            ];

        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /** Update advertisement data (title, description, images). */
    public function updateAdvertisment($request): array
    {
        $ad = Advertisment::with('images', 'clinic')->find($request->advertisement_id);

        if (!$ad) {
            return ['status' => false, 'message' => 'Advertisement not found', 'data' => null];
        }

        if ($request->user()->id !== $ad->clinic->user_id) {
            return ['status' => false, 'message' => 'Unauthorized', 'data' => null,];
        }

        $clinic = $ad->clinic;

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return ['status' => false, 'message' => 'Clinic subscription not found','data' => null];
        }

        $clinicExpiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
        if (now()->greaterThanOrEqualTo($clinicExpiresAt)) {
            return ['status' => false, 'message' => 'Clinic subscription expired, cannot update advertisement','data' => null];
        }

        if (!$ad->subscription_duration_days) {
            return ['status' => false, 'message' => 'Advertisement subscription not found','data' => null];
        }

        $adExpiresAt = $ad->created_at->copy()->addDays($ad->subscription_duration_days);
        if (now()->greaterThanOrEqualTo($adExpiresAt)) {
            return ['status' => false, 'message' => 'Advertisement subscription expired, cannot update','data' => null];
        }

        $ad->title = $request->input('title', $ad->title);
        $ad->description = $request->input('description', $ad->description);
        $ad->status = 'pending';
        $ad->save();

        if ($request->hasFile('images')) {
            foreach ($ad->images as $oldImage) {
                Storage::disk('public')->delete($oldImage->path);
                $oldImage->delete();
            }

            foreach ($request->file('images') as $imageFile) {
                $path = $imageFile->store('advertisements', 'public');
                $this->addImageFromPath($ad, $path, AdvertismentImages::class, 'advertisment_id');
            }
        }

        return [
            'status' => true,
            'message' => 'Advertisement updated and awaiting admin approval again',
            'data' => [
                'id' => $ad->id,
                'status' => $ad->status,
            ],
        ];
    }

    /** Retrieve single advertisement by ID with its relations. */
    public function showAdvertisment(int $id): array //all
    {
        $ad = Advertisment::with(['images', 'clinic.street.city'])->find($id);

        if (!$ad) {
            return ['status' => false, 'message' => 'Advertisement not found', 'data' => null];
        }

        return [
            'status' => true,
            'message' => 'Advertisement retrieved successfully',
            'data' => $ad,
        ];
    }

    /** Get advertisements of a specific clinic (only if user owns the clinic). */
    public function getClinicAdvertisments(Request $request, int $clinic_id , $status): array //manager
    {
        $clinic = Clinic::find($clinic_id);

        if (!$clinic) {
            return ['status' => false,'message' => 'Clinic not found','data' => null];
        }

        if ($request->user()->id !== $clinic->user_id) {
            return ['status' => false, 'message' => 'Unauthorized access to clinic advertisements', 'data' => null];
        }

        $ads = Advertisment::with('images')->where('clinic_id', $clinic_id)->where('status', $status)->get();

        return [
            'status' => true,
            'message' => 'Clinic advertisements retrieved successfully',
            'data' => $ads,
        ];
    }

    /** Get all advertisements with clinic ID and name. */
    public function getAllAdvertismentsWithClinics(Request $request , $status): array //admin
    {
        $ads = Advertisment::with(['clinic:id,name', 'images'])->where('status' , $status)->get()->map(function ($ad) {
            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'status' => $ad->status,
                'clinic' => [
                    'id' => $ad->clinic->id,
                    'name' => $ad->clinic->name,
                ],
                'images' => $ad->images,
            ];
        });

        return [
            'status' => true,
            'message' => 'All advertisements retrieved successfully',
            'data' => $ads,
        ];
    }

    /** resend url for payment. */
    public function resendPaymentLinkForAd(Request $request , int $advertisementId, User $authUser): array
    {
        $ad = Advertisment::find($advertisementId);

        if (!$ad)  return ['status' => false, 'message' => 'Advertisement not found', 'data' => null];

        if ($ad->clinic->user_id !== $authUser->id)  return ['status' => false, 'message' => 'Unauthorized', 'data' => null];

        if ($ad->status !== 'approved' || $ad->subscribed_at)  return ['status' => false, 'message' => 'Payment link not required', 'data' => null];

        $paymentSession = $this->createPaymentSession(request(), $ad->id);
        $checkoutUrl = $paymentSession['data']['checkout_url'] ?? null;

        if (!$checkoutUrl)  return ['status' => false, 'message' => 'Could not generate payment link', 'data' => null];

        $title = 'Payment Link for Your Approved Advertisement';
        $body = 'Please complete your payment to activate the advertisement';

        $user = $ad->clinic->user;

        if (!empty($user->fcm_token)) {
            try {
                resolve(FirebaseService::class)->sendNotification(
                    $user->fcm_token,
                    $title,
                    $body,
                    [
                        'advertisement_id' => $ad->id,
                        'type' => 'advertisement_payment',
                        'checkout_url' => $checkoutUrl
                    ]
                );
            } catch (\Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
                $user->notify(new MailNotification(__('Advertisement'), $body, $title));
            }
        } else {
            $user->notify(new MailNotification(__('Advertisement'), $body, $title));
        }

        UserNotification::create([
            'type' => 'advertisement_payment',
            'title' => $title,
            'messages' => $body,
            'is_read' => false,
            'data' => json_encode([
                'advertisement_id' => $ad->id,
                'checkout_url' => $checkoutUrl
            ]),
            'user_id' => $user->id,
        ]);

        return [
            'status' => true,
            'message' => 'Payment link resent successfully',
            'data' => [
                'advertisement_id' => $ad->id,
                'checkout_url' => $checkoutUrl
            ]
        ];
    }

    /** renew advs subs */
    public function renewAdvertisment($request, $authUser): array
    {
        $ad = Advertisment::with('clinic')->find($request->advertisement_id);

        if (!$ad) {
            return ['status' => false, 'message' => 'Advertisement not found', 'data' => null, 'code' => 404];
        }

        if ($ad->clinic->user_id !== $authUser->id) {
            return ['status' => false, 'message' => 'Unauthorized', 'data' => null, 'code' => 403];
        }

        if ($ad->status !== 'expired') {
            return [
                'status' => false,
                'message' => 'Only expired advertisements can be renewed',
                'data' => ['current_status' => $ad->status],
                'code' => 422
            ];
        }

        $duration = Setting::where('key', 'advertisement_duration_days')->value('value') ?? 15;

        $ad->subscription_duration_days = $duration;
        $ad->created_at = now();
        $ad->status = 'pending';
        $ad->save();

        Subscription::updateOrCreate(
            [
                'subscribable_id' => $ad->id,
                'subscribable_type' => Advertisment::class,
            ],
            [
                'subscribed_at' => now(),
                'duration_days' => $duration,
                'amount' => Setting::where('key','advertisement_price')->value('value') ?? 0,
                'status' => 'active',
            ]
        );

        return [
            'status' => true,
            'message' => 'Advertisement renewed and awaiting admin approval',
            'data' => [
                'id' => $ad->id,
                'status' => $ad->status,
                'expires_at' => $ad->created_at->copy()->addDays($duration),
            ],
            'code' => 200
        ];
    }

}
