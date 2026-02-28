<?php

namespace App\Http\Controllers\Manager;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Http\Requests\Manager\Clinic\{
    StoreClinicRequest,
    ShowClinicRequest,
    GetClinicStaffRequest,
    AddStaffRequest,
    AddImageRequest,
    DeleteImageRequest,
    GetImageRequest,
    UpdateClinicRequest,
    UpdateStaffWorkingHoursRequest,
};

use App\Http\Requests\Manager\Service\{
    AddServiceRequest,
    ShowClinicServicesRequest,
    RemoveServiceFromClinicRequest,
    GetServiceStagesRequest
};

use App\Http\Requests\Patient\SearchRequest;

use App\Services\Manager\{ClinicService, ServiceAndStageService};

use App\Traits\Responses;

class ClinicController extends Controller
{
    use Responses;

    private ClinicService $service;
    private ServiceAndStageService $services;
    public function __construct(ClinicService $service , ServiceAndStageService $services)
    {
        $this->service = $service;
        $this->services = $services;
    }

    public function store(StoreClinicRequest $request)
    {
        $res = $this->service->createClinicWithPayment($request->validated(), $request->user());
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 422);
    }

    public function updateClinic(UpdateClinicRequest $request)
    {
        $res = $this->service->update($request->validated(), $request->user());
        return $res['status']
            ? $this->success($res['message'], ['clinic' => $res['data']])
            : $this->error($res['message'], 403);
    }

    public function renewSubscription(Request $request)
    {
        $request->validate([
            'clinic_id' => 'required|integer|exists:clinics,id',
        ]);
        $res = $this->service->renewClinicSubscription($request->all(), $request->user());
        return $res['status']
            ? $this->success($res['message'], $res['data'] ?? [], $res['code'] ?? 200)
            : $this->error($res['message'], $res['code'] ?? 422);
    }

    public function addStaff(AddStaffRequest $request)
    {
        $res = $this->service->addStaff($request->validated());
        if (!$res['status'])  return $this->error($res['message'], null, 422);
        return $this->success($res['message'], $res['data']);
    }

    public function updateStaffWorkingHours(UpdateStaffWorkingHoursRequest $request)
    {
        $res = $this->service->updateStaffWorkingHours($request->validated());
        return $this->success($res['message'], $res['data']);
    }

    public function addImage(AddImageRequest $request)
    {
        $res = $this->service->addClinicImage($request->clinic_id, $request->file('image'));
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 403);
    }

    public function deleteImage(DeleteImageRequest $request)
    {
        $res = $this->service->deleteImage($request->image_id);

        return $res['status']
            ? $this->success($res['message'], true)
            : $this->error($res['message'], 403);
    }

    public function getClinicImages(GetImageRequest $request)
    {
        $res = $this->service->getClinicImages($request->clinic_id);
        return $this->success($res['message'], $res['data']);
    }

    public function getClinicDoctors(GetClinicStaffRequest $request)
    {
        $res = $this->service->getClinicDoctors($request->user(), $request->clinic_id);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 403);
    }

    public function getClinicSecretaries(GetClinicStaffRequest $request)
    {
        $res = $this->service->getClinicSecretaries($request->user(), $request->clinic_id);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 403);
    }

    public function showClinic(ShowClinicRequest $request)
    {
        $res = $this->service->show($request->clinic_id);
        return $res['status']
            ? $this->success($res['message'], ['clinic' => $res['data']])
            : $this->error($res['message'], 404);
    }

    public function getClinics(Request $request)
    {
        $res = $this->service->allClinics($request->user());
        return $this->success($res['message'], ['clinics' => $res['data']]);
    }

    public function getMyClinics(Request $request)
    {
        $res = $this->service->myClinics($request->user());
        return $this->success($res['message'], ['clinics' => $res['data']]);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'keyword'   => 'required|string|max:255',
            'clinic_id' => 'required|integer|exists:clinics,id',
        ]);
        return $this->service->searchPatients($validated);
        // return response()->json($result);
    }

    // public function GetAppointments()
    // {
    //     return $this->service->GetAppointments();
    // }

    /*---- services and stages ---*/
    public function addServiceToClinic(AddServiceRequest $request)
    {
        $attributes = $request->validated();
        $res = $this->services->addServiceToClinic(
            $attributes['clinic_id'],
            $attributes,
            $attributes['price']
        );
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 422);
    }

    public function removeServiceFromClinic(RemoveServiceFromClinicRequest $request)
    {
        $attributes = $request->validated();
        $res = $this->services->removeServiceFromClinic(
            $attributes['clinic_id'],
            $attributes['service_id']
        );
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 422);
    }

    public function showClinicServices(ShowClinicServicesRequest $request)
    {
        $attributes = $request->validated();
        $res = $this->services->showClinicServices($attributes['clinic_id']);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 404);
    }

    public function getServices()
    {
        $res = $this->services->getServices();
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 422);
    }

    public function getStages(GetServiceStagesRequest $request)
    {
        $attributes = $request->validated();
        $res = $this->services->getStages($attributes['service_id']);
        return $res['status']
            ? $this->success($res['message'], $res['data'])
            : $this->error($res['message'], 404);
    }

    public function searchForServices(SearchRequest $request)
    {
        $attributes = $request->validate($request->rules());
        $res = $this->services->searchForService($attributes);
        return $this->success($res['message'], ['services' => $res['data']]);
    }

    public function getSpecializations()
    {
        return $this->services->getSpecializations();
    }

}
