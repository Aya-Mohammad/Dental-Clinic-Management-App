<?php

namespace App\Http\Controllers\Manager;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Manager\ManagerService;
use App\Http\Requests\Manager\UpdateManagerProfileRequest;
use App\Models\User;

class ManagerController extends Controller
{
    protected $service;

    public function __construct(ManagerService $service)
    {
        $this->service = $service;
    }

    public function updateProfile(UpdateManagerProfileRequest $request)
    {
        $manager = auth()->user();
        $data = $request->validated();
        return $this->service->updateProfile($manager, $data);
    }

    public function blockUser(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'clinic_id' => 'required|exists:clinics,id',
        ]);
        $user = User::findOrFail($request->id);
        $clinicId = $request->clinic_id;
        $manager = $request->user();
        return response()->json(
            $this->service->blockUser($user, $clinicId, $manager)
        );
    }

    public function unblockUser(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:users,id',
            'clinic_id' => 'required|exists:clinics,id',
        ]);
        $user = User::findOrFail($request->id);
        $clinicId = $request->clinic_id;
        $manager = $request->user();
        return response()->json(
            $this->service->unblockUser($user, $clinicId, $manager)
        );
    }

    public function getBlockedUsers(Request $request)
    {
        $request->validate([
            'clinic_id' => 'required|exists:clinics,id'
        ]);
        $clinicId = $request->input('clinic_id');
        $manager = $request->user();
        $response = $this->service->getBlockedUsers($manager, $clinicId);
        return response()->json($response);
    }

    public function patientsStats(Request $request)
    {
        $clinicId = $request->input('clinic_id');
        $year = $request->input('year');
        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'messages' => 'clinic_id is required',
                'data' => []
            ]);
        }
        return response()->json(
            $this->service->getClinicPatientsStats($clinicId, $year)
        );
    }

        public function servicesUsage(Request $request)
    {
        $clinicId = $request->input('clinic_id');
        $year = $request->input('year');

        if (!$clinicId) {
            return response()->json([
                'status' => false,
                'messages' => 'clinic_id is required',
                'data' => []
            ]);
        }

        return response()->json(
            $this->service->getClinicServicesUsage($clinicId, $year)
        );
    }

    public function getNotifications(Request $request)
    {
        return response()->json( $this->service->getUserNotifications() );
    }

}
