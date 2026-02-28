<?php

namespace App\Services;

use App\Models\Advertisment;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\ClinicUser;
use App\Models\MedicalRecord;
use App\Models\Block;
use App\Models\Review;
use App\Models\SearchHistory;
use App\Models\Service;
use App\Models\ServiceTreatment;
use App\Models\ServiceStage;
use App\Models\Stage;
use App\Models\User;
use App\Models\City;
use App\Models\Street;
use App\Models\PatientData;
use App\Models\Treatment;
use App\Traits\Responses;
use Illuminate\Support\Arr;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PatientService
{
    use Responses;

    public function home()
    {
        $data['advertisments'] = Advertisment::paginate(10);
        return $this->success('Data sent', $data, 200);
    }

    public function getClinics($user)
    {
        if($user){
            $user_id = $user->id;
        }
        else{
            $user_id = null;
        }
        $clinics = Clinic::with(['street.city'])
            ->leftJoin('clinic_user', function ($join) use ($user_id) {
                $join->on('clinics.id', '=', 'clinic_user.clinic_id')
                    ->where('clinic_user.user_id', '=', $user_id);
            })
            ->select('clinics.*', DB::raw('IF(clinic_user.id IS NOT NULL, 1, 0) as is_favourite'))
            ->get();
        $res = [];
        $res['clinics'] =[];
        foreach($clinics as $clinic){
            $res['clinics'][] = [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'bio' => $clinic->bio,
                'phone' => $clinic->phone,
                'street' => $clinic->street->name,
                'city' => $clinic->street->city->name,
                'is_favourite' => $clinic->is_favourite,
            ];
        }
        return $this->success('clinics sent', $res);
    }

    public function search($attributes, $user)
    {
        if($user){
            $user_id = $user->id;
            SearchHistory::firstOrCreate([
                'search_term' => $attributes['keyword'],
                'user_id' => $user_id
            ]);
        }
        else{
            $user_id = null;
        }
        $keyword = '%' . $attributes['keyword'] . '%';
        $clinics = Clinic::with(['clinicImages'])
            ->where('name', 'like', $keyword)
            ->leftjoin('clinic_user', function ($join) use ($user_id) {
                $join->on('clinics.id', '=', 'clinic_user.clinic_id')
                    ->where('clinic_user.user_id', '=', $user_id);
            })
            ->select('clinics.*', DB::raw('IF(clinic_user.id IS NOT NULL, 1, 0) as is_favourite'))
            ->get();

        return $this->success('Clinics sent', $clinics, 200);
    }

    public function showClinic($attributes, $user)
    {
        if($user){
            $user_id = $user->id;
        }
        else{
            $user_id = null;
        }
        $clinic = Clinic::with(['clinicImages', 'street.city', 'user', 'workingHours'])
            ->select('clinics.*')
            ->selectRaw('EXISTS (
                SELECT 1 FROM clinic_user
                WHERE clinic_user.clinic_id = clinics.id
                AND clinic_user.user_id = ?
            ) as is_favourite', [$user_id])
            ->find($attributes['clinic_id']);

        return $this->success('Clinic sent', $clinic, 200);
    }

    public function getOngoingTreatments($id){
        $treatments = Treatment::with(['clinic'])
            ->where('user_id', '=', $id)
            ->where('status', '!=', 'X')
            ->where('status', '!=', 'C')
            ->get();
        return $this->success('ongoing treatments sent', $treatments);
    }

    public function getAllTreatments($id){
        $treatments = Treatment::with(['clinic'])
            ->where('user_id', '=', $id)
            ->get();
        return $this->success('treatments sent', $treatments);
    }

    public function getAvailableStages($id)
    {
        $active_treatments = Treatment::where('user_id', '=', $id)
            ->where('status', '=', 'UC')
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
                    ->sortBy('time')
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

    public function getFavourites($user_id)
    {
        $user = User::with('favourites')
            ->find($user_id);
        return $this->success('favourite clinics sent', $user->favourites);
    }

    public function updateImage($attributes, $user_id)
    {
        $user = User::find($user_id);
        if($user->profile_image && Storage::disk('public')->exists($user->profile_image)){
            Storage::disk('public')->delete($user->profile_image);
        }

        $path = $attributes['image']->store('user_images', 'public');
        $user->profile_image = $path;
        $user->save();
        return $this->success('profile image updated', $user);

    }

    public function updateLocation($attributes, $user_id)
    {
        $city = City::where('name', '=', $attributes['city'])
            ->first();
        if (is_null($city)) {
            $city = City::create(['name' => $attributes['city']]);
        }
        $street = Street::where('name', '=', $attributes['street'])
            ->where('city_id', '-=', $city->id)
            ->first();
        if (is_null($street)) {
            $street = Street::create([
                'name' => $attributes['street'],
                'city_id' => $city->id
            ]);
        }
        $patient_data = PatientData::firstWhere('user_id', '=', $user_id);
        $patient_data->street_id = $street->id;
        $patient_data->save();
        return $this->success('location updated', $patient_data);
    }

    public function bookAppointment($attributes, $user_id)
    {
        $block = Block::where('user_id', '=', $user_id)
            ->where('clinic_id', '=', $attributes['clinic_id'])
            ->first();
        if(!is_null($block)){
            return $this->error('patient block by manager', 403);
        }
        $clinic = Clinic::find($attributes['clinic_id']);
        $expire_date = Carbon::parse($clinic->subscribed_at);
        $expire_date->addDays($clinic->subscription_duration_days);
        if($expire_date->lt(now())){
            return $this->error("can't book appointment in this clinic, contact manager", 401);
        }
        $appointments = Appointment::where('clinic_id', '=', $attributes['clinic_id'])
            ->where('doctor_id', '=', $attributes['doctor_id'])
            ->whereDate('date', '=', $attributes['date'])
            ->orderBy('time', 'asc')
            ->get();
        $time = Carbon::createFromTimeString($attributes['time']);
        if(array_key_exists('treatment_id', $attributes)){
            $treatment = Treatment::find($attributes['treatment_id']);
        }
        else{
            $treatments = Treatment::where('clinic_id', '=', $attributes['clinic_id'])
                ->where('user_id', '=', $user_id)
                ->get();
            foreach($treatments as $tr){
                if($tr->status == 'UC' || $tr->status == 'A'){
                    return $this->error('patient already have an active treatment in this clinic', 401);
                }
            }
            $attr['services'] = 1;
            $attr['service_number'] = 1;
            $attr['status'] = 'UC';
            $attr['user_id'] = $user_id;
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
        $appointment = Appointment::create([
            'date' => $attributes['date'],
            'time' => $attributes['time'],
            'duration' => $duration,
            'status' => 'UC',
            'clinic_id' => $attributes['clinic_id'],
            'treatment_id' => $treatment->id,
            'doctor_id' => $attributes['doctor_id'],
            'user_id' => $user_id,
        ]);
        $treatment->status = 'A';
        $treatment->save();
        return $this->success('appointment booked', $appointment);
    }

    public function getAccessibleServices($attributes){
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

    public function addTreatment($attributes)
    {
        $attr['services'] = 1;
        $attr['service_number'] = 1;
        $attr['status'] = 'UC';
        $attr['user_id'] = $attributes['user_id'];
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
        return $this->success('treatment added', $treatment);
    }

    public function addFavourite($attributes)
    {
        $clinic_user = ClinicUser::where('clinic_id', '=', $attributes['clinic_id'])
            ->where('user_id', '=', $attributes['user_id'])
            ->first();
        if (!is_null($clinic_user)) {
            return $this->error('clinic already in favourites', 401);
        }
        ClinicUser::create($attributes);
        return $this->success('clinic added to favourites', []);
    }
    public function removeFavourite($attributes)
    {
        $clinic_user = ClinicUser::where('clinic_id', '=', $attributes['clinic_id'])
            ->where('user_id', '=', $attributes['user_id'])
            ->first();
        if (is_null($clinic_user)) {
            return $this->error('clinic is not in favourites', 401);
        }
        $clinic_user->delete();
        return $this->success('clinic removed from favourites', []);
    }

    public function addSelfData($attributes)
    {
        $check = PatientData::firstWhere('user_id', '=', $attributes['user_id']);
        if($check){
            return $this->error('patient already has data', 401);
        }
        if(array_key_exists('city', $attributes) && array_key_exists('street', $attributes)){
            $city = City::firstOrCreate(
                ['name' => $attributes['city']]
            );
            $street = Street::firstOrCreate(
                ['city_id' => $city->id],
                ['name' => $attributes['street']]
            );
            $street_id = $street->id;
        }
        else {
            $street_id = null;
        }
        $attr = [
            'gender' => $attributes['gender'],
            'date_of_birth' => $attributes['date_of_birth'],
            'blood_type' => $attributes['blood_type'],
            'street_id' => $street_id,
            'user_id' => $attributes['user_id']
        ];
        $data = PatientData::create($attr);
        return $this->success('data created', $data);
    }

    public function upcomingAppointments($user_id){
        $appointments = Appointment::with(['clinic', 'doctor', 'treatment'])
            ->where('user_id', '=', $user_id)
            ->where('status', '=', 'UC')
            ->get();
        return $this->success('upcoming appointments sent', $appointments);
    }

    public function history($user_id){
        $appointments = Appointment::with(['clinic', 'doctor', 'treatment'])
            ->where('user_id', '=', $user_id)
            ->get();
        return $this->success('all appointments sent', $appointments);
    }

    public function addReview($attributes, $user_id){
        $treatment = Treatment::find($attributes['treatment_id']);
        if($treatment->user_id != $user_id){
            return $this->error("treatment should be yours to be able to add a review", 403);
        }
        $review = Review::firstWhere('treatment_id', '=', $treatment->id);
        if(!is_null($review)){
            return $this->error('treatment already has a review', 401);
        }
        $review = Review::create([
            'rating' => $attributes['rating'],
            'comment' => $attributes['comment'],
            'treatment_id' => $treatment->id,
            'clinic_id' => $treatment->clinic_id,
            'user_id' => $treatment->user_id
        ]);
        return $this->success('review added', $review);
    }

    public function showMedicalRecord($user_id){
        $medical_record = MedicalRecord::firstWhere('user_id', '=', $user_id);
        return $this->success('medical record sent', $medical_record);
    }

    /** Get all approved advertisements with clinic ID and name. */
    public function getAdvertisements(): array //admin
    {
        $ads = Advertisment::with(['clinic:id,name', 'images'])
            ->where('status', 'approved')
            ->get();
        
        $ads2 = [];
        foreach($ads as $adv){
            $expire_date = Carbon::parse($adv->subscribed_at);
            $expire_date->addDays($adv->subscription_duration_days);
            if($expire_date->lt(now())){
                continue;
            }
            $ads2[] = $adv;
        }
        $ret = collect($ads2)->map(function($ad){
            return [
                'id' => $ad->id,
                'title' => $ad->title,
                'description' => $ad->description,
                'status' => $ad->status,
                'clinic' => [
                    'id' => $ad->clinic->id,
                    'name' => $ad->clinic->name,
                ],
                'images' => $ad->images,
            ];
        });
        return [
            'status' => true,
            'message' => 'All approved advertisements retrieved successfully',
            'data' => $ret,
        ];
    }

    public function getSearchTerms($id){
        $terms = SearchHistory::where('user_id', '=', $id)
            ->get();
        return $this->success('search history sent', $terms);
    }
}
