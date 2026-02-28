<?php

namespace App\Http\Controllers;

use App\Http\Requests\Patient\AddFavouriteRequest;
use App\Http\Requests\Patient\AddReviewRequest;
use App\Http\Requests\Patient\AddSelfDataRequest;
use App\Http\Requests\Patient\AddTreatmentRequest;
use App\Http\Requests\Patient\BookAppointmentRequest;
use App\Http\Requests\Patient\GetStageAvailableDatesRequest;
use App\Http\Requests\Patient\GetAccessibleServicesRequest;
use App\Http\Requests\Patient\RemoveFavouriteRequest;
use App\Http\Requests\Patient\SearchRequest;
use App\Http\Requests\Patient\ShowClinicRequest;
use App\Http\Requests\Patient\UpdateImageRequest;
use App\Http\Requests\Patient\UpdateLocationRequest;
use App\Models\Treatment;
use App\Services\PatientService;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    protected $service;

    public function __construct(PatientService $service){
        $this->service = $service;
    }

    public function home(Request $request){
        return $this->service->home();
    }

    public function getClinics(Request $request){
        return $this->service->getClinics($request->user());
    }

    public function search(SearchRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->search($attributes, $request->user());
    }

    public function showClinic(ShowClinicRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->showClinic($attributes, $request->user());
    }

    public function getOngoingTreatments(Request $request){
        return $this->service->getOngoingTreatments($request->user()->id);
    }

    public function getAllTreatments(Request $request){
        return $this->service->getAllTreatments($request->user()->id);
    }
    public function getAvailableStages(Request $request){
        return $this->service->getAvailableStages($request->user()->id);
    }

    public function getStageAvailableDates(GetStageAvailableDatesRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['user']['id']=$request->user()->id;
        return $this->service->getStageAvailableDates($attributes);
    }

    public function getFavourites(Request $request){
        return $this->service->getFavourites($request->user()->id);
    }

    public function updateImage(UpdateImageRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->updateImage($attributes, $request->user()->id);
    }

    public function updateLocation(UpdateLocationRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->updateLocation($attributes, $request->user()->id);
    }

    public function bookAppointment(BookAppointmentRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['user_id'] = $request->user()->id;
        return $this->service->bookAppointment($attributes, $request->user()->id);
    }

    public function getAccessibleServices(GetAccessibleServicesRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->getAccessibleServices($attributes);
    }

    public function addTreatment(AddTreatmentRequest $request){
        $this->authorize('create', Treatment::class);
        $attributes = $request->validate($request->rules());
        $attributes['user_id'] = $request->user()->id;
        return $this->service->addTreatment($attributes);
    }

    public function addFavourite(AddFavouriteRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['user_id'] = $request->user()->id;
        return $this->service->addFavourite($attributes);
    }

    public function removeFavourite(RemoveFavouriteRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['user_id'] = $request->user()->id;
        return $this->service->removeFavourite($attributes);
    }

    public function addSelfData(AddSelfDataRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['user_id'] = $request->user()->id;
        return $this->service->addSelfData($attributes);
    }

    public function upcomingAppointments(Request $request){
        return $this->service->upcomingAppointments($request->user()->id);
    }

    public function history(Request $request){
        return $this->service->history($request->user()->id);
    }

    public function addReview(AddReviewRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->addReview($attributes, $request->user()->id);
    }

    public function getAdvertisements()
    {
        return $this->service->getAdvertisements();
    }

    public function showMedicalRecord(Request $request){
        return $this->service->showMedicalRecord($request->user()->id);
    }

    public function getSearchTerms(Request $request){
        return $this->service->getSearchTerms($request->user()->id);
    }
}

