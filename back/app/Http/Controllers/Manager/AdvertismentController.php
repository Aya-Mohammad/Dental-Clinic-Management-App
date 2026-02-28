<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\Manager\Advertisment\{
    AddAdvertismentRequest,
    ShowAdvertismentRequest,
    UpdateAdvertismentRequest
};

use App\Services\Manager\AdvertismentService;

use App\Traits\Responses;

class AdvertismentController extends Controller
{
     use Responses;

    protected $service;
    public function __construct(AdvertismentService $service)
    {
        $this->service = $service;
    }

    public function addAdvertisment(AddAdvertismentRequest $request)
    {
        $attributes = $request->validated();
        $res = $this->service->addAdvertisment($request, $attributes);
        return $res['status']
            ? $this->success($res['message'], $res['data'] ?? [], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function createPaymentSession(Request $request)
    {
        $advertisement_id = $request->input('advertisement_id');
        $res = $this->service->createPaymentSession($request, $advertisement_id);
        return $res['status']
            ? $this->success($res['message'], $res['data'] ?? [], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function updateAdvertisment(UpdateAdvertismentRequest $request)
    {
        $res = $this->service->updateAdvertisment($request);
        return $res['status']
            ? $this->success($res['message'], $res['data'], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function showAdvertisment(ShowAdvertismentRequest $request)
    {
        $adData = $this->service->showAdvertisment($request->validated()['advertisment_id']);
        return $adData['status']
            ? $this->success($adData['message'], $adData['data'] ?? [], $adData['code'] ?? 200)
            : $this->error($adData['message'], $adData['code'] ?? 422);
    }

    public function getClinicAdvertisments(Request $request)
    {
        $clinic_id = $request->input('clinic_id');
        $status = $request->input('status');
        $res = $this->service->getClinicAdvertisments($request, $clinic_id , $status);
        return $res['status']
            ? $this->success($res['message'], $res['data'], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function getAllAdvertismentsWithClinics(Request $request)
    {
        $status = $request->input('status');
        $res = $this->service->getAllAdvertismentsWithClinics($request , $status);
        return $res['status']
            ? $this->success($res['message'], $res['data'], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function resendPaymentLink(Request $request)
    {
        $advertisement_id = $request->input('advertisement_id');
        $response = $this->service->resendPaymentLinkForAd($request,$advertisement_id, auth()->user());
        return response()->json($response);
    }

    public function renew(Request $request)
    {
        $authUser = $request->user();
        $res = $this->service->renewAdvertisment($request, $authUser);
        return $res['status']
            ? $this->success($res['message'], $res['data'] ?? [], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }


}
