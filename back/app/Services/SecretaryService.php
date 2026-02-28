<?php

namespace App\Services;

use App\Models\Block;
use App\Models\City;
use App\Models\ClinicSecretary;
use App\Models\ClinicService;
use App\Models\PatientData;
use App\Models\Secretary;
use App\Models\ServiceTreatment;
use App\Models\Street;
use App\Models\ToothTreatment;
use App\Notifications\AppointmentCancelNotification;
use App\Traits\Responses;
use App\Models\User;
use App\Models\Treatment;
use App\Models\Service;
use App\Models\ServiceStage;
use App\Models\Stage;
use App\Models\Clinic;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Notifications\MailNotification;
use Illuminate\Support\Facades\Date;
use Exception;
use Illuminate\Support\Facades\Hash;
use App\Models\UserNotification;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class SecretaryService
{
    use Responses;

    public function makeNumberAccount($attributes)
    {
        /// must send otp
        $attributes['password'] = Hash::make($attributes['password']);
        $otp = (string) rand(100000, 999999);
        Log::info('otp recieved: ', ['otp' => $otp]);
        $attributes['otp'] = Hash::make($otp);
        $attributes['expire_at'] = now()->addHour();
        $user = User::create($attributes);
        $user->addRoleByName('patient');
        return $this->success('user created', $user);
    }

    public function makeEmailAccount($attributes)
    {
        $attributes['password'] = Hash::make($attributes['password']);
        $otp = (string) rand(100000, 999999);
        Log::info('otp recieved: ', ['otp' => $otp]);
        $attributes['otp'] = Hash::make($otp);
        $attributes['expire_at'] = now()->addHour();
        $user = User::create($attributes);
        $user->addRoleByName('patient');
        $user->notify(new MailNotification($otp));
        return $this->success('user created', $user);
    }
    //not completed
    public function verifyAccountByNumber($attributes)
    {
        $user = User::firstWhere('number', '=', $attributes['number']);
        if (now()->greaterThan($user->expire_at)) {
            return $this->error('OTP expired', 1);
        }
        if (!Hash::check($attributes['otp'], $user->otp)) {
            return $this->error('Invalid OTP', 0);
        }
        $user->verified_at = now();
        $user->otp = null;
        $user->save();
        return $this->success('Verified successfully', $user, 200);
    }

    public function verifyAccountByEmail($attributes)
    {
        $user = User::firstWhere('email', '=', $attributes['email']);
        if (now()->greaterThan($user->expire_at)) {
            return $this->error('OTP expired', 1);
        }
        if (!Hash::check($attributes['otp'], $user->otp)) {
            return $this->error('Invalid OTP', 0);
        }
        $user->verified_at = now();
        $user->otp = null;
        $user->save();
        return $this->success('Verified successfully', $user, 200);
    }


    public function getAccessibleServices($attributes)
    {
        $ids = ClinicService::where('clinic_id', '=', $attributes['clinic_id'])
            ->where('accessibility', '=', 'A')
            ->pluck('service_id')
            ->toArray();
        $services = Service::whereIn('id', $ids)
            ->get();
        $data = [];
        foreach($services as $service){
            $service_stage = ServiceStage::where('service_id', '=', $service->id)
                ->where('order', '=', 1)
                ->first();

        if (!$service_stage) {
            continue;
        }

            $stage = Stage::find($service_stage->stage_id);
            $stage_available_dates = $this->getStageAvailableDates([
                'clinic_id' => $attributes['clinic_id'],
                'stage_id' => $stage->id,
                'self_called' => true
            ]);
            $data[] = [
                'service' => $service,
                'next_stage' => $stage,
                'available_dates' => $stage_available_dates
            ];
        }
        return $this->success('services sent', $data);
    }

    public function addTreatmentForPatient($attributes)
    {
        $clinic = Clinic::find($attributes['clinic_id']);
        $expire_date = Carbon::parse($clinic->subscribed_at);
        $expire_date->addDays($clinic->subscription_duration_days);
        if($expire_date->lt(now())){
            return $this->error("can't add treatments for this clinic, contact manager", 401);
        }
        if(array_key_exists('number', $attributes)){
            $patient = User::firstWhere('number', '=', $attributes['number']);
        } else {
            $patient = User::firstWhere('email', '=', $attributes['email']);
        }


        $attr['services'] = count($attributes['services']);
        $attr['service_number'] = 1;
        $attr['status'] = 'UC'; 
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

    public function bookAppointmentForPatient($attributes)
    {

        if(array_key_exists('number', $attributes)) {
            $patient = User::firstWhere('number', '=', $attributes['number']);
        } else {
            $patient = User::firstWhere('email', '=', $attributes['email']);
        }
        $attributes['user_id'] = $patient->id;
        $block = Block::where('user_id', '=', $attributes['user_id'])
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->first();
        if(!is_null($block)){
            return $this->error('patient block by manager', 403);
        }

        $clinic = Clinic::find($attributes['clinic_id']);
        $expire_date = Carbon::parse($clinic->subscribed_at);
        $expire_date->addDays($clinic->subscription_duration_days);
        if($expire_date->lt(now())){
            return $this->error("can't book appointments for this clinic, contact manager", 401);
        }

        $appointments = Appointment::where('clinic_id', '=', $attributes['clinic_id'])
            ->where('doctor_id', '=', $attributes['doctor_id'])
            ->where('status', '=', 'UC')
            ->whereDate('date', '=', $attributes['date'])
            ->orderBy('time', 'asc')
            ->get();
        $time = Carbon::createFromTimeString($attributes['time']);
        if(array_key_exists('treatment_id', $attributes)){
            $treatment = Treatment::find($attributes['treatment_id']);
        }
        else{
            $attr['services'] = 1;
            $attr['service_number'] = 1;
            $attr['status'] = 'UC'; /// better check this one in the database
            $attr['user_id'] = $patient->id;
            $attr['clinic_id'] = $attributes['clinic_id'];
            $treatment = Treatment::create($attr);
            ServiceTreatment::create([
                'order' => 1,
                'stage_number' => 1,
                'service_id' => $attributes['service_id'],
                'treatment_id' => $treatment->id
            ]);
        }
        if($treatment->status != 'UC'){
            return $this->error('treatment max booked appointments reached', 401);
        }
        $stage = Stage::find($attributes['stage_id']);
        foreach ($appointments as $appt) {
            $temp_time = Carbon::createFromTimeString($appt->time);
            if ($temp_time->eq($time)) {
                return $this->error('appointment time is not available', 401);
            } else if ($temp_time->lt($time)) {
                $duration = Carbon::createFromTimeString($appt->duration);
                $temp_time->addHours($duration->hour);
                $temp_time->addMinutes($duration->minute);
                $temp_time->addSeconds($duration->second);
                if ($time->lt($temp_time)) {
                    return $this->error('appointment time is not available', 401);
                }
            } else {
                $duration = Carbon::createFromTimeString($stage->duration);
                if(array_key_exists('duration', $attributes)){
                    $duration = Carbon::createFromTimeString($attributes['duration']);
                }
                $time->addHours($duration->hour);
                $time->addMinutes($duration->minute);
                $time->addSeconds($duration->second);
                if ($temp_time->lt($time)) {
                    return $this->error('appointment time is not available', 401);
                }
                break;
            }
        }
        $duration = $stage->duration;
        if(array_key_exists('duration', $attributes)){
            $duration = $attributes['duration'];
        }
        $appointment = Appointment::create([
            'date' => $attributes['date'],
            'time' => $attributes['time'],
            'duration' => $duration,
            'status' => 'UC',
            'clinic_id' => $attributes['clinic_id'],
            'treatment_id' => $treatment->id,
            'doctor_id' => $attributes['doctor_id'],
            'user_id' => $patient->id,
        ]);
        $treatment->status = 'A';
        $treatment->save();
        return $this->success('appointment booked', $appointment);
    }

    public function cancelAppointment($attributes)
    {
        $appointment = Appointment::find($attributes['appointment_id']);
        $doctor = Doctor::find($appointment->doctor_id);
        $doctor_user = User::find($doctor->user_id);
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
        if(!is_null($doctor_user->fcm_token)){
            try{
                UserNotification::create([
                    'type' => 'appointment',
                    'title' => 'appointment canceled',
                    'messages' => 'your appointment has been canceled',
                    'is_read' => false,
                    'data' => null,
                    'user_id' => $doctor_user->id,
                ]);
                $doctor_user->notify(new AppointmentCancelNotification((string) $appointment->id));
            }
            catch(Exception $e){

            }
        }
        $treatment = Treatment::find($appointment->treatment_id);
        $appointment->status = 'X';
        $appointment->save();
        if($treatment->status == 'A'){
            $treatment->status = 'UC';
            $treatment->save();
        }
        return $this->success('appointment canceled', $appointment);
    }

    public function getAvailableStagesForPatient($attributes)
    {
        if(array_key_exists('number', $attributes)) {
            $patient = User::firstWhere('number', '=', $attributes['number']);
        } else {
            $patient = User::firstWhere('email', '=', $attributes['email']);
        }
        $active_treatments = Treatment::where('user_id', '=', $patient->id)
            ->where('status', '!=', 'C')
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->get();
        $services = [];
        foreach ($active_treatments as $treatment) {
            $service_treatment = ServiceTreatment::where('treatment_id', '=', $treatment->id)
                ->where('order', '=', $treatment->service_number)
                ->first();
            $service = Service::find($service_treatment->service_id);
            $service_stage = ServiceStage::where('service_id', '=', $service_treatment->service_id)
                ->where('order', '=', $service_treatment->stage_number)
                ->first();
            $stage = Stage::find($service_stage->stage_id);
            $service['next_stage'] = $stage;
            $service['treatment'] = $treatment;
            $services[] = $service;
        }
        return $this->success('services with stages sent', $services);
    }

    public function getStageAvailableDates($attributes)
    {
        /// get details needed to check which specialization is needed for the stage
        $clinic = Clinic::with(['clinicDoctors.doctor.appointments', 'clinicDoctors.workingHours'])
            ->find($attributes['clinic_id']);

        $stage = Stage::find($attributes['stage_id']);
        $specialization = $stage->specialization;
        $clinic->setRelation('clinic_doctors', $clinic->clinicDoctors);
        if ($specialization != 'G') {
            $filteredDoctors = $clinic->clinicDoctors->filter(function ($clinicDoctor) use ($specialization) {
                $doctor = $clinicDoctor->doctor;
                return $doctor && strtolower($doctor->specialization) === strtolower($specialization);
            })->values();

            // Replace the original relation so Laravel uses the correct key (fixes how data look like)
            $clinic->setRelation('clinic_doctors', $filteredDoctors);
        }

        /// get appointments for every doctor
        foreach ($clinic->clinic_doctors as $clinicDoctor) {
            $doctor = $clinicDoctor->doctor;

            if ($doctor && $doctor->relationLoaded('appointments')) {
                $filteredAppointments = $doctor->appointments
                    ->filter(fn($appt) => $appt->status === 'UC')
                    ->sortBy('time') // 👈 Sort ascending by time
                    ->values();

                $doctor->setRelation('appointments', $filteredAppointments);
            }
        }

        $data = [];
        $no_dates = true;

        foreach ($clinic->clinic_doctors as $obj) {
            /// get for each doctor his working hours
            foreach ($obj->workingHours as $working_hour) {
                $current = Date::now();
                while (True) {
                    $day = Carbon::parse($current)->format('l');
                    if ($day == $working_hour->pivot->working_day)
                        break;
                    $current->addDay();
                }
                /// this for to get available dates for 2 weeks, 2 can be edited to get more dates
                for ($it = 0; $it < 2; $it++) {
                    /// remove booked appointments
                    $current_time = Carbon::createFromFormat('H:i:s', $working_hour->start);
                    foreach ($obj->doctor->appointments as $appointment) {
                        $appointment_date = Carbon::parse($appointment->date);
                        $appointment_time = Carbon::createFromFormat('H:i:s', $appointment->time);
                        $appointment_duration = Carbon::createFromFormat('H:i:s', $appointment->duration);
                        if (!$appointment_date->isSameDay($current))
                            continue;
                        if ($appointment_time->eq($current_time)) {
                            $current_time->addHours($appointment_duration->hour)
                                ->addMinutes($appointment_duration->minute)
                                ->addSeconds($appointment_duration->second);
                        }
                    }


                    $stage_duration = Carbon::createFromFormat('H:i:s', $stage->duration);
                    if(array_key_exists('duration', $attributes)){

                        $stage_duration = Carbon::createFromFormat('H:i:s', $attributes['duration']);
                    }
                    $temp = $current_time->copy()->addHours($stage_duration->hour)
                        ->addMinutes($stage_duration->minute)
                        ->addSeconds($stage_duration->second)
                        ->setDate(2000, 1, 1);
                    $working_hour_end = Carbon::createFromFormat('H:i:s', $working_hour->end)
                        ->setDate(2000, 1, 1);

                    /// check if the appointment will exceed doctor worknig hour
                    if ($temp->lt($working_hour_end) || $temp->eq($working_hour_end)) {
                        $string_date = Carbon::parse($current)->toDateString();
                        $string_time = $current_time->toTimeString();

                        if (!Arr::has($data, $string_date))
                            $data[$string_date] = [];


                        if (!Arr::has($data[$string_date], $string_time))
                            $data[$string_date][$string_time] = [];

                        $id = $obj->doctor_id;
                        $doc = Doctor::with('user')
                            ->find($id);
                        $data[$string_date][$string_time][] = $doc;
                        $no_dates = false;
                    }
                    $current->addDays(7);
                }
            }
        }
        if($no_dates){
            $data = null;
        }
        if(array_key_exists('self_called', $attributes)){
            return $data;
        }
        return $this->success('', $data, 200);
    }

    public function getPatients($attributes)
    {
        $treatments = Treatment::where('clinic_id', '=', $attributes['clinic_id'])
            ->get();
        $ids = [];
        foreach($treatments as $treatment){
            $ids[] = $treatment->user_id;
        }
        $ids = array_unique($ids);
        $users = User::whereIn('id', $ids)
            ->get();
        return $this->success('patients sent', $users);
    }

    public function showPatient($attributes)
    {
        $user = User::with(['patientData.street.city', 'appointments.doctor.user'])
            ->find($attributes['user_id']);
        $filteredAppointments = $user->appointments
            ->filter(fn($appt) => $appt->clinic_id === $attributes['clinic_id'])
            ->sortBy('time') // 👈 Sort ascending by time
            ->values();

        $user->setRelation('appointments', $filteredAppointments);
        foreach($user->appointments as $appointment){
            $doc_name = $appointment->doctor->user->name;
            $appointment->doctor = $doc_name;
        }
        return $this->success('patient data sent', $user);
    }

    public function addPatientData($attributes)
    {
        $check = PatientData::firstWhere('user_id', '=', $attributes['user_id']);
        if($check){
            return $this->error('patient already has data', 401);
        }
        $city = City::firstOrCreate(
            ['name' => $attributes['city']]
        );
        $street = Street::firstOrCreate(
            ['city_id' => $city->id],
            ['name' => $attributes['street']]
        );
        $attr = [
            'gender' => $attributes['gender'],
            'date_of_birth' => $attributes['date_of_birth'],
            'blood_type' => $attributes['blood_type'],
            'street_id' => $street->id,
            'user_id' => $attributes['user_id']
        ];
        $data = PatientData::create($attr);
        return $this->success('data created', $data);
    }

    public function getClinicAppointments($attributes, $user_id)
    {
        if($attributes['role'] == 'S'){
            $secretary = Secretary::firstWhere('user_id', '=', $user_id);
            $clinic_secretary = ClinicSecretary::where('clinic_id', '=', $attributes['clinic_id'])
                ->where('secretary_id', '=', $secretary->id)
                ->first();
            if(is_null($clinic_secretary)){
                return $this->error('secretary does not work in this clinic', 403);
            }
        }
        else{
            $clinic = Clinic::find($attributes['clinic_id']);
            if($clinic->user_id != $user_id){
                return $this->error('secretary does not work in this clinic', 403);
            }
        }
        $clinic = Clinic::with(['doctors.appointments.user', 'doctors.appointments.treatment', 'doctors.user'])
            ->find($attributes['clinic_id']);
        if(!array_key_exists('date', $attributes)){
            return $this->success('appointments sent', $clinic->doctors);
        }
        $doctors = [];
        $required_date = Carbon::parse($attributes['date']);
        foreach($clinic->doctors as $doctor){
            $filtered_appointments = [];
            foreach($doctor->appointments as $appointment){
                $appointment_date = Carbon::parse($appointment->date);
                if($appointment_date->isSameDay($required_date)){
                    $filtered_appointments[] = $appointment;
                }
            }
            $doctors[] = [
                'id' => $doctor->id,
                'specialization' => $doctor->specialization,
                'experience_years' => $doctor->experience_years,
                'bio' => $doctor->bio,
                'phone' => $doctor->phone,
                'user' => $doctor->user,
                'appointments' => $filtered_appointments
            ];
        }

        return $this->success('appintments sent', $doctors);
    }

    public function updateProfile($attributes, $user_id){
        $secretary = Secretary::firstWhere('user_id', '=', $user_id);
        $secretary->update($attributes);
        return $this->success('secretary updated', $secretary);
    }
}
