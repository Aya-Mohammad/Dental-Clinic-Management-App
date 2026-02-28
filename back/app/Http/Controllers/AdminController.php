<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Models\{
    Clinic,
    Advertisment,
};

use Illuminate\Http\Request;
use App\Http\Requests\Admin\{
    SetSubscriptionValuesRequest
};

use App\Traits\Responses;
use App\Services\Admin\AdminService;

class AdminController extends Controller
{
    use Responses;

    protected $service ;
    public function __construct(AdminService $service)
    {
        $this->service = $service;
    }

   public function checkAdvertismentSubscription(Request $request)
    {
        $res = $this->service->checkAdvertismentSubscription($request->advertisment_id);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], $res['data'] ?? [], $res['status_code'] ?? 403);
    }

    public function checkClinicSubscription(Request $request)
    {
        $res = $this->service->checkClinicSubscription($request->clinic_id);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], $res['data'] ?? [], $res['status_code'] ?? 403);
    }

    public function setAdvertismentSubscription(SetSubscriptionValuesRequest $request)
    {
        $res = $this->service->setAdvertismentSubscription($request->price, $request->days);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 422);
    }

    public function setClinicSubscription(SetSubscriptionValuesRequest $request)
    {
        $res = $this->service->setClinicSubscription($request->price, $request->days);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 422);
    }

    public function getClinicSubscription(Request $request)
    {
        $res = $this->service->getClinicSubscription($request->clinic_id, $request->user());
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 404);
    }

    public function getAdvertisementSubscriptions(Request $request)
    {
        $res = $this->service->getAdvertisementSubscriptions($request->clinic_id, $request->user());
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 404);
    }

    public function approveAdvertisment(Request $request)
    {
        $res = $this->service->approveAdvertisment($request->advertisement_id);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 400);
    }

    public function rejectAdvertisment(Request $request)
    {
        $res = $this->service->rejectAdvertisment($request->advertisement_id);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 400);
    }

    public function getAllSubscriptionSettings()
    {
        $res = $this->service->getAllSubscriptionSettings();
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], 422);
    }

   public function clinicsStatsAndServices(Request $request)
    {
        $year = $request->input('year');
        $res = $this->service->getAllClinicsStatsAndServices($year);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], $res['data'] ?? [], $res['status_code'] ?? 422);
    }

    public function subscriptionsStats(Request $request)
    {
        $year = $request->input('year');
        $res = $this->service->getAllSubscriptionsStats($year);
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], $res['data'] ?? [], $res['status_code'] ?? 422);
    }

    public function annualPaymentsStats()
    {
        $res = $this->service->getAnnualPaymentsStats();
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], $res['data'] ?? [], $res['status_code'] ?? 422);
    }

    public function usersStats()
    {
        $res = $this->service->getUsersAnnualStats();
        return $res['status']
            ? $this->success($res['messages'], $res['data'])
            : $this->error($res['messages'], $res['data'] ?? [], $res['status_code'] ?? 422);
}

}
