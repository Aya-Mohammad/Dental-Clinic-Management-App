<?php

namespace App\Services;

use App\Models\Clinic;
use App\Models\ClinicDoctor;
use App\Models\ClinicSecretary;
use App\Models\Secretary;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Doctor;
use App\Traits\Responses;
use App\Notifications\MailNotification;
use App\Permissions\Abilities;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuthService
{
    use Responses;

    private function returnTokenResponse($request, $user, $token, $message)
    {
        $data = ['user' => $user];
        $clientType = $request->header('X-Client-Type');

        if ($clientType === 'web') {
            return $this->success(__($message), $data, 200)
                ->cookie('token', $token, 60 * 24 * 30, '/', null, true, true, true, 'None');
        }

        $data['token'] = $token;
        return $this->success(__($message), $data, 200);
    }

    private function extractToken($request)
    {
        $token = $request->bearerToken();
        if (!$token && $request->hasCookie('token')) {
            $token = $request->cookie('token');
        }
        return $token;
    }

    public function numberRegister($attributes)
    {
        $attributes['password'] = Hash::make($attributes['password']);
        $otp = (string) rand(100000, 999999);
        Log::info('otp recieved: ', ['otp' => $otp]);
        $attributes['otp'] = Hash::make($otp);
        $attributes['expire_at'] = now()->addHour();

        $user = User::create($attributes);
        $user->addRoleByName('patient');


        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;

        return $this->returnTokenResponse(request(), $user, $token, 'Account created');
    }

    public function emailRegister($attributes)
    {
        $attributes['password'] = Hash::make($attributes['password']);
        $otp = (string) rand(100000, 999999);
        Log::info('otp recieved: ', ['otp' => $otp]);
        $attributes['otp'] = Hash::make($otp);
        $attributes['expire_at'] = now()->addHour();

        $user = User::create($attributes);
        $user->addRoleByName('patient');
        $user->notify(new MailNotification($otp));

        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;

        return $this->returnTokenResponse(request(), $user, $token, 'Account created');
    }

    public function numberLogin($attributes)
    {
        $credentials = Arr::only($attributes, ['number', 'password']);

        if (!Auth::attempt($credentials)) {
            return $this->error(__('invalid credentials'), 0);
        }

        $user = User::firstWhere('number', $credentials['number']);

        if (isset($attributes['fcm_token'])) {
            $user->fcm_token = $attributes['fcm_token'];
            $user->save();
        }

        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;

        return $this->returnTokenResponse(request(), $user, $token, 'Login successfully');
    }

    public function emailLogin($attributes)
    {
        $credentials = Arr::only($attributes, ['email', 'password']);

        if (!Auth::attempt($credentials)) {
            return $this->error(__('invalid credentials'), 0);
        }

        $user = User::firstWhere('email', $credentials['email']);
        $user->fcm_token = $attributes['fcm_token'] ?? null;
        $user->save();

        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;

        return $this->returnTokenResponse(request(), $user, $token, 'Login successfully');
    }

    public function verify($attributes)
    {
        $user = User::find($attributes['id']);

        if (now()->greaterThan($user->expire_at)) {
            return $this->error(__('OTP expired'), 1);
        }
        if (!Hash::check($attributes['otp'], $user->otp)) {
            return $this->error(__('Invalid OTP'), 0);
        }

        $user->verified_at = now();
        $user->otp = null;
        $user->save();

        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;
        $user->load('roles');

        return $this->returnTokenResponse(request(), $user, $token, 'Verified successfully');
    }

    public function fetchUser($request)
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->error(__('Token not provided'), 0);
        }
        $user = $request->user()->load('roles');

        if (!$user) {
            return $this->error(__('Invalid or expired token'), 0);
        }

        return $this->returnTokenResponse(request(), $user, $token, 'User fetched successfully');
    }

    public function logout($request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        $user->fcm_token = null;
        $user->save();

        $clientType = $request->header('X-Client-Type');
        $response = $this->success(__('Logout successfully'), (object)[], 200);

        if ($clientType === 'web') {
            $response = $response->withoutCookie('token');
        }

        return $response;
    }

    public function resendOTP($attributes)
    {
        $otp = (string) rand(100000, 999999);
        Log::info('otp received: ', ['otp' => $otp]);
        $user = User::find($attributes['id']);
        $user->otp = Hash::make($otp);
        $user->expire_at = now()->addHour();
        $user->save();
        $user->notify(new MailNotification($otp));
        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;

        return $this->returnTokenResponse(request(), $user, $token, 'OTP sent');
    }

    public function resetPasswordOTP(array $attributes)
    {
        $user = User::firstWhere('email', $attributes['email']);
        if (!$user) {
            return $this->error(__('User not found.'), 404);
        }

        $otp = (string) rand(100000, 999999);
        $user->otp = Hash::make($otp);
        $user->expire_at = now()->addHour();
        $user->save();

        $token = $user->createToken('password-reset-token')->plainTextToken;
        $user->notify(new MailNotification($otp));

        return $this->returnTokenResponse(request(), $user, $token, 'OTP sent successfully.');
    }

    public function resetPasswordVerify($attributes)
    {
        $user = User::find($attributes['id']);

        if (!$user) {
            return $this->error(__('User not found'), 404);
        }
        if (now()->greaterThan($user->expire_at)) {
            return $this->error(__('OTP expired'), 1);
        }
        if (!Hash::check($attributes['otp'], $user->otp)) {
            return $this->error(__('Invalid OTP'), 0);
        }

        $user->password = Hash::make($attributes['new_password']);
        $user->otp = null;
        $user->expire_at = null;
        $user->save();

        $abilities = Abilities::getAbilities($user);
        $token = $user->createToken('user token for ' . $user->first_name, $abilities)->plainTextToken;
        $user->load('roles');

        return $this->returnTokenResponse(request(), $user, $token, 'Password reset successfully');
    }

    public function getClinicsWithRoles($id)
    {
        $manager_ids = Clinic::where('user_id', '=', $id)
            ->orderBy('id')
            ->pluck('id')
            ->toArray();

        $doctor = Doctor::firstWhere('user_id', '=', $id);
        $doctor_ids = $doctor ? ClinicDoctor::where('doctor_id', '=', $doctor->id)
            ->orderBy('clinic_id')
            ->pluck('clinic_id')
            ->toArray() : [];

        $secretary = Secretary::firstWhere('user_id', '=', $id);
        $secretary_ids = $secretary ? ClinicSecretary::where('secretary_id', '=', $secretary->id)
            ->orderBy('clinic_id')
            ->pluck('clinic_id')
            ->toArray() : [];

        $clinic_ids = array_unique(array_merge($manager_ids, $doctor_ids, $secretary_ids));
        $clinics = Clinic::whereIn('id', $clinic_ids)->get();

        $clinic_roles = [];
        foreach ($clinics as $clinic) {
            $roles = [];
            if (in_array($clinic->id, $manager_ids)) $roles[] = 'manager';
            if (in_array($clinic->id, $doctor_ids)) $roles[] = 'doctor';
            if (in_array($clinic->id, $secretary_ids)) $roles[] = 'secretary';

            $expire_date = Carbon::parse($clinic->subscribed_at);
            $expire_date->addDays($clinic->subscription_duration_days);
            $subscription_valid = true;
            if($expire_date->lt(now())){
                $subscription_valid = false;
            }
            $clinic_roles[] = [
                'clinic' => [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'still_subscriped' => $subscription_valid
                ],
                'roles' => $roles
            ];
        }
        return $this->success(__('clinics sent'), $clinic_roles);
    }
}
