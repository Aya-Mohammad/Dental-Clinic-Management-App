<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\ClinicDoctor;
use App\Models\ClinicSecretary;
use App\Models\Secretary;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function viewAny(User $user)
    {
        $role = request()->get('role');
        $clinic_id = request()->get('clinic_id');

        if ($role === 'D') {
            $doctor = Doctor::firstWhere('user_id', $user->id);
            return ClinicDoctor::where('clinic_id', $clinic_id)
                ->where('doctor_id', $doctor->id)
                ->exists();
        }

        if ($role === 'S') {
            $secretary = Secretary::firstWhere('user_id', $user->id);
            return ClinicSecretary::where('clinic_id', $clinic_id)
                ->where('secretary_id', $secretary->id)
                ->exists();
        }

        if ($role === 'M') {
            $clinic = Clinic::find($clinic_id);
            return $clinic && $clinic->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    public function view(User $user, User $model)
    {
        $role = request()->get('role');
        $clinic_id = request()->get('clinic_id');
        $appointment = Appointment::where('clinic_id', '=', $clinic_id)
            ->where('user_id', '=', $model->id)
            ->first();
        if (is_null($appointment)) {
            return false;
        }


        if ($role === 'D') {
            $doctor = Doctor::firstWhere('user_id', '=', $user->id);
            return ClinicDoctor::where('clinic_id', '=', $clinic_id)
                ->where('doctor_id', '=', $doctor->id)
                ->exists();
        }

        if ($role === 'S') {
            $secretary = Secretary::firstWhere('user_id', '=', $user->id);
            return ClinicSecretary::where('clinic_id', '=', $clinic_id)
                ->where('secretary_id', '=', $secretary->id)
                ->exists();
        }

        if ($role === 'M') {
            $clinic = Clinic::find($clinic_id);
            return $clinic && $clinic->user_id === $user->id;
        }

        return false;
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
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function update(User $user, User $model)
    {
        //
    }*/

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function delete(User $user, User $model)
    {
        //
    }*/

    /**
     * Determine whether the user can restore the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function restore(User $user, User $model)
    {
        //
    }*/

    /**
     * Determine whether the user can permanently delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\User  $model
     * @return \Illuminate\Auth\Access\Response|bool
     */
    /*public function forceDelete(User $user, User $model)
    {
        //
    }*/
}
