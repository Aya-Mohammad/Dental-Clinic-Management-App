<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\GetClinicsWithRolesRequest;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\{
    EmailRegisterRequest,
    EmailLoginRequest,
    NumberLoginRequest,
    NumberRegisterRequest,
    VerifyAccountRequest,
    ResetPasswordOTPRequest,
    ResetPasswordVerifyRequest,
    ResendOtpRequest
};
use App\Services\AuthService;

class AuthController extends Controller
{
    protected $service;
    public function __construct(AuthService $service){
        $this->service = $service;
    }

    public function numberRegister(NumberRegisterRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->numberRegister($attributes);
    }

    public function emailRegister(EmailRegisterRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->emailRegister($attributes);
    }

    public function numberLogin(NumberLoginRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->numberLogin($attributes);
    }
    public function emailLogin(EmailLoginRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->emailLogin($attributes);
    }

    public function logout(Request $request){
        return $this->service->logout($request);
    }

    public function resendOTP(ResendOTPRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['id'] = $request->user()->id;
        return $this->service->resendOTP($attributes);
    }

    public function verify(VerifyAccountRequest $request){
        $attributes = $request->validate($request->rules());
        $attributes['id'] = $request->user()->id;
        return $this->service->verify($attributes);
    }

    public function resetPasswordOTP(ResetPasswordOTPRequest $request){
        $attributes = $request->validate($request->rules());
        return $this->service->resetPasswordOTP($attributes);
    }

    public function resetPasswordVerify(ResetPasswordVerifyRequest $request)
    {
        $attributes = $request->validated();
        $attributes['id'] = $request->user()->id;
        return $this->service->resetPasswordVerify($attributes);
    }

    public function fetchUser(Request $request)
    {
        return $this->service->fetchUser($request);
    }

    public function getClinicsWithRoles(GetClinicsWithRolesRequest $request)
    {

        return $this->service->getClinicsWithRoles($request->user()->id);
    }
}
