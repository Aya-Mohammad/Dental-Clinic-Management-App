<?php

use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\SecretaryController;
use App\Http\Middleware\OptionalAuth;
use App\Models\Secretary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
//auth
use App\Http\Controllers\AuthController;
//manager
use App\Http\Controllers\Manager\ClinicController;
use App\Http\Controllers\Manager\ServiceController;
use App\Http\Controllers\Manager\AdvertismentController;
use App\Http\Controllers\Manager\PaymentController;
use App\Http\Controllers\Manager\ManagerController;
//admin
use App\Http\Controllers\AdminController;

/** auth */
Route::controller(AuthController::class)->group(function () {
    Route::post('/register/email', 'emailRegister');
    Route::post('/register/number', 'numberRegister');
    Route::post('/reset-password/otp', 'resetPasswordOTP');

    Route::post('/login/email', 'emailLogin');
    Route::post('/login/number', 'numberLogin');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/get-clinics-with-roles', 'getClinicsWithRoles');
        Route::post('/logout', 'logout');
        Route::post('/resend-otp', 'resendOTP');
        Route::post('/reset-password/verify', 'resetPasswordVerify');
        Route::post('/verify-otp', 'verify');
        Route::post('/user', 'fetchUser');
    });
});

/** admin */
Route::controller(AdminController::class)->prefix('admin')->middleware('auth:sanctum')->group(function () {
    Route::middleware('role:admin,manager')->group(function () {
        Route::post('/check/clinic-subscription', 'checkClinicSubscription');
        Route::post('/check/advertisment-subscription', 'checkAdvertismentSubscription');
        Route::post('/clinic-subscriptions', 'getClinicSubscription');
        Route::post('/advertisement-subscriptions', 'getAdvertisementSubscriptions');
        Route::get('/subscription/settings', 'getAllSubscriptionSettings');
    });

    Route::middleware('role:admin')->group(function () {
        Route::post('/set/clinic-subscription', 'setClinicSubscription');
        Route::post('/set/advertisement-subscription', 'setAdvertismentSubscription');
        Route::post('/advertisement/approve', 'approveAdvertisment');
        Route::post('/advertisement/reject', 'rejectAdvertisment');
        Route::post('/clinics/stats-services', 'clinicsStatsAndServices');
        Route::post('/subscriptions/stats', 'subscriptionsStats');
        Route::get('/payments/annual-stats', 'annualPaymentsStats');
        Route::get('/users-stats', 'usersStats');
        Route::post('all-advertisments', [AdvertismentController::class, 'getAllAdvertismentsWithClinics']);
    });
});


/** clinics */
Route::controller(ClinicController::class)->group(function () {
        Route::get('/get/clinics', 'getClinics');
        Route::post('/clinics/search', 'search');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/store/clinic', 'store');
        Route::post('/show/clinic', 'showClinic');
        Route::get('/clinic/images', 'getClinicImages');
        Route::get('/search/doctors', 'searchDoctors');
        Route::get('/search/secretaries', 'searchSecretaries');
        Route::get('/search/patients', 'searchPatients');

        Route::middleware('role:manager')->group(function () {
            Route::post('/add/image', 'addImage');
            Route::post('/delete/image', 'deleteImage');
            Route::post('/update/clinic', 'updateClinic');
            Route::get('/get/my-clinics', 'getMyClinics');
            Route::get('/clinic/patients', 'getClinicPatients');
            Route::get('/clinic/doctors', 'getClinicDoctors');
            Route::get('/clinic/secretaries', 'getClinicSecretaries');
            Route::post('/clinic/add-staff', 'addStaff');
            Route::post('/clinic/update-staff-working-hours', 'updateStaffWorkingHours');
            Route::get('/appointments', 'GetAppointments');
        });

        Route::middleware('role:doctor,secretary,manager')->group(function () {
            Route::post('/patients/search', 'search');
        });
        });
});

/** manager */
Route::controller(ManagerController::class)->group(function () {
    Route::middleware('auth:sanctum,role:manager')->group(function () {
    Route::post('/manager/users/block', 'blockUser');
    Route::post('/manager/users/unblock', 'unblockUser');
    Route::post('/manager/profile/update', 'updateProfile');
    Route::post('/patients-stats', 'patientsStats');
    Route::post('/services-usage', 'servicesUsage');
    Route::get('/notifications', 'getNotifications');
    Route::post('/manager/blocked-users', 'getBlockedUsers');
    });
});

/** advertisments */
Route::controller(AdvertismentController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/advertisement/show', 'showAdvertisment');

        Route::middleware('role:manager')->group(function () {
            Route::post('/advertisement/add-image', 'addAdvertismentImages');
            Route::post('/advertisement/delete-image', 'deleteAdvertismentImage');
            Route::post('/advertisement/images', 'getAdvertismentImages');
            Route::post('/advertisement/add', 'addAdvertisment');
            Route::post('/advertisement/create-payment-session', 'createPaymentSession');
            Route::post('/advertisement/update', 'updateAdvertisment');
            Route::post('/advertisements/resend-payment-link', 'resendPaymentLink');
            Route::post('clinic-advertisments', 'getClinicAdvertisments');
            Route::post('/advertisements/renew', 'renew');
        });
    });
});

/** services */
Route::controller(ClinicController::class)->group(function () {
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/services/search', 'search');

        Route::middleware('role:manager')->group(function () {
            Route::post('/add/service', 'addServiceToClinic');
            Route::post('/remove/service', 'removeServiceFromClinic');
            Route::post('/service/stages', 'getStages');
            Route::post('clinics/renew-subscription', 'renewSubscription');
        });

        Route::middleware('role:manager,doctor')->group(function () {
            Route::post('/show/clinic/service', 'showClinicServices');
            Route::get('/get/services', 'getServices');
           Route::get('/specializations', 'getSpecializations');
        });
    });
});

/** payments */
Route::controller(PaymentController::class)->group(function () {
    Route::get('/clinic/payment/success', 'paymentClinicSuccess')->name('clinic.payment.success');
    Route::get('/advertisment/payment/success', 'paymentAdvertisementSuccess')->name('advertisement.payment.success');
    Route::get('/payment/cancel', 'paymentCancel')->name('payment.cancel');
});

/** Secretary */
Route::controller(SecretaryController::class)->group(function () {
    Route::middleware('role:secretary')->group(function () {
        Route::post('/create/number', 'makeNumberAccount');
        Route::post('/create/email', 'makeEmailAccount');
        Route::post('/verify/number', 'verifyAccountByNumber');
        Route::post('/verify/email', 'verifyAccountByEmail');
        Route::post('/add-Inroduction', 'addTreatmentForPatient');
        Route::post('/add-patient-data', 'addPatientData');
        Route::post('/book-appointment', 'bookAppointmentForPatient');
        Route::post('/patient-stages', 'getAvailableStagesForPatient');
        Route::post('/stage-dates', 'getStageAvailableDates');
        Route::post('/accessible-services', 'getAccessibleServices');
    });
    Route::middleware('role:secretary,manager')->group(function () {
        Route::post('/get-appointments', 'getClinicAppointments');
        Route::post('/show-patient', 'showPatient');
    });
    Route::middleware('role:secretary,manager,doctor')->group(function () {
        Route::post('/patients', 'getPatients');
    });

    Route::middleware('auth:sanctum')->middleware('role:patient,secretary,doctor')
        ->post('/cancel-appointment', 'cancelAppointment');
});
//////////////__________________________________/////////////////////////
//patients
Route::controller(PatientController::class)->group(function () {
    Route::middleware(OptionalAuth::class)->group(function() {
        Route::get('/patient/home', 'home');
        Route::post('/patient/search', 'search');
        Route::post('/patient/show-clinic', 'showClinic');
        Route::get('/patient/get-clinics', 'getClinics');
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/patient/search-terms', 'getSearchTerms');
        Route::get('/patient/available-stages', 'getAvailableStages');
        Route::post('/patient/stage-available-dates', 'getStageAvailableDates');
        Route::get('/patient/upcoming-appointments', 'upcomingAppointments');
        Route::get('/patient/history', 'history');
        Route::get('/patient/ongoing-treatments', 'getOngoingTreatments');
        Route::get('/patient/treatments', 'getAllTreatments');
        Route::get('/patient/medical-record', 'showMedicalRecord');

        Route::post('/patient/update-image', 'updateImage');
        Route::post('/patient/update-location', 'updateLocation');
        Route::post('/patient/add-self-data', 'addSelfData');

        Route::post('/patient/accessible-services', 'getAccessibleServices');
        Route::post('/patient/book-appointment', 'bookAppointment');
        Route::post('/patient/add-introduction-treatment', 'addTreatment');

        Route::get('/patient/favourites', 'getFavourites');
        Route::post('/patient/add-favourite', 'addFavourite');
        Route::post('/patient/remove-favourite', 'removeFavourite');

        Route::post('/patient/add-review', 'addReview');
        Route::get('/advertisements', 'getAdvertisements');

    });
});

Route::controller(DoctorController::class)->group(function () {
    Route::middleware('role:doctor')->group(function () {
        Route::post('/doctor/show-patient', 'showPatient');
        Route::post('/doctor/teeth-services', 'getTeethServices');
        Route::post('/doctor/tooth-treatments', 'showToothTreatments');
        Route::post('/doctor/own-patients', 'getOwnPatients');
        Route::post('/doctor/own-appointments', 'getOwnAppointments');
        Route::post('/doctor/add-treatment', 'AddTreatment');
        Route::post('/doctor/update-treatment', 'updateTreatment');
        Route::post('/doctor/show-medical-record', 'showMedicalRecord');
        Route::post('/doctor/update-medical-record', 'updateMedicalRecord');
        Route::post('/doctor/update-appointment', 'updateAppointment');
        Route::post('/doctor/cancel-day-appointmetns', 'cancelDayAppointments');
        Route::get('/doctor/updatable-treatments', 'getUpdatableTreatments');
    });
});
