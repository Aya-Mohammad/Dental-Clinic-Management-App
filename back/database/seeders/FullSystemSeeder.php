<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\City;
use App\Models\Street;
use App\Models\Clinic;
use App\Models\Service;
use App\Models\Treatment;
use App\Models\Advertisment;
use App\Models\Subscription;

class FullSystemSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // ---------------- Roles ----------------
        $roles = ['admin', 'manager', 'doctor', 'secretary', 'patient'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }

        // ---------------- Cities & Streets ----------------
        for ($i = 0; $i < 3; $i++) {
            $city = City::create(['name' => $faker->city]);
            for ($j = 0; $j < 3; $j++) {
                Street::create([
                    'name' => $faker->streetName,
                    'city_id' => $city->id
                ]);
            }
        }
        $streets = Street::all();

        // ---------------- Managers & Clinics ----------------
        for ($m = 1; $m <= 6; $m++) {
            $manager = User::firstOrCreate(
                ['email' => "manager$m@gmail.com"],
                [
                    'name' => "Manager $m",
                    'password' => Hash::make('password'),
                    'verified_at' => now(),
                    'profile_image' => 'https://via.placeholder.com/200x200.png/003388?text=Admin',
                    'fcm_token' => $faker->uuid,
                ]
            );
            $manager->roles()->syncWithoutDetaching([Role::where('name', 'manager')->first()->id]);

            for ($c = 1; $c <= 2; $c++) {
                $clinic = Clinic::create([
                    'name' => "Clinic {$m}-{$c}",
                    'phone' => "1000$m$c",
                    'bio' => "Bio for clinic {$m}-{$c}",
                    'street_id' => $streets->random()->id,
                    'user_id' => $manager->id,
                    'subscribed_at' => now()->subMonths(rand(1, 12)),
                    'subscription_duration_days' => 365,
                ]);

                // ---------------- Clinic Subscription ----------------
                Subscription::create([
                    'subscribable_id' => $clinic->id,
                    'subscribable_type' => Clinic::class,
                    'subscribed_at' => Carbon::create(rand(2023, 2025), rand(1, 12), rand(1, 28)),
                    'duration_days' => 365,
                    'amount' => rand(500, 1000),
                    'status' => 'active',
                ]);

                // ---------------- Ads ----------------
                $adsCount = rand(1, 3);
                $status = $faker->randomElement(['pending', 'approved', 'expired', 'rejected']);

                for ($a = 1; $a <= $adsCount; $a++) {
                    $ad = Advertisment::create([
                        'title' => "Ad {$clinic->id}-{$a}",
                        'description' => $faker->sentence,
                        'status' => $status,
                        'clinic_id' => $clinic->id,
                        'subscribed_at' => now()->subMonths(rand(1, 12)),
                        'subscription_duration_days' => 365,
                    ]);

                    Subscription::create([
                        'subscribable_id' => $ad->id,
                        'subscribable_type' => Advertisment::class,
                        'subscribed_at' => Carbon::create(rand(2023, 2025), rand(1, 12), rand(1, 28)),
                        'duration_days' => 365,
                        'amount' => rand(100, 500),
                        'status' => 'active',
                    ]);
                }

                // ---------------- Services & Stages ----------------
                $servicesPool = [
                    ['name' => 'General Checkup', 'stages' => 1, 'specialization' => 'G'],
                    ['name' => 'Teeth Cleaning', 'stages' => 1, 'specialization' => 'G'],
                    ['name' => 'Root Canal', 'stages' => 3, 'specialization' => 'C'],
                    ['name' => 'Tooth Filling', 'stages' => 2, 'specialization' => 'C'],
                    ['name' => 'Dental X-Ray', 'stages' => 1, 'specialization' => 'G'],
                    ['name' => 'Orthodontics', 'stages' => 4, 'specialization' => 'O'],
                ];

                $servicesData = collect($servicesPool)->shuffle()->take(rand(2,5))->toArray();

                foreach ($servicesData as $sd) {
                    $service = Service::firstOrCreate(
                        ['name' => $sd['name']],
                        [
                            'description' => $faker->sentence,
                            'duration' => $faker->numberBetween(30, 90),
                            'stages_number' => $sd['stages'],
                        ]
                    );

                    DB::table('clinic_service')->updateOrInsert([
                        'clinic_id' => $clinic->id,
                        'service_id' => $service->id,
                    ], [
                        'price' => $faker->randomFloat(2, 50, 500),
                        'accessibility' => 'A',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    for ($st = 1; $st <= $sd['stages']; $st++) {
                        $stageId = DB::table('stages')->insertGetId([
                            'title' => $service->name . " - Stage $st",
                            'duration' => gmdate('H:i:s', $faker->numberBetween(900, 3600)),
                            'specialization' => $sd['specialization'],
                            'description' => $faker->sentence,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('service_stage')->insert([
                            'service_id' => $service->id,
                            'stage_id' => $stageId,
                            'order' => $st,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // ---------------- Doctors ----------------
                $specializations = ['C', 'G', 'E', 'O'];

                for ($d = 1; $d <= rand(1, 3); $d++) {
                    $doctorUser = User::firstOrCreate(
                        ['email' => "doctor{$clinic->id}_$d@gmail.com"],
                        [
                            'name' => $faker->name,
                            'password' => Hash::make('password'),
                            'verified_at' => now(),
                        ]
                    );
                    $doctorUser->roles()->syncWithoutDetaching([Role::where('name','doctor')->first()->id]);

                    $doctorId = DB::table('doctors')->insertGetId([
                        'user_id' => $doctorUser->id,
                        'specialization' => $specializations[array_rand($specializations)],
                        'experience_years' => rand(1, 20),
                        'bio' => $faker->sentence,
                        'phone' => $faker->phoneNumber,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    DB::table('clinic_doctor')->insert([
                        'clinic_id' => $clinic->id,
                        'doctor_id' => $doctorId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                    // ---------------- Secretaries ----------------
                    for ($s = 1; $s <= rand(1, 2); $s++) {
                        $secretaryUser = User::firstOrCreate(
                            ['email' => "secretary{$clinic->id}_$s@gmail.com"],
                            [
                                'name' => $faker->name,
                                'password' => Hash::make('password'),
                                'verified_at' => now(),
                            ]
                        );
                        $secretaryUser->roles()->syncWithoutDetaching([Role::where('name','secretary')->first()->id]);

                        $secretaryId = DB::table('secretaries')->insertGetId([
                            'user_id' => $secretaryUser->id,
                            'bio' => $faker->sentence,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        DB::table('clinic_secretary')->insert([
                            'clinic_id' => $clinic->id,
                            'secretary_id' => $secretaryId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }

                    // ---------------- Patients, Treatments & Appointments ----------------
                    $years = [2023, 2024, 2025];
                    $months = range(1, 12);

                    foreach ($years as $year) {
                        foreach ($months as $month) {
                            $patientsCount = rand(1, 3);
                            for ($p = 1; $p <= $patientsCount; $p++) {
                                $createdDate = Carbon::create($year, $month, rand(1, 28));

                    $patient = User::firstOrCreate(
                        ['email' => "patient{$clinic->id}_{$year}_{$month}_$p@gmail.com"],
                        [
                            'name' => $faker->name,
                            'password' => Hash::make('password'),
                            'verified_at' => $createdDate,
                            'created_at' => $createdDate,
                            'updated_at' => $createdDate,
                        ]
                    );

                    $patient->roles()->syncWithoutDetaching([Role::where('name','patient')->first()->id]);

                    $treatmentServices = collect($servicesData)->shuffle()->take(rand(1, 3))->toArray();

                    $treatmentDate = Carbon::create($year, $month, rand(1, 28));

                    $treatment = Treatment::create([
                        'clinic_id' => $clinic->id,
                        'user_id' => $patient->id,
                        'services' => count($treatmentServices),
                        'service_number' => 1,
                        'status' => 'C',
                        'created_at' => $treatmentDate,
                        'updated_at' => $treatmentDate,
                    ]);

                        foreach ($treatmentServices as $ts) {
                            DB::table('service_treatment')->insert([
                                'treatment_id' => $treatment->id,
                                'service_id' => Service::where('name', $ts['name'])->first()->id,
                                'order' => 1,
                                'stage_number' => rand(1, 3),
                                'created_at' => $treatmentDate,
                                'updated_at' => $treatmentDate,
                            ]);
                        }

                $doctor = DB::table('clinic_doctor')
                            ->where('clinic_id', $clinic->id)
                            ->inRandomOrder()
                            ->first();

                    if ($doctor) {
                        foreach ($treatmentServices as $ts) {
                            $appointmentDate = Carbon::create($year, $month, rand(1, 28));
                            DB::table('appointments')->insert([
                                'clinic_id' => $clinic->id,
                                'treatment_id' => $treatment->id,
                                'user_id' => $patient->id,
                                'doctor_id' => $doctor->doctor_id,
                                'date' => $appointmentDate->format('Y-m-d'),
                                'time' => $faker->time(),
                                'duration' => '00:30:00',
                                'description' => $faker->sentence(),
                                'status' => fake()->randomElement(['C','UC','X']),
                                'created_at' => $appointmentDate,
                                'updated_at' => $appointmentDate,
                            ]);
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }
}
