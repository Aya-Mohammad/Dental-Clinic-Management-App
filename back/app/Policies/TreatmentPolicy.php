<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\ClinicService;
use App\Models\Doctor;
use App\Models\Treatment;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TreatmentPolicy
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
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function view(User $user, Treatment $treatment)
    {
        //
    }*/

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function create(User $user)
    {
        $services = request()->get('services');
        $clinic_id = request()->get('clinic_id');
        $role = request()->get('role');
        $secretaryAccessible = true;
        foreach($services as $service){
            $clinic_service = ClinicService::where('clinic_id', '=', $clinic_id)
                ->where('service_id', '=', $service['service_id'])
                ->first();
            if(is_null($clinic_service)){
                return false;
            }
            if($clinic_service->accessibility == 'D'){
                $secretaryAccessible = false;
            }
        }
        if($role != 'D' ){
            if(!$secretaryAccessible){
                return false;
            }
        }
        if($role == 'P'){
            $treatments = Treatment::where('clinic_id', '=', $clinic_id)
                ->where('user_id', '=',request()->user()->id)
                ->get();
            foreach($treatments as $treatment){
                if($treatment->status == 'UC' || $treatment->status == 'A'){
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function update(User $user, Treatment $treatment)
    {
        $doctor = Doctor::where('user_id', '=', $user->id)
            ->first();
        $appointment = Appointment::where('treatment_id', '=', $treatment->id)
            ->where('doctor_id', '=', $doctor->id)
            ->get()
            ->last();
        if(is_null($appointment)){
            return false;
        }
        if($appointment->doctor_id != $doctor->id){
            return false;
        }
        return true;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function delete(User $user, Treatment $treatment)
    {
        //
    }*/

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function restore(User $user, Treatment $treatment)
    {
        //
    }*/

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Treatment  $treatment
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function forceDelete(User $user, Treatment $treatment)
    {
        //
    }*/
}
