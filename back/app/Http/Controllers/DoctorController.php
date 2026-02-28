<?php

namespace App\Http\Controllers;

use App\Http\Requests\Doctor\AddTreatmentRequest;
use App\Http\Requests\Doctor\CancelDayAppointmentsRequest;
use App\Http\Requests\Doctor\GetMedicalRecordRequest;
use App\Http\Requests\Doctor\GetOwnAppointmentsRequest;
use App\Http\Requests\Doctor\GetOwnPatientsRequest;
use App\Http\Requests\Doctor\GetTeethServicesRequest;
use App\Http\Requests\Doctor\GetUpdatableTreatmentsRequest;
use App\Http\Requests\Doctor\ShowPatientRequest;
use App\Http\Requests\Doctor\ShowToothTreatmentsRequest;
use App\Http\Requests\Doctor\UpdateAppointmentRequest;
use App\Http\Requests\Doctor\UpdateMedicalRecordRequest;
use App\Http\Requests\Doctor\UpdateProfileRequest;
use App\Http\Requests\Doctor\UpdateTreatmentRequest;
use App\Models\Appointment;
use App\Models\MedicalRecord;
use Illuminate\Http\Request;
use App\Services\DoctorService;
use App\Models\Treatment;

class DoctorController extends Controller
{
    protected $service;

    public function __construct(DoctorService $service)
    {
        $this->service = $service;
    }

    public function home()
    {

    }

    public function cancelAppointment()
    {

    }

    public function getOwnPatients(GetOwnPatientsRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->getOwnPatients($attributes, $request->user());
    }

    public function showPatient(ShowPatientRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->showPatient($attributes, $request->user()->id);
    }
    public function getOwnAppointments(GetOwnAppointmentsRequest $request)
    {
        $attributes = $request->validate($request->rules());
        return $this->service->getOwnAppointments($attributes, $request->user()->id);
    }

    public function addTreatment(AddTreatmentRequest $request)
    {
        $this->authorize('create', Treatment::class);
        $attributes = $request->validate($request->rules());
        return $this->service->addTreatment($attributes);
    }

    public function updateTreatment(UpdateTreatmentRequest $request)
    {
        $treatment = Treatment::Find($request->get('treatment_id'));
        $this->authorize('update', $treatment);
        $attributes = $request->validate($request->rules());
        return $this->service->updateTreatment($attributes);
    }

    public function showMedicalRecord(GetMedicalRecordRequest $request)
    {
        $medical_record = MedicalRecord::firstOrCreate([
            'user_id' => $request->get('user_id')
        ], [
            'record' => null
        ]);
        $this->authorize('view', $medical_record);
        $attributes = $request->validate($request->rules());
        return $this->service->showMedicalRecord($attributes);
    }

    public function updateMedicalRecord(UpdateMedicalRecordRequest $request)
    {
        $medical_record = MedicalRecord::firstOrCreate([
            'user_id' => $request->get('user_id')
        ], [
            'record' => null
        ]);
        $this->authorize('update', $medical_record);
        $attributes = $request->validate($request->rules());
        return $this->service->updateMedicalRecord($attributes);
    }

    public function updateAppointment(UpdateAppointmentRequest $request)
    {
        $appointment = Appointment::find($request->get('appointment_id'));
        $this->authorize('update', $appointment);
        $attributes = $request->validate($request->rules());
        return $this->service->updateAppointment($attributes);
    }

    public function getTeethServices(GetTeethServicesRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->getTeethServices($attributes);
    }

    public function showToothTreatments(ShowToothTreatmentsRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->showToothTreatments($attributes);
    }

    public function getUpdatableTreatments(GetUpdatableTreatmentsRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->getUpdatableTreatments($attributes, $request->user()->id);
    }

    public function updateProfile(UpdateProfileRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->updateProfile($attributes, $request->user()->id);
    }

    public function cancelDayAppointments(CancelDayAppointmentsRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->cancelDayAppointments($attributes, $request->user()->id);
    }
}
