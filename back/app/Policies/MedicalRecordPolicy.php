<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\MedicalRecord;
use App\Models\PatientData;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MedicalRecordPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function viewAny(User $user)
    {
        //
    }*/

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MedicalRecord  $medicalRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, MedicalRecord $medicalRecord)
    {
        $user_id = request()->user()->id;
        $doctor = Doctor::firstWhere('user_id', '=', $user_id);
        $appointment = Appointment::where('doctor_id', '=', $doctor->id)
            ->where('user_id', '=', request()->get('user_id'))
            ->first();
        if(is_null($appointment)){
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function create(User $user)
    {
        //
    }*/

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MedicalRecord  $medicalRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, MedicalRecord $medicalRecord)
    {
        $user_id = request()->get('user_id');
        $doctor = Doctor::firstWhere('user_id', '=', request()->user()->id);
        $appointment = Appointment::where('user_id', '=', $user_id)
            ->where('doctor_id', '=', $doctor->id)
            ->first();
        if(is_null($appointment)){
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MedicalRecord  $medicalRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function delete(User $user, MedicalRecord $medicalRecord)
    {
        //
    }*/

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MedicalRecord  $medicalRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function restore(User $user, MedicalRecord $medicalRecord)
    {
        //
    }*/

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\MedicalRecord  $medicalRecord
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function forceDelete(User $user, MedicalRecord $medicalRecord)
    {
        //
    }*/
}
