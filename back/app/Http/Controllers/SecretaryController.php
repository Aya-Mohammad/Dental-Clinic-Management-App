<?php

namespace App\Http\Controllers;

use App\Http\Requests\Secretary\CancelAppointmentRequest;
use App\Http\Requests\Secretary\UpdateProfileRequest;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Treatment;
use App\Http\Requests\Secretary\AddPatientDataRequest;
use App\Http\Requests\Secretary\AddTreatmentForPatientRequest;
use App\Http\Requests\Secretary\BookAppointmentForPatientRequest;
use App\Http\Requests\Secretary\getAccessibleServicesRequest;
use App\Http\Requests\Secretary\GetAvailableStagesForPatientRequest;
use App\Http\Requests\Secretary\GetClinicAppointmentsRequest;
use App\Http\Requests\Secretary\GetPatientsRequest;
use App\Http\Requests\Secretary\GetStageAvailableDatesRequest;
use App\Http\Requests\Secretary\MakeEmailAccountRequest;
use App\Http\Requests\Secretary\MakeNumberAccountRequest;
use App\Http\Requests\Secretary\VerifyAccountByEmailRequest;
use App\Http\Requests\Secretary\VerifyAccountByNumberRequest;
use App\Http\Requests\Secretary\ShowPatientRequest;
use App\Services\SecretaryService;
use Illuminate\Http\Request;

class SecretaryController extends Controller
{
    protected $service;

    public function __construct(SecretaryService $service)
    {
        $this->service = $service;
    }

    public function makeNumberAccount(MakeNumberAccountRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->makeNumberAccount($attributes);
    }

    public function makeEmailAccount(MakeEmailAccountRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->makeEmailAccount($attributes);
    }
    public function verifyAccountByNumber(VerifyAccountByNumberRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->verifyAccountByNumber($attributes);
    }

    public function verifyAccountByEmail(VerifyAccountByEmailRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->verifyAccountByEmail($attributes);
    }

    public function getAccessibleServices(getAccessibleServicesRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->getAccessibleServices($attributes);
    }

    public function addTreatmentForPatient(AddTreatmentForPatientRequest $request)
    {
        $this->authorize('create', Treatment::class);
        $attributes = $request->validate($request->rules());
        $attributes['user'] = $request->user();
        return $this->service->addTreatmentForPatient($attributes);
    }

    public function bookAppointmentForPatient(BookAppointmentForPatientRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->bookAppointmentForPatient($attributes);
    }

    public function cancelAppointment(CancelAppointmentRequest $request)
    {
        $appointment = Appointment::find($request->get('appointment_id'));
        $this->authorize('cancel', $appointment);
        $attributes = $request->validate($request->rules());
        return $this->service->cancelAppointment($attributes);
    }

    public function getAvailableStagesForPatient(GetAvailableStagesForPatientRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->getAvailableStagesForPatient($attributes);
    }

    public function getStageAvailableDates(GetStageAvailableDatesRequest $request)
    {
        /// get details needed to check which specialization is needed for the stage
        $attributes = $request->validate($request->rules());
        return $this->service->getStageAvailableDates($attributes);
    }

    public function getPatients(GetPatientsRequest $request)
    {
        $this->authorize('viewAny', User::class);
        $attributes = $request->validate($request->rules());

        return $this->service->getPatients($attributes);
    }

    public function showPatient(ShowPatientRequest $request)
    {
        $user = User::find($request->get('user_id'));
        $this->authorize('view', $user);
        $attributes = $request->validate($request->rules());
        return $this->service->showPatient($attributes);
    }

    public function addPatientData(AddPatientDataRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->addPatientData($attributes);
    }

    public function getClinicAppointments(GetClinicAppointmentsRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->getClinicAppointments($attributes, $request->user()->id);
    }

    public function updateProfile(UpdateProfileRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->updateProfile($attributes, $request->user()->id);
    }
}
