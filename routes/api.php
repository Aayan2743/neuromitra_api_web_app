<?php

use App\Mail\SendOtp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomApiAuthController;
use App\Http\Controllers\ManagerController;
use App\Http\Controllers\buildingController;
use App\Http\Controllers\TwoStepVerification;
use App\Http\Controllers\Mgs91Controller;
use App\Http\Controllers\RozarpayController;
use App\Http\Controllers\staffController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\assesementsController;
use App\Http\Controllers\Mobile\DashboardApi;
use App\Http\Controllers\Mobile\servicesController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PhonePaycontroller;
use App\Http\Controllers\ChildController;


Route::get('/send-notification', [NotificationController::class, 'send']);

Route::get('staff_engagged/{id}',[AppointmentController::class,'staff_engagged']);
Route::get('staff_free/{id}',[AppointmentController::class,'free_days']);



Route::group(['middleware'=>'api'],function($routes){


    //Mobile Api
    Route::post('/mobile-login',[CustomApiAuthController::class,'mobile_login']);
    Route::post('/register-user',[CustomApiAuthController::class,'register']);
    Route::post('/register-mobile-otp',[CustomApiAuthController::class,'registerOtp']);
    Route::post('/register-mobile-otp-verify',[CustomApiAuthController::class,'registerverifyOtp']);

    // login with Mgs91 
    Route::post('/login-otp',[CustomApiAuthController::class,'loginOtp']);
    Route::post('/verify-login-otp',[CustomApiAuthController::class,'verifyOtpLoginOTP']);

    // web Api
    Route::post('/web-login',[CustomApiAuthController::class,'web_login']);
    Route::post('/web-verify-login-otp',[CustomApiAuthController::class,'webverifyOtpLoginOTP']);

  
   // for both Web and Api only Email
    Route::post('/reset_password',[CustomApiAuthController::class,'reset_password_link']);
    Route::post('/verify_otp',[CustomApiAuthController::class,'verify_otp_update_password']);


    // Guest login
    Route::get('/users/guest-service/{id?}',[servicesController::class,'viewService']);
    Route::get('/users/guest-quatations',[DashboardApi::class,'randomQuote']);

 

});

Route::group(['middleware'=>['jwt.verify', 'checkAdmin']],function($routes){

    // Staff CURD
     Route::post('/add-staff',[staffController::class,'store']);
     Route::get('/view-staff/{id?}',[staffController::class,'viewStaff']);
     Route::put('/update-staff/{id?}', [staffController::class, 'update']);
     Route::delete('/deleted-staff/{id?}', [staffController::class, 'deleted']);

    // assesement Question for Kids
    Route::post('/add-questions',[assesementsController::class,'store']);
    Route::get('/view-questions/{id?}',[assesementsController::class,'viewQuestions']);
    Route::put('/update-questions/{id?}', [assesementsController::class, 'update']);
    Route::delete('/deleted-questions/{id?}', [assesementsController::class, 'deleted']);

    // assesement answers
    Route::get('/view-assesement-submission/{id?}',[assesementsController::class,'assesementsubmission']);

    //services i.e therapy councelling all curd
    Route::post('add-service',[servicesController::class,'store']);
    Route::get('view-service/{id?}',[servicesController::class,'viewService']);
    Route::delete('delete-service/{id?}',[servicesController::class,'deleteservice']);
    Route::post('/update-service/{id?}', [servicesController::class, 'update']);


    // profile details
    Route::get('view-profile_details',[ProfileController::class,'viewProfiles']);
    Route::post('profile-update_details',[ProfileController::class,'update']);

 
    // view appointments
    Route::get('view-appointments/{id?}',[AppointmentController::class,'index']);
    // Route::put('assigned-staff/{id}',[AppointmentController::class,'assign_to_staff']);
    Route::post('assigned-staff',[AppointmentController::class,'assign_to_staff']);


    // staff availability
 
    



});

Route::group(['middleware'=>['jwt.verify', 'checkUser'],  'prefix' => 'users'],function($routes){

    //Dashboard Api
    Route::get('quatations',[DashboardApi::class,'randomQuote']);
    Route::post('daily-feedback',[DashboardApi::class,'feedbackHealth']);


    //view services i.e therapy or councelling
    Route::get('view-service/{id?}',[servicesController::class,'viewService']);

    // Profile Gets
    Route::get('view-profile',[ProfileController::class,'viewProfiles']);
    Route::post('profile-update',[ProfileController::class,'update']);



       // create Appointment
       Route::post('create-appointment-request',[AppointmentController::class,'store']);
       

       // get_child_details
       Route::get('get-child-details/{id?}',[ChildController::class,'index']);
       Route::post('add-new-child',[ChildController::class,'store']);
       Route::put('edit-child/{id}',[ChildController::class,'update']);
       Route::delete('delete-child/{id}',[ChildController::class,'delete']);

});

// test on phonepay

// Route::post('/create_phonepay',[PhonePaycontroller::class,'phonePePayment']);
// Route::post('/create_phonepay',[PhonePaycontroller::class,'phonePePayment']);
// Route::any('/redirct-url', [PhonePaycontroller::class, 'callBackAction']);





    // rozarpay Flow
    // Route::post('/createOrder',[RozarpayController::class,'createOrder'])->middleware('RozarPayAddOn');
    // Route::post('/verifyPayment',[RozarpayController::class,'verifyPayment'])->middleware('RozarPayAddOn');