<?php

namespace App\Services\Manager;

use App\Models\{
    City, Street,
    Clinic, ClinicDoctor, ClinicImages,
    Service, Stage,
    Role, User, Secretary, Doctor,
    Setting, WorkingHour,
};

use App\Traits\HasImageActions;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

use Stripe\Checkout\Session;
use Stripe\Stripe;

class ServiceAndStageService
{
    /** Add (or create) a service to a clinic. */
    public function addServiceToClinic($clinicId, $serviceData, $price): array
    {
        $clinic = Clinic::findOrFail($clinicId);

        $currentUser = auth()->user();
        if ($clinic->user_id !== $currentUser->id) {
            return [
                'status'  => false,
                'message' => 'You are not authorized to add services to this clinic',
                'data'    => null
            ];
        }

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return [
                'status' => false,
                'message' => 'Clinic subscription not found',
                'data' => null,
            ];
        }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
        if (now()->greaterThanOrEqualTo($expiresAt)) {
            return [
                'status' => false,
                'message' => 'Clinic subscription expired, cannot add service',
                'data' => null,
            ];
        }

        if (isset($serviceData['service_id'])) {
            $service = Service::findOrFail($serviceData['service_id']);
        } else {
            $service = Service::create([
                'name'           => $serviceData['name'],
                'description'    => $serviceData['description'] ?? null,
                'duration'       => $serviceData['duration'] ?? null,
                'stages_number'  => isset($serviceData['stages']) ? count($serviceData['stages']) : ($serviceData['stages_number'] ?? 0),
            ]);

            if (!empty($serviceData['stages']) && is_array($serviceData['stages'])) {
                foreach ($serviceData['stages'] as $index => $stageData) {
                    $stage = Stage::create([
                        'duration'       => $stageData['duration'],
                        'title'          => $stageData['title'],
                        'specialization' => $stageData['specialization'],
                        'description'    => $stageData['description'],
                    ]);
                    $service->stages()->attach($stage->id, ['order' => $index + 1]);
                }
            }
        }

        if ($clinic->services()->where('service_id', $service->id)->exists()) {
            $clinic->services()->updateExistingPivot($service->id, [
                'price'         => $price,
                'accessibility' => $serviceData['accessibility'] ?? 'A',
            ]);

            $serviceWithPivot = $clinic->services()->where('service_id', $service->id)->first();

            return [
                'status' => true,
                'message' => 'Service already linked, values updated successfully',
                'data'   => [
                    'service'       => $serviceWithPivot,
                    'price'         => $serviceWithPivot->pivot->price,
                    'accessibility' => $serviceWithPivot->pivot->accessibility,
                ]
            ];
        }

        $clinic->services()->attach($service->id, [
            'price' => $price,
            'accessibility' => $serviceData['accessibility'] ?? 'A',
        ]);

        $serviceWithPivot = $clinic->services()->where('service_id', $service->id)->first();

        return [
            'status' => true,
            'message' => 'Service linked to clinic successfully',
            'data'   => [
                'service'       => $serviceWithPivot,
                'price'         => $serviceWithPivot->pivot->price,
                'accessibility' => $serviceWithPivot->pivot->accessibility,
            ]
        ];
    }

    /** Detach a service from a clinic. */
    public function removeServiceFromClinic($clinicId, $serviceId): array
    {
        $clinic  = Clinic::find($clinicId);
        $service = Service::find($serviceId);

        if (!$clinic || !$service) {
            return ['status' => false, 'message' => 'Clinic or service not found', 'data' => null ];
        }

        $currentUser = auth()->user();
        if ($clinic->user_id !== $currentUser->id) {
             return [ 'status'  => false, 'message' => 'You are not authorized to remove services from this clinic', 'data'    => null ];
        }

        if (!$clinic->subscribed_at || !$clinic->subscription_duration_days) {
            return [
                'status' => false,
                'message' => 'Clinic subscription not found',
                'data' => null,
            ];
        }

        $expiresAt = $clinic->subscribed_at->copy()->addDays($clinic->subscription_duration_days);
        if (now()->greaterThanOrEqualTo($expiresAt)) {
            return [
                'status' => false,
                'message' => 'Clinic subscription expired, cannot add service',
                'data' => null,
            ];
        }

        if (!$clinic->services()->where('service_id', $serviceId)->exists()){
            return [ 'status' => false, 'message' => __('This service is not found in this clinic'), 'data' => null ];
        }

        $clinic->services()->detach($serviceId);

        return [
            'status' => true,
            'message' => 'Service removed successfully',
            'data' => ['service' => $service]
        ];
    }

    /** Get all services that belong to a specific clinic. */
    public function showClinicServices($clinicId): array
    {
        $clinic = Clinic::find($clinicId);

        if (!$clinic)  return [ 'status'  => false, 'message' => 'Clinic not found', 'data'    => null ];

        $services = $clinic->services()->get()->map(function ($service) {
            return [
                'id'            => $service->id,
                'name'          => $service->name,
                'duration'      => $service->duration,
                'description'   => $service->description,
                'stages_number' => $service->stages_number,
                'created_at'    => $service->created_at,
                'updated_at'    => $service->updated_at,
                'price'         => $service->pivot->price,
                'accessibility' => $service->pivot->accessibility,
            ];
        })->values();

        return [
            'status'  => true,
            'message' => 'Clinic services fetched successfully',
            'data'    => ['services' => $services]
        ];
    }

    /** Fetch all available services in the system. */
    public function getServices(): array
    {
        $services = Service::select('id', 'name', 'description', 'duration', 'stages_number')->get();

        return [
            'status'  => true,
            'message' => 'Services fetched successfully',
            'data'    => ['services' => $services]
        ];
    }

    /** Get the stages of a specific service — ordered by pivot:order. */
    public function getStages(int $serviceId): array
    {
        $service = Service::with('stages')->find($serviceId);

        if (!$service)  return [ 'status'  => false, 'message' => 'Service not found', 'data'    => null ];

        $stages = $service->stages->map(function ($stage) {
            return [
                'id'            => $stage->id,
                'title'         => $stage->title,
                'duration'      => $stage->duration,
                'description'   => $stage->description,
                'specialization' => $stage->specialization,
                'order'         => $stage->pivot->order,
            ];
        })->sortBy('order')->values()->all();

        return [
            'status'  => true,
            'message' => 'Stages fetched successfully',
            'data'    => ['stages' => $stages]
        ];
    }

    /** Search for services by name. */
    public function searchForService($attributes)
    {
        $keyword = '%' . $attributes['keyword'] . '%';

        $services = Service::with(['stages.specialization'])
            ->where('name', 'like', $keyword)
            ->get()
            ->map(function ($service) {
                return [
                    'id'            => $service->id,
                    'name'          => $service->name,
                    'description'   => $service->description,
                    'duration'      => $service->duration,
                    'stages_number' => $service->stages_number,
                    'stages'        => $service->stages->map(function ($stage) {
                        return [
                            'id'             => $stage->id,
                            'title'          => $stage->title,
                            'duration'       => $stage->duration,
                            'description'    => $stage->description,
                            'specialization' => $stage->specialization,
                        ];
                    }),
                ];
            });

        return [
            'status'  => true,
            'message' => 'Services sent',
            'data'    => $services
        ];
    }

    public function getSpecializations()
    {
        $specializations = Stage::getSpecializations();

        return response()->json([
        'status'  => true,
        'message' => 'Specializations fetched successfully',
        'data'    => $specializations
       ]);
    }
}
