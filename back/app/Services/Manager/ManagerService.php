<?php

namespace App\Services\Manager;

use App\Models\User;
use App\Models\Clinic;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\UserNotification;
use Carbon\Carbon;

class ManagerService
{
    /** update manager profile */
    public function updateProfile(User $manager, array $data): array
    {
        $manager->name = $data['name'];
        $manager->email = $data['email'] ?? $manager->email;
        $manager->number = $data['number'] ?? $manager->number;

        if (!empty($data['password'])) {
            $manager->password = Hash::make($data['password']);
        }

        if (!empty($data['profile_image'])) {
            if ($manager->profile_image) {
                Storage::disk('public')->delete($manager->profile_image);
            }
            $path = $data['profile_image']->store('profiles', 'public');
            $manager->profile_image = $path;
        }

        $manager->save();

        return [
            'status' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $manager->id,
                'name' => $manager->name,
                'email' => $manager->email,
                'number' => $manager->number,
                'profile_image' => $manager->profile_image,
            ],
        ];
    }

    /** block & unblock users */
    public function blockUser(User $user, int $clinicId, User $manager): array
    {
        $clinic = Clinic::where('id', $clinicId)
                    ->where('user_id', $manager->id)
                    ->first();

        if (!$clinic) {
            return [
                'status' => false,
                'message' => 'You are not authorized to block users in this clinic',
            ];
        }

        $isBlocked = DB::table('blocks')
            ->where('clinic_id', $clinicId)
            ->where('user_id', $user->id)
            ->exists();

        if ($isBlocked) {
            return [
                'status' => false,
                'message' => 'User is already blocked in this clinic',
            ];
        }

        DB::table('blocks')->insert([
            'clinic_id' => $clinicId,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return [
            'status' => true,
            'message' => 'User blocked successfully for this clinic',
        ];
    }

    public function unblockUser(User $user, int $clinicId, User $manager): array
    {
        $clinic = Clinic::where('id', $clinicId)
                    ->where('user_id', $manager->id)
                    ->first();

        if (!$clinic) {
            return [
                'status' => false,
                'message' => 'You are not authorized to unblock users in this clinic',
            ];
        }

        $isBlocked = DB::table('blocks')
            ->where('clinic_id', $clinicId)
            ->where('user_id', $user->id)
            ->exists();

        if (!$isBlocked) {
            return [
                'status' => false,
                'message' => 'User is not blocked in this clinic',
            ];
        }

        DB::table('blocks')
            ->where('clinic_id', $clinicId)
            ->where('user_id', $user->id)
            ->delete();

        return [
            'status' => true,
            'message' => 'User unblocked successfully for this clinic',
        ];
    }

    public function getBlockedUsers(User $manager, int $clinicId): array
    {
        $clinic = Clinic::where('id', $clinicId)
            ->where('user_id', $manager->id)
            ->first();

        if (!$clinic) {
            return [
                'status' => false,
                'message' => 'You do not own this clinic or it does not exist',
                'data' => []
            ];
        }

        $blockedUsers = DB::table('blocks')
            ->join('users', 'blocks.user_id', '=', 'users.id')
            ->join('clinics', 'blocks.clinic_id', '=', 'clinics.id')
            ->where('blocks.clinic_id', $clinic->id)
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'users.email',
                'users.number',
                'clinics.id as clinic_id',
                'clinics.name as clinic_name',
                'blocks.created_at as blocked_at'
            )
            ->get();

        return [
            'status' => true,
            'message' => 'List of blocked users for this clinic',
            'data' => $blockedUsers
        ];
    }

    /** statistics */
    public function getClinicPatientsStats(int $clinicId, $year = null): array
    {
        $userId = auth()->id();
        $year = $year ?? now()->year;

        $clinic = DB::table('clinics')
            ->where('id', $clinicId)
            ->where('user_id', $userId)
            ->first();

        if (!$clinic) {
            return [
                'status' => false,
                'messages' => __('You do not own this clinic'),
                'data' => []
            ];
        }

        $appointmentsData = DB::table('appointments')
            ->where('clinic_id', $clinicId)
            ->whereYear('created_at', $year)
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('COUNT(DISTINCT user_id) as total_patients')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $stats = [];
        $previousCount = 0;

        foreach ($appointmentsData as $row) {
            $monthLabel = Carbon::create($row->year, $row->month)->format('M Y');

            $change = $percentChange = 0;
            if ($previousCount !== 0) {
                $change = $row->total_patients - $previousCount;
                $percentChange = $previousCount > 0 ? round(($change / $previousCount) * 100, 2) : 100;
            }

            $stats[] = [
                'month' => $monthLabel,
                'patients_count' => $row->total_patients,
                'change' => $change,
                'percentage_change' => $percentChange
            ];

            $previousCount = $row->total_patients;
        }

        return [
            'status' => true,
            'messages' => __('Patients statistics fetched successfully'),
            'data' => [
                'clinic_id' => $clinicId,
                'stats' => $stats
            ]
        ];
    }

    public function getClinicServicesUsage(int $clinicId, $year = null): array
    {
        $userId = auth()->id();
        $year = $year ?? now()->year;

        $clinic = DB::table('clinics')
            ->where('id', $clinicId)
            ->where('user_id', $userId)
            ->first();

        if (!$clinic) {
            return [
                'status' => false,
                'messages' => __('You do not own this clinic'),
                'data' => []
            ];
        }

        $serviceCounts = DB::table('appointments')
            ->join('treatments', 'appointments.treatment_id', '=', 'treatments.id')
            ->join('service_treatment', 'treatments.id', '=', 'service_treatment.treatment_id')
            ->join('services', 'service_treatment.service_id', '=', 'services.id')
            ->where('treatments.clinic_id', $clinicId)
            ->whereYear('appointments.created_at', $year)
            ->select(
                'services.id as service_id',
                'services.name as service_name',
                DB::raw('COUNT(appointments.id) as usage_count')
            )
            ->groupBy('services.id', 'services.name')
            ->get();

        $totalUsage = $serviceCounts->sum('usage_count');
        $servicesUsage = [];

        foreach ($serviceCounts as $service) {
            $servicesUsage[] = [
                'service_id' => $service->service_id,
                'service_name' => $service->service_name,
                'usage_count' => $service->usage_count,
                'usage_percentage' => $totalUsage > 0 ? round(($service->usage_count / $totalUsage) * 100, 2) : 0
            ];
        }

        return [
            'status' => true,
            'messages' => __('Services usage fetched successfully'),
            'data' => [
                'clinic_id' => $clinicId,
                'clinic_name' => $clinic->name,
                'services' => $servicesUsage
            ]
        ];
    }

    /** notifications */
    public function getUserNotifications()
    {
        $userId = Auth::id();
        $notifications = UserNotification::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        UserNotification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return [
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'data' => $notifications
        ];
    }


}
