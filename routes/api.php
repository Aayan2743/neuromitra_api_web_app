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


Route::get('/send-notification', [NotificationController::class, 'send']);


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


    // Manager Flow

    // Route::post('/add-manager',[ManagerController::class,'store']);
    // Route::get('/view-manager/{id?}',[ManagerController::class,'viewmanager']); // use this for to fetch the manager details
    // Route::post('/update-manager/{id}',[ManagerController::class,'updatemanager']);
    // Route::get('/delete-manager/{id}',[ManagerController::class,'deletemanager']);

    // //building Flow
    // Route::post('/add-building',[buildingController::class,'store']);
    // Route::post('/logout',[CustomApiAuthController::class,'logout']);


    // // 2 step factor verify email or update R & D later 
    // Route::post('/send_verification_code',[TwoStepVerification::class,'send_verification_code'])->middleware('EmailTwoStepVerification');
    // Route::post('/verify_email',[TwoStepVerification::class,'verifyEmail'])->middleware('EmailTwoStepVerification');

    // // Message OTP Flow
    // Route::post('/Send-Otp',[Mgs91Controller::class,'SendOtp'])->middleware('Mgs91access');
    // Route::post('/Verify-Otp',[Mgs91Controller::class,'VerifyOtp'])->middleware('Mgs91access');



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

    // appouintments
    Route::post('create-appointment',[AppointmentController::class,'store']);

});






    // rozarpay Flow
    // Route::post('/createOrder',[RozarpayController::class,'createOrder'])->middleware('RozarPayAddOn');
    // Route::post('/verifyPayment',[RozarpayController::class,'verifyPayment'])->middleware('RozarPayAddOn');