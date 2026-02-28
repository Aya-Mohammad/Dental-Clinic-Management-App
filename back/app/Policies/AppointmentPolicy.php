<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\ClinicSecretary;
use App\Models\Doctor;
use App\Models\Secretary;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppointmentPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Appointment $appointment)
    {
        $appt = Appointment::find(request()->get('appointment_id'));
        $doc = Doctor::find($appointment->doctor_id);
        if($doc && ($doc->user_id == $user->id)){
            return true;
        }
        return false;
    }

    public function cancel(User $user, Appointment $appointment)
    {
        if($appointment->status != 'UC'){
            return false;
        }
        if($user->id == $appointment->user_id){
            return true;
        }
        $doctor = Doctor::find($appointment->doctor_id);
        if($user->id == $doctor->user_id){
            return true;
        }
        $secretary = Secretary::firstWhere('user_id', '=', $user->id);
        if(is_null($secretary)){
            return false;
        }
        $treatment = Treatment::find($appointment->treatment_id);
        $clinic_secretary = ClinicSecretary::where('clinic_id', '=', $treatment->clinic_id)
            ->where('secretary_id', '=', $secretary->id)
            ->first();
        if($clinic_secretary){
            return true;
        }
        return false;
    }
}
