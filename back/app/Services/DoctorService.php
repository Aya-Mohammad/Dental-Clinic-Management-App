<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\PatientData;
use App\Models\Service;
use App\Models\User;
use App\Models\Treatment;
use App\Models\ServiceTreatment;
use App\Models\ToothTreatment;
use App\Traits\Responses;
use App\Models\UserNotification;
use App\Notifications\AppointmentCancelNotification;
use Exception;
use Carbon\Carbon;

class DoctorService{
    use Responses;

    public function home(){

    }

    public function cancelAppointment(){

    }

    public function getOwnPatients($attributes, $user){
        $doctor = Doctor::firstWhere('user_id', $user->id);
        $appointments = Appointment::with(['user'])
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->where('doctor_id', '=', $doctor->id)
            ->get();
        $patients = [];
        $ids = [];
        foreach($appointments as $appointment){
            $ids[] = $appointment->user->id;
        }
        $ids = array_unique($ids);
        $patients = User::with(['patientData', 'medicalRecord'])
            ->whereIn('id', $ids)
            ->get();
        return $this->success('doctor own patients sent', $patients);
    }

    public function showPatient($attributes, $user_id){
        $doctor = Doctor::firstWhere('user_id', '=', $user_id);
        $appointment = Appointment::where('user_id', '=', $attributes['user_id'])
            ->where('doctor_id', '=', $doctor->id)
            ->where('status', '!=', 'X')
            ->first();
        if(is_null($appointment)){
            return $this->error('unauthorized', 403);
        }
        $patient = User::with(['patientData'])
            ->find($attributes['user_id']);
        return $this->success('patient data sent', $patient);
    }
    public function getOwnAppointments($attributes, $id){
        $doctor = Doctor::firstWhere('user_id', '=', $id);
        $required_date = Carbon::parse($attributes['date']);
        $appointments = Appointment::with(['user', 'treatment'])
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->where('doctor_id', '=', $doctor->id)
            ->where('status', '=', 'UC')
            ->whereDate('date', '=', $required_date)
            ->get();
        return $this->success('own upcoming appointments sent', $appointments);
    }

    public function addTreatment($attributes){
        $clinic = Clinic::find($attributes['clinic_id']);
        $expire_date = Carbon::parse($clinic->subscribed_at);
        $expire_date->addDays($clinic->subscription_duration_days);
        if($expire_date->lt(now())){
            return $this->error("can't show this clinic, contact manager", 401);
        }

        if(array_key_exists('number', $attributes)){
            $patient = User::firstWhere('number', '=', $attributes['number']);
        } else {
            $patient = User::firstWhere('email', '=', $attributes['email']);
        }


        $attr['services'] = count($attributes['services']);
        $attr['service_number'] = 1;
        $attr['status'] = 'UC'; /// better check this one in the database
        $attr['user_id'] = $patient->id;
        $attr['clinic_id'] = $attributes['clinic_id'];
        $treatment = Treatment::create($attr);
        foreach($attributes['services'] as $service){
            ServiceTreatment::create([
                'order' => $service['order'],
                'stage_number' => 1,
                'service_id' => $service['service_id'],
                'treatment_id' => $treatment->id
            ]);
        }
        if(array_key_exists('teeth', $attributes)){
            foreach($attributes['teeth'] as $tooth){
                ToothTreatment::create([
                    'tooth_number' => $tooth,
                    'treatment_id' => $treatment->id
                ]);
            }
        }

        return $this->success('treatment added', $treatment);
    }

    public function updateTreatment($attributes){
        $treatment = Treatment::find($attributes['treatment_id']);
        if($attributes['service_number'] > $treatment->services){
            return $this->error('invalid service number', 401);
        }
        $service_treatment = ServiceTreatment::where('treatment_id', '=', $treatment->id)
            ->where('order', '=', $attributes['service_number'])
            ->first();
        $service = Service::find($service_treatment->service_id);
        if($service->stages_number < $attributes['stage_number']){
            return $this->error('invalid stage number', 401);
        }
        $treatment->status = $attributes['status'];
        $treatment->service_number = $attributes['service_number'];
        $treatment->save();
        $service_treatment->stage_number = $attributes['stage_number'];
        $service_treatment->save();
        return $this->success('treatment updated', $treatment);
    }

    public function showMedicalRecord($attributes) {
        $medical_record = MedicalRecord::firstWhere('user_id', '=', $attributes['user_id']);
        return $this->success('medical record sent', $medical_record);
    }

    public function updateMedicalRecord($attributes) {
        $medical_record = MedicalRecord::firstWhere('user_id', '=', $attributes['user_id']);
        $medical_record->record = $attributes['record'];
        $medical_record->save();
        return $this->success('medical record updated', $medical_record);
    }

    public function updateAppointment($attributes){
        $appointment = Appointment::find($attributes['appointment_id']);
        $appointment->status = $attributes['status'];
        $appointment->save();
        $treatment = Treatment::find($appointment->treatment_id);
        if($treatment->status == 'A'){
            $treatment->status = 'UC';
            $treatment->save();
        }
        return $this->success('appointment updated', $appointment);
    }

    public function getTeethServices($attributes){
        $treatments = Treatment::with(['toothtreatments', 'services'])
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->where('user_id', '=', $attributes['user_id'])
            ->get();
        $teeth_data = [];
        for($i = 11; $i<=48;$i++){
            if($i%10 == 0 || $i%10 == 9){
                continue;
            }
            $str = (string) $i;
            $teeth_data[$str] = [];
        }
        foreach($treatments as $treatment){
            foreach($treatment->toothTreatments as $toothTreatment){
                $str = (string) $toothTreatment->tooth_number;
                foreach($treatment->getRelation('services') as $service){
                    $teeth_data[$str][] = [
                        'service_id' => $service->id,
                        'service_name' => $service->name
                    ];
                }
            }
        }
        for($i = 11; $i<=48;$i++){
            if($i%10 == 0 || $i%10 == 9){
                continue;
            }
            $str = (string) $i;
            $seen = [];
            $unique = [];
            foreach($teeth_data[$str] as $data){
                if(!in_array($data['service_id'], $seen)){
                    $seen[] = $data['service_id'];
                    $unique[] = $data;
                }
            }
            $teeth_data[$str] = $unique;
        }
        return $this->success('teeth data sent', $teeth_data);
    }

    public function showToothTreatments($attributes){
        $treatments = Treatment::with(['toothtreatments', 'services', 'appointments.doctor'])
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->where('user_id', '=', $attributes['user_id'])
            ->get();
        $tooth_data = [];
        foreach($treatments as $treatment){
            $check = false;
            foreach($treatment->toothtreatments as $toothtreatment){
                if($toothtreatment->tooth_number == $attributes['tooth_number']){
                    $check = true;
                    break;
                }
            }
            if($check){
                $tooth_data[] = $treatment;
            }
        }
        return $this->success('tooth data sent', $tooth_data);
    }

    public function getUpdatableTreatments($attributes, $user_id){
        $doctor = Doctor::firstWhere('user_id', '=', $user_id);
        $appointments = Appointment::where('doctor_id', '=', $doctor->id)
            ->where('user_id', '=', $attributes['user_id'])
            ->get();
        $treatment_ids = [];
        foreach($appointments as $appointment){
            $treatment_ids[] = $appointment->treatment_id;
        }
        $treatment_ids = array_unique($treatment_ids);
        $treatments = Treatment::with(['appointments', 'services'])
            ->whereIn('id', $treatment_ids)
            ->where('status', '!=', 'C')
            ->where('status', '!=', 'X')
            ->get();
        $data = [];
        foreach($treatments as $treatment){
            $appointment = $treatment->appointments->last();
            if($appointment->doctor_id == $doctor->id){
                $data[] = $treatment;
            }
        }
        return $this->success('updatable treatments sent', $data);
    }

    public function updateProfile($attributes, $user_id){
        $doctor = Doctor::firstWhere('user_id', '=', $user_id);
        $doctor->update($attributes);
        return $this->success('doctor updated', $doctor);
    }

    public function cancelDayAppointments($attributes, $user_id){
        $doctor = Doctor::firstWhere('user_id', '=', $user_id);
        $required_date = Carbon::parse($attributes['date']);
        $appointments = Appointment::where('doctor_id', '=', $doctor->id)
            ->where('status', '=', 'UC')
            ->whereDate('date', '=', $required_date)
            ->get();
        
        foreach($appointments as $appointment){
            $patient = User::find($appointment->user_id);
            if(!is_null($patient->fcm_token)){
                try{
                    UserNotification::create([
                        'type' => 'appointment',
                        'title' => 'appointment canceled',
                        'messages' => 'your appointment has been canceled',
                        'is_read' => false,
                        'data' => null,
                        'user_id' => $patient->id,
                    ]);
                    $patient->notify(new AppointmentCancelNotification((string) $appointment->id));
                }
                catch(Exception $e){

                }
            }
            $treatment = Treatment::find($appointment->treatment_id);
            $appointment->status = 'X';
            $appointment->save();
            $treatment->status = 'UC';
            $treatment->save();
        }
        return $this->success('appointments canceled', $appointment);
    }
}
