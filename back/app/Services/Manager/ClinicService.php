<?php

namespace App\Services\Manager;

use App\Models\{
    City, Street,
    Clinic, ClinicDoctor, ClinicImages, ClinicSecretary,
    Role, User, Secretary, Subscription,
    Setting, WorkingHour, PatientData, Treatment
};

use App\Traits\HasImageActions;
use App\Traits\Responses;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

use Stripe\Checkout\Session;
use Stripe\Stripe;
use Illuminate\Database\Eloquent\Builder;

class ClinicService
{
    use HasImageActions;

    /**Create clinic record in database.*/
    public function createClinic(array $attributes, User $user): Clinic
    {
        $attributes['user_id'] = $user->id;
        return Clinic::create($attributes);
    }

    /** Create Clinic + Stripe Checkout Session */
    public function createClinicWithPayment(array $attributes, User $user): array
    {
        try {
            $amount = (int) Setting::where('key', 'subscription_price')->value('value') ?? 0;

            $city   = City::firstOrCreate(['name' => $attributes['city_name']]);
            $street = Street::firstOrCreate([
                'name'    => $attributes['street_name'],
                'city_id' => $city->id,
            ]);

            $attributes['street_id'] = $street->id;
            unset($attributes['city_name'], $attributes['street_name'], $attributes['working_hours']);

            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::create([
                'mode'  => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'sar',
                        'product_data' => ['name' => 'Clinic Subscription'],
                        'unit_amount'  => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('clinic.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('payment.cancel'),
                'metadata'    => [
                    'user_id'     => $user->id,
                    'clinic_data' => json_encode($attributes),
                ],
            ]);

            $managerRoleId = Role::where('name', 'manager')->value('id');
            $user->roles()->syncWithoutDetaching([$managerRoleId]);

            return [
                'status'  => true,
                'message' => 'clinic.checkout_created',
                'data'    => ['checkout_url' => $session->url]
            ];
        } catch (\Throwable $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
                'data'    => null
            ];
        }
    }

    /** Update clinic info */
    public function update(array $attributes, User $user): array
    {
        $clinic = Clinic::find($attributes['clinic_id']);

        if (!$clinic || $clinic->user_id !== $user->id) {
            return ['status' => false, 'message' => 'unauthorized', 'data' => null];
        }

        if ($user->hasRole('manager')) {
            if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
                return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
            }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return ['status' => false, 'message' => 'Clinic subscription expired', 'data' => null];
            }
        }

        if (!empty($attributes['city']) && !empty($attributes['street'])) {
            $city = City::firstOrCreate(['name' => $attributes['city']]);
            $street = Street::firstOrCreate(['name' => $attributes['street'], 'city_id' => $city->id]);
            $attributes['street_id'] = $street->id;
        }

        $clinic->update(collect($attributes)->only('name', 'bio', 'phone', 'street_id')->toArray());

        return [
            'status'  => true,
            'message' => 'clinic updated successfully',
            'data'    => $this->transform($clinic->fresh('street.city'))
        ];
    }

    /** renew clinic subs */
    public function renewClinicSubscription(array $attributes, User $user): array
    {
        $clinic = Clinic::find($attributes['clinic_id']);

        if (!$clinic || $clinic->user_id !== $user->id) {
            return ['status' => false, 'message' => 'Unauthorized', 'data' => null];
        }

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
        }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);

        if (now()->lt($expiresAt)) {
            return [
                'status' => false,
                'message' => 'Clinic subscription is still active, cannot renew yet',
                'data' => ['expires_at' => $expiresAt],
                'code' => 422
            ];
        }

        $duration = Setting::where('key', 'subscription_duration_days')->value('value') ?? 365;

        $clinic->subscribed_at = now();
        $clinic->subscription_duration_days = $duration;
        $clinic->save();

        $amount = Setting::where('key', 'subscription_price')->value('value') ?? 0;

        Subscription::updateOrCreate(
            [
                'subscribable_id' => $clinic->id,
                'subscribable_type' => Clinic::class,
            ],
            [
                'subscribed_at' => now(),
                'duration_days' => $duration,
                'amount' => $amount,
                'status' => 'active',
            ]
        );

        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::create([
                'mode' => 'payment',
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'sar',
                        'product_data' => ['name' => 'Clinic Subscription Renewal'],
                        'unit_amount' => $amount * 100,
                    ],
                    'quantity' => 1,
                ]],
                'success_url' => route('clinic.payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('payment.cancel'),
                'metadata' => [
                    'user_id' => $user->id,
                    'clinic_id' => $clinic->id,
                ],
            ]);

            return [
                'status'  => true,
                'message' => 'Clinic subscription renewed. Complete payment to activate.',
                'data'    => ['checkout_url' => $session->url, 'expires_at' => $clinic->subscribed_at->copy()->addDays($duration)]
            ];

        } catch (\Throwable $e) {
            return [
                'status' => false,
                'message' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /** Add staff to clinic */
    public function addStaff(array $data): ?array
    {
        $user = User::where('email', $data['email_or_phone'])
            ->orWhere('number', $data['email_or_phone'])
            ->first();

        if (!$user)  return ['status' => false, 'message' => 'User not found', 'data' => null];

        $clinic = Clinic::find($data['clinic_id']);
        if (!$clinic || $clinic->user_id !== auth()->id())  return ['status' => false, 'message' => 'Unauthorized', 'data' => null];

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
        }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
        if (now()->greaterThanOrEqualTo($expiresAt)) {
            return ['status' => false, 'message' => 'Clinic subscription expired', 'data' => null];
        }

        $role = Role::where('name', $data['role'])->first();
        if ($role)  $user->roles()->syncWithoutDetaching($role->id);

        match ($data['role']) {
            'doctor'    => $this->addDoctor($user, $clinic, $data['working_hours']),
            'secretary' => $this->addSecretary($user, $clinic, $data['working_hours']),
            default     => null
        };

        $staffDetails = null;
        if ($data['role'] === 'doctor') {
            $staffDetails = DB::table('doctors')->where('user_id', $user->id)->first();
        } elseif ($data['role'] === 'secretary') {
            $staffDetails = DB::table('secretaries')->where('user_id', $user->id)->first();
        }
        $clinicWorkingHours = $this->calculateClinicWorkingHours($clinic);

        DB::table('clinic_working_hour')->where('clinic_id', $clinic->id)->delete();

        foreach ($clinicWorkingHours as $day => $periods) {
            foreach ($periods as $range) {
                // if (!isset($range['start']) || !isset($range['end'])) {
                //     continue;
                // }

                $workingHour = WorkingHour::firstOrCreate([
                    'start' => $range['start'],
                    'end'   => $range['end'],
                ]);

                DB::table('clinic_working_hour')->insert([
                    'clinic_id'       => $clinic->id,
                    'working_hour_id' => $workingHour->id,
                    'working_day'     => $day,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        $user = $user->fresh()->load('roles');

        return [
            'status'  => true,
            'message' => __('staff added successfully'),
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'image' => $user->profile_image,
                    'roles' => $user->roles->pluck('name')
                ],
                'staff_details' => $staffDetails,
                'clinic_working_hours' => $clinicWorkingHours
            ]
        ];
    }

    /** Update staff working hour */
    public function updateStaffWorkingHours(array $data): array
    {
        $user = User::find($data['user_id']);
        if (!$user)  return ['status' => false, 'message' => 'User not found', 'data' => null];

        $role = $data['role'];
        // if (!in_array($role, ['doctor', 'secretary'])) {
        //     return ['status' => false, 'message' => 'Only doctor or secretary roles are supported', 'data' => null];
        // }

        if (!$user->roles()->where('name', $role)->exists()) {
            return ['status' => false, 'message' => "user_does_not_have_role", ['role' => $role], 'data' => null];
        }

        $clinicId = $data['clinic_id'];
        $clinic   = Clinic::find($clinicId);
        if (!$clinic) return ['status' => false, 'message' => 'Clinic not found', 'data' => null];

        if ($user->hasRole('manager')) {
            if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
                return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
            }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return ['status' => false, 'message' => 'Clinic subscription expired', 'data' => null];
            }
        }

        // ===== Update Doctor Hours =====
        if ($role === 'doctor') {
            $doctor = DB::table('doctors')->where('user_id', $user->id)->first();
            if (!$doctor) {
                $doctorId = DB::table('doctors')->insertGetId([
                    'user_id' => $user->id,
                    'specialization' => 'G',
                    'experience_years' => 1,
                    'bio' => 'Auto-generated bio',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $doctor = DB::table('doctors')->where('id', $doctorId)->first();
            }

            $clinicDoctor = DB::table('clinic_doctor')
                ->where('clinic_id', $clinicId)
                ->where('doctor_id', $doctor->id)
                ->first();

            if (!$clinicDoctor) {
                $clinicDoctorId = DB::table('clinic_doctor')->insertGetId([
                    'clinic_id' => $clinicId,
                    'doctor_id' => $doctor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $clinicDoctor = DB::table('clinic_doctor')->where('id', $clinicDoctorId)->first();
            }

            DB::table('clinic_doctor_working_hour')
                ->where('clinic_doctor_id', $clinicDoctor->id)
                ->delete();

            foreach ($data['working_hours'] as $wh) {
                $workingHour = WorkingHour::firstOrCreate([
                    'start' => $wh['start'],
                    'end' => $wh['end'],
                ]);

                DB::table('clinic_doctor_working_hour')->insert([
                    'clinic_doctor_id' => $clinicDoctor->id,
                    'working_hour_id' => $workingHour->id,
                    'working_day' => $wh['day'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        // ===== Update Secretary Hours =====
        else {
            $secretary = DB::table('secretaries')->where('user_id', $user->id)->first();
            if (!$secretary) {
                $secretaryId = DB::table('secretaries')->insertGetId([
                    'user_id' => $user->id,
                    'clinic_id' => $clinicId,
                    'bio' => 'Auto-generated bio',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $secretary = DB::table('secretaries')->where('id', $secretaryId)->first();
            }

            $clinicSecretary = DB::table('clinic_secretary')
                ->where('clinic_id', $clinicId)
                ->where('secretary_id', $secretary->id)
                ->first();

            if (!$clinicSecretary) {
                $clinicSecretaryId = DB::table('clinic_secretary')->insertGetId([
                    'clinic_id' => $clinicId,
                    'secretary_id' => $secretary->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $clinicSecretary = DB::table('clinic_secretary')->where('id', $clinicSecretaryId)->first();
            }

            DB::table('clinic_secretary_working_hour')
                ->where('clinic_secretary_id', $clinicSecretary->id)
                ->delete();

            foreach ($data['working_hours'] as $wh) {
                $workingHour = WorkingHour::firstOrCreate([
                    'start' => $wh['start'],
                    'end' => $wh['end'],
                ]);

                DB::table('clinic_secretary_working_hour')->insert([
                    'clinic_secretary_id' => $clinicSecretary->id,
                    'working_hour_id' => $workingHour->id,
                    'working_day' => $wh['day'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $staffDetails = null;
        if ($role === 'doctor') {
            $staffDetails = DB::table('doctors')->where('user_id', $user->id)->first();
        } elseif ($role === 'secretary') {
            $staffDetails = DB::table('secretaries')->where('user_id', $user->id)->first();
        }
        // === Recalculate clinic working hours ===
        $clinicWorkingHours = $this->calculateClinicWorkingHours($clinic);
        DB::table('clinic_working_hour')->where('clinic_id', $clinic->id)->delete();

        foreach ($clinicWorkingHours as $day => $ranges) {
            foreach ($ranges as $range) {
                $workingHour = WorkingHour::firstOrCreate([
                    'start' => $range['start'],
                    'end'   => $range['end'],
                ]);

                DB::table('clinic_working_hour')->insert([
                    'clinic_id'       => $clinic->id,
                    'working_hour_id' => $workingHour->id,
                    'working_day'     => $day,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }

        return [
            'status'  => true,
            'message' => 'working hours updated successfully',
            'data'    => [
                'user'  => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'image' => $user->profile_image,
                    'roles' => $user->roles->pluck('name')
                ],
                'staff_details' => $staffDetails,
                'clinic_working_hours' => $clinicWorkingHours
            ]
        ];
    }

    /** Add clinic image */
    public function addClinicImage(int $clinicId, UploadedFile $file): array
    {
        $clinic = Clinic::find($clinicId);
        $user   = auth()->user();

        if (!$clinic || $user->id !== $clinic->user_id) {
            return ['status' => false, 'message' => 'Unauthorized', 'data' => null];
        }


            if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
                return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
            }

            $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return ['status' => false, 'message' => 'Clinic subscription expired', 'data' => null];
            }


        $img = $this->addImage($clinic, $file, ClinicImages::class, 'clinic_id', 'clinic_images');

        return [
            'status'  => true,
            'message' => 'Image added',
            'data'    => [
                'image' => [
                    'id'   => $img->id,
                    'path' => $img->path,
                ]
            ]
        ];
    }

    /** Delete clinic image */
    public function deleteImage(int $imageId): array
    {
        $img   = ClinicImages::find($imageId);
        $user  = auth()->user();

        if (!$img || $user->id !== $img->clinic->user_id) {
            return ['status' => false, 'message' => 'Unauthorized.', 'data' => null];
        }

        $clinic = $img->clinic;

            if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
                return ['status' => false, 'message' => 'Subscription not found', 'data' => null];
            }

            $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
            if (now()->greaterThanOrEqualTo($expiresAt)) {
                return ['status' => false, 'message' => 'Clinic subscription expired', 'data' => null];
            }

        $this->deleteImageById($imageId, ClinicImages::class);

        return [
            'status'  => true,
            'message' => 'Image deleted',
            'data'    => null
        ];
    }

    /** Get Clinic Images */
    public function getClinicImages(int $clinicId): array
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic)  return ['status'  => false, 'message' => 'Clinic not found', 'data' => null];

        $images = $clinic->clinicImages()->get(['id', 'path']);

        return ['message' => 'Images retrieved successfully', 'data' => ['images' => $images]];
    }

    /**  Get clinic doctore */
    public function getClinicDoctors(User $user, int $clinicId): array
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic || $clinic->user_id !== $user->id)  return ['status' => false, 'message' => 'Unauthorized or clinic not found', 'data' => null ];

        $doctors = $clinic->doctors()
        ->with('user:id,name,email')
        ->get()
        ->map(function ($doctor) use ($clinicId) {
            $clinicDoctorPivot = $doctor->clinicDoctors()->where('clinic_id', $clinicId)->first();
            $workingHours = $clinicDoctorPivot
                ? $clinicDoctorPivot->workingHours->map(function ($wh) {
                    return [
                        'day'   => $wh->pivot->working_day,
                        'start' => $wh->start,
                        'end'   => $wh->end,
                    ];
                })
                : collect();

            return [
                'id' => $doctor->id,
                'name' => $doctor->user->name ?? null,
                'phone' => $doctor->phone,
                'bio' => $doctor->bio,
                'experience_years' => $doctor->experience_years,
                'specialization' => $doctor->specialization,
                'clinic_id' => $clinicId,
                'created_at' => $doctor->created_at,
                'updated_at' => $doctor->updated_at,
                'working_hours' => $workingHours,
                'user' => [
                    'id' => $doctor->user->id,
                    'name' => $doctor->user->name,
                    'email' => $doctor->user->email,
                    'number' => $doctor->user->number,
                    'profile_image' => $doctor->user->profile_image,

                ]
            ];
        });

        return [
            'status' => true,
            'message' => 'Doctors fetched successfully',
            'data' => ['doctors' => $doctors]
        ];
    }

    /** Get clinic secretaries */
    public function getClinicSecretaries(User $user, int $clinicId): array
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic || $clinic->user_id !== $user->id)  return ['status' => false, 'message' => 'Unauthorized or clinic not found', 'data' => null ];

        $secretaries = $clinic->secretaries()
        ->with('user:id,name,email,number,profile_image')
        ->get()
        ->map(function ($sec) use ($clinicId) {
            $clinicSecretaryPivot = $sec->clinicSecretary()->where('clinic_id', $clinicId)->first();
            $workingHours = $clinicSecretaryPivot
                ? $clinicSecretaryPivot->workingHours->map(function ($wh) {
                    return [
                        'day'   => $wh->pivot->working_day,
                        'start' => $wh->start,
                        'end'   => $wh->end,
                    ];
                })
                : collect();

            return [
                'id' => $sec->id,
                'name' => $sec->user->name ?? null,
                'email' => $sec->user->email ?? null,
                'phone' => $sec->phone ?? null,
                'bio' => $sec->bio,
                'created_at' => $sec->created_at,
                'updated_at' => $sec->updated_at,
                'working_hours' => $workingHours,
                'user' => [
                    'id' => $sec->user->id,
                    'name' => $sec->user->name,
                    'email' => $sec->user->email,
                    'number' => $sec->user->number,
                    'profile_image' => $sec->user->profile_image,
                ]
            ];
        });

        return [
            'status' => true,
            'message' => 'Secretaries fetched successfully',
            'data' => ['secretaries' => $secretaries]
        ];
    }

    /** Show Clinic Details */
    public function show(int $clinicId): array
    {
        $clinic = Clinic::with(['street.city', 'workingHours', 'clinicImages', 'reviews.user'])->find($clinicId);

        if (!$clinic) return ['status'  => false, 'message' => 'Clinic not found', 'data' => null ];

        $data = [
            'id'         => $clinic->id,
            'name'       => $clinic->name,
            'phone'      => $clinic->phone,
            'bio'        => $clinic->bio,
            'street'     => optional($clinic->street)->name,
            'city'       => optional(optional($clinic->street)->city)->name,
            'working_hours' => $clinic->workingHours->map(fn($wh) => [
                'day'   => $wh->pivot->working_day,
                'start' => $wh->start,
                'end'   => $wh->end,
            ]),
            'reviews' => $clinic->reviews->map(fn($review) => [
                'id'       => $review->id,
                'rating'   => $review->rating,
                'comment'  => $review->comment,
                'user'     => [
                    'id'   => $review->user->id,
                    'name' => $review->user->name
                ],
                'treatment_id' => $review->treatment_id,
                'created_at'   => $review->created_at->toDateTimeString(),
             ]),
            'subscribed_at' => $clinic->subscribed_at,
            'duration' => $clinic->subscription_duration_days
        ];

        return [
            'status'  => true,
            'message' => 'Clinic retrieved successfully',
            'data'    => $data
        ];
    }

    /** Return my clinics */
    public function myClinics(User $user): array
    {
        $clinics = Clinic::where('user_id', $user->id)
            ->with('street:id,name,city_id', 'street.city:id,name' , 'reviews.user')
            ->get()
            ->map(function ($clinic) {
                return [
                    'id'          => $clinic->id,
                    'name'        => $clinic->name,
                    'street' => optional($clinic->street)->name,
                    'city'   => optional($clinic->street->city)->name,
                    'reviews' => $clinic->reviews->map(fn($review) => [
                        'id'       => $review->id,
                        'rating'   => $review->rating,
                        'comment'  => $review->comment,
                        'user'     => [
                            'id'   => $review->user->id,
                            'name' => $review->user->name
                        ],
                        'treatment_id' => $review->treatment_id,
                        'created_at'   => $review->created_at->toDateTimeString(),
                    ])
                ];
            });

        return [
         'status' => true,
         'message' => 'Clinics fetched',
         'data' => $clinics];
    }

    /** Return paginated clinics */
    public function allClinics(?User $user = null): array
    {
        /** @var Builder|Clinic $query */
        $query = Clinic::query()->with('street.city');

        $data = $user
            ? $query->get()
            : $query->paginate(10);

        $result = $data instanceof \Illuminate\Pagination\LengthAwarePaginator
            ? tap($data, fn($c) => $c->getCollection()->transform(fn($i) => $this->transform($i)))
            : $data->map(fn($i) => $this->transform($i));

        return [
            'status'  => true,
            'message' => 'Clinics fetched',
            'data'    => $result,
        ];
    }
    use Responses;
    /** Search */
    // public function searchPatients(array $attributes)
    // {
    //     $keyword  = $attributes['keyword'] ?? null;
    //     $clinicId = $attributes['clinic_id'];

    //     $treatments = Treatment::where('clinic_id', $clinicId)->get();
    //     $userIds   = $treatments->pluck('user_id')->unique();

    //     $usersQuery = User::with(['patientData.street.city'])
    //         ->whereIn('id', $userIds);

    //     if ($keyword) {
    //         $usersQuery->where('name', 'like', "%{$keyword}%");
    //     }

    //     $users = $usersQuery->get()->map(function ($user) {
    //         $patient = $user->patientData;
    //         return [
    //             'id'          => $user->id,
    //             'name'        => $user->name,
    //             'email'       => $user->email,
    //             'gender'      => $patient->gender ?? null,
    //             'dateOfBirth' => $patient->date_of_birth ?? null,
    //             'bloodType'   => $patient->blood_type ?? null,
    //             'street'      => optional($patient->street)->name ?? null,
    //             'city'        => optional(optional($patient->street)->city)->name ?? null,
    //         ];
    //     });

    //     return [
    //         'status'  => true,
    //         'message' => 'Patients sent',
    //         'data'    => $users,
    //     ];
    // }

    public function searchPatients(array $attributes)
    {
        $keyword   = $attributes['keyword'] ?? null; 
        $clinicId  = $attributes['clinic_id']; 
        $treatments = Treatment::where('clinic_id', '=', $clinicId)->get();
        $ids = [];
        foreach ($treatments as $treatment) {
            $ids[] = $treatment->user_id;
        }
        $ids = array_unique($ids);
        $usersQuery = User::whereIn('id', $ids);
        if ($keyword) {
            $usersQuery->where('name', 'like', '%' . $keyword . '%');
        }
        $users = $usersQuery->get();
        return $this->success('Users retrieved successfully', $users);
    }

    /*======== PRIVATE ========*/

    private function transform(Clinic $c): array
    {
        return [
            'id'    => $c->id,
            'name'  => $c->name,
            'bio'   => $c->bio,
            'phone'  => $c->phone,
            'street' => optional($c->street)->name,
            'city'  => optional(optional($c->street)->city)->name
        ];
    }

    private function addDoctor(User $user, Clinic $clinic, array $hours): void
    {
        $doctor = $user->doctor()->firstOrCreate(
            ['user_id' => $user->id],
            ['specialization' => 'G', 'experience_years' => 1, 'bio' => 'Auto-generated']
        );

        $clinicDoctor = ClinicDoctor::firstOrCreate(['clinic_id' => $clinic->id, 'doctor_id' => $doctor->id]);

        foreach ($hours as $wh) {
            $w = WorkingHour::firstOrCreate(['start' => $wh['start'], 'end' => $wh['end']]);
            $clinicDoctor->workingHours()->attach($w->id, ['working_day' => $wh['day']]);
        }
    }

    private function addSecretary(User $user, Clinic $clinic, array $hours): void
    {
        $secretary = Secretary::firstOrCreate(
            ['user_id' => $user->id],
            ['bio' => 'Auto-generated']
        );

        $clinicSecretary = ClinicSecretary::firstOrCreate(['clinic_id' => $clinic->id, 'secretary_id' => $secretary->id]);

        foreach ($hours as $wh) {
            $w = WorkingHour::firstOrCreate(['start' => $wh['start'], 'end' => $wh['end']]);
            $clinicSecretary->workingHours()->attach($w->id, ['working_day' => $wh['day']]);
        }
    }

    public function calculateClinicWorkingHours(Clinic $clinic): array
    {
        $doctorHours = DB::table('clinic_doctor_working_hour')
            ->join('clinic_doctor', 'clinic_doctor_working_hour.clinic_doctor_id', '=', 'clinic_doctor.id')
            ->join('working_hours', 'clinic_doctor_working_hour.working_hour_id', '=', 'working_hours.id')
            ->where('clinic_doctor.clinic_id', $clinic->id)
            ->select('clinic_doctor_working_hour.working_day as day', 'working_hours.start', 'working_hours.end')
            ->get();

        $secretaryHours = DB::table('clinic_secretary_working_hour')
            ->join('clinic_secretary', 'clinic_secretary_working_hour.clinic_secretary_id', '=', 'clinic_secretary.id')
            ->join('working_hours', 'clinic_secretary_working_hour.working_hour_id', '=', 'working_hours.id')
            ->where('clinic_secretary.clinic_id', $clinic->id)
            ->select('clinic_secretary_working_hour.working_day as day', 'working_hours.start', 'working_hours.end')
            ->get();

        $allHours = $doctorHours->merge($secretaryHours);
        $grouped = $allHours->groupBy('day');

        $result = [];

        foreach ($grouped as $day => $hours) {
            $periods = [];
            foreach ($hours as $hour) {
                $start = strtotime($hour->start);
                $end = strtotime($hour->end);
                $periods[] = ['start' => $start, 'end' => $end];
            }

            usort($periods, fn($a, $b) => $a['start'] <=> $b['start']);

            $merged = [];
            foreach ($periods as $period) {
                if (empty($merged)) {
                    $merged[] = $period;
                    continue;
                }

                $last = &$merged[count($merged) - 1];
                if ($period['start'] <= $last['end']) {
                    $last['end'] = max($last['end'], $period['end']);
                } else {
                    $merged[] = $period;
                }
            }

            $result[$day] = array_map(function ($range) {
                return [
                    'start' => date('H:i:s', $range['start']),
                    'end'   => date('H:i:s', $range['end']),
                ];
            }, $merged);
        }

        return $result;
    }
}
