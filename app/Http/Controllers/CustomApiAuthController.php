<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendOtp;
use App\Models\msg91;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\FirebaseService;



class CustomApiAuthController extends Controller
{

    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }


    //login with OTP  for mobile
    public function loginOtp(Request $request)   {
      
        $validator=Validator::make($request->all(),[
            'phone'=>'required|digits:10',
            
        ]);

            if ($validator->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ]);
            }

            try{
                $checkmobileExist=User::where('contact', $request->phone)->first();
                
                // dd(  $checkmobileExist->unique_hostal_id)

                if($checkmobileExist){

                    if($checkmobileExist->deleted_at==1){
                        return response()->json([
                            'status'=>false,
                            'message'=>'User Deleted',
                        ]);
                    }

                    
                    $checkMsg91=msg91::where('company_id',$checkmobileExist->unique_hostal_id)->first();

                   
                    if($checkMsg91){
                        $authKey = $checkMsg91->authKey; // keep your key in .env
                        $senderId =$checkMsg91->senderId;
                        $templateId = $checkMsg91->templateId; // required for transactional messages
                        
                        try{
                            $response = Http::post('https://control.msg91.com/api/v5/otp', [
                                'authkey' => $authKey,
                                'template_id' => $templateId,
                                'mobile' => '91' . $request->phone,
                                'sender' => $senderId,
                            ]);
                        
                            $responseData = $response->json();

                            // dd($responseData);
                            if (isset($responseData['type']) && $responseData['type'] === 'success') {
                                // OTP sent successfully
                                return response()->json([
                                    'status'=>true,
                                    'message' => $responseData['type'] ], 200);
                            } else {
                                // Log and handle the error
                                // Log::error('MSG91 OTP Send Failed', $responseData);
                                return response()->json([
                                    'status'=>false,
                                    'message' => $responseData['message'] ?? 'Failed to send OTP'], 200);
                            }


                        }catch (\Exception $e){
                            return response()->json([
                                'status'=>false,
                                'message'=>$e->getMessage(),
                            ]);
                        }
                
                      
                    }else{
                        return response()->json([
                            'status'=>false,
                            'message'=>'Mgs91 Not Integrated',
                        ]);
                    }

                   
                }else{
                    return response()->json([
                        'status'=>false,
                        'message'=>'Invalid Mobile Number',
                    ]);
                }
                
            }catch(\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=> $e->getMessage(),
                ]);
            }
     
       
     
    }
     //verify Login with OTP for mobile
    public function verifyOtpLoginOTP(Request $request) {

        $validator=Validator::make($request->all(),[
            'phone'=>'required|digits:10',
            'otp'=>'required',
            'fcm_token'=>'required',

        ]);
        if ($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=> $validator->errors()->first(),
            ]);
        }

        $checkmobileExist=User::where('contact', $request->phone)->first();

        if(!$checkmobileExist){
            return response()->json([
                'status'=>false,
                'message'=> 'Mobile Number Not found',
            ]);
        }

        $checkMsg91=msg91::where('company_id',$checkmobileExist->unique_hostal_id)->first();
        if($checkMsg91){
     
            $authKey = $checkMsg91->authKey;
     
            try{
                $response = Http::get('https://control.msg91.com/api/v5/otp/verify', [
                    'authkey' => $authKey,
                    'mobile' => '91' . $request->phone,
                    'otp' => $request->otp,
                ]);
            
                if (isset($response['type']) && $response['type'] == 'success') {
                    // OTP verified

                    $user = User::where('contact', $request->phone)->first();
                    
                    if (!$user) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Mobile number not found',
                        ], 200);
                    }
                    
                    // manually login the user
                    $token = auth()->login($user);
                    
                    $role = $user->role;
                    if($role!="User"){
                        return response()->json([
                            'status' => false,
                            'message' => 'Only User allowed',
                        ], 200);
                    }

                    $refreshToken = Str::random(60);
                    
                    // store hashed refresh token
                    $user->refresh_token = hash('sha256', $refreshToken);
                    $user->device_fcm_token = $request->fcm_token;
                    $user->save();
                    

                    
                    return response()->json([
                        'access_token' => $token,
                        'refresh_token' => $refreshToken,
                        'token_type' => 'bearer',
                        'role'=>$role,
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ]);


                  
        
                } else {
                    // OTP invalid
        
                    return response()->json([
                        'status'=>false,
                        'message'=> $response['message'],
                    ]);
        
                }

            }catch(\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=> $e->getMessage(),
                ]);
            }
           
        }else{
            return response()->json([
                'status'=>false,
                'message'=> 'Mgs91 Not Integrated',
            ]);
        }
       
      
    }

    // verify login for web
    public function webverifyOtpLoginOTP(Request $request) {

        $validator=Validator::make($request->all(),[
            'phone'=>'required|digits:10',
            'otp'=>'required',
            'fcm_token'=>'required'

        ]);
        if ($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=> $validator->errors()->first(),
            ]);
        }

        $checkmobileExist=User::where('contact', $request->phone)->first();

        if(!$checkmobileExist){
            return response()->json([
                'status'=>false,
                'message'=> 'Mobile Number Not found',
            ]);
        }

        $checkMsg91=msg91::where('company_id',$checkmobileExist->unique_hostal_id)->first();
        if($checkMsg91){
     
            $authKey = $checkMsg91->authKey;
     
            try{
                $response = Http::get('https://control.msg91.com/api/v5/otp/verify', [
                    'authkey' => $authKey,
                    'mobile' => '91' . $request->phone,
                    'otp' => $request->otp,
                ]);
            
                if (isset($response['type']) && $response['type'] == 'success') {
                    // OTP verified

                    $user = User::where('contact', $request->phone)->first();
                    
                    if (!$user) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Mobile number not found',
                        ], 200);
                    }
                    
                    // manually login the user
                    $token = auth()->login($user);
                    
                    $role = $user->role;
                    if($role=="User"){
                        return response()->json([
                            'status' => false,
                            'message' => 'User Not allowed',
                        ], 200);
                    }
                    $refreshToken = Str::random(60);
                    
                    // store hashed refresh token
                    $user->refresh_token = hash('sha256', $refreshToken);
                    $user->web_fcm_token = $request->fcm_token;
                    $user->save();
                    

                    
                    return response()->json([
                        'status'=>true,
                        'access_token' => $token,
                        'refresh_token' => $refreshToken,
                        'token_type' => 'bearer',
                        'role'=>$role,
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ]);


                  
        
                } else {
                    // OTP invalid
        
                    return response()->json([
                        'status'=>false,
                        'message'=> $response['message'],
                    ]);
        
                }

            }catch(\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=> $e->getMessage(),
                ]);
            }
           
        }else{
            return response()->json([
                'status'=>false,
                'message'=> 'Mgs91 Not Integrated',
            ]);
        }
       
      
    }


    // general register approach
    public function register(Request $req){
        // dd($req->all());
        $validator=Validator::make($req->all(),[
            'name'=>'required|string|min:2|max:100',
            'email'=>'required|string|email|max:100|unique:users',
            'password'=>'required|min:6',
            'contact'=>'required|digits:10|unique:users,contact',
            'fcm_token'=>'required',
        ]);
    
        if($validator->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ],
               200);
        }
    
        try{
            $code = substr(str_replace('.', '', microtime(true)), -8);

            $userresult=User::create([
                'name'=>$req->name,
                'email'=>$req->email,
                'password'=>Hash::make($req->password),
                'contact'=>$req->contact,
                'unique_hostal_id'=>env('SAAS_KEY'),
                'role'=>'User',
            ]);

            $token = JWTAuth::fromUser($userresult); // Generate JWT token for the new user

            $refreshToken = Str::random(60);
            $userresult->refresh_token = hash('sha256', $refreshToken);
            $userresult->device_fcm_token = $req->fcm_token;
            $userresult->save();

        
            $role = $userresult->role;
        
            $adminFcmTokens = User::where('role', 'Admin')
                      ->whereNotNull('web_fcm_token')
                      ->pluck('web_fcm_token')
                      ->toArray();

                
                      if (empty($adminFcmTokens)) {
                       
                        
                        return response()->json([
                            'status' => false,
                            'message' => 'No Admin FCM tokens available for notifications.'
                        ], 200);
                    }

                      
              
             
                // $title = 'Registration';
                // $body = 'New User Registered';
                // $responses = [];

                // foreach ($adminFcmTokens as $deviceToken) {
                //     $response = $this->firebase->sendNotification($deviceToken, $title, $body);
                //     $responses[] = $response; 
                // }

            
        

            return response()->json([
                'status'=>true,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'role'=>$role,
                'expires_in' => auth()->factory()->getTTL() * 60,
                // 'Fcm_Notification_response' => $response 
            ]);




             

        
        }catch(\Exception $e){
            return response()->json([
                'status'=>false,
                'message' =>$e->getMessage(),
                
                
            ]);
        }

       
       
    }


    // register with Otp
    public function registerOtp(Request $request){
     
        $validator=Validator::make($request->all(),[
           
            'phone'=>'required|digits:10|unique:users,contact'
        ]);
    
        if($validator->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ],
               200);
        }




        $checkMsg91=msg91::where('company_id',env('SAAS_KEY'))->first();

                   
        if($checkMsg91){
            $authKey = $checkMsg91->authKey; // keep your key in .env
            $senderId =$checkMsg91->senderId;
            $templateId = $checkMsg91->templateId; // required for transactional messages
            
            try{
                $response = Http::post('https://control.msg91.com/api/v5/otp', [
                    'authkey' => $authKey,
                    'template_id' => $templateId,
                    'mobile' => '91' . $request->phone,
                    'sender' => $senderId,
                ]);
            
                $responseData = $response->json();

                // dd($responseData);
                if (isset($responseData['type']) && $responseData['type'] === 'success') {
                    // OTP sent successfully
                    return response()->json([
                        'status'=>true,
                        'message' => $responseData['type'] ], 200);
                } else {
                    // Log and handle the error
                    // Log::error('MSG91 OTP Send Failed', $responseData);
                    return response()->json([
                        'status'=>false,
                        'message' => $responseData['message'] ?? 'Failed to send OTP'], 200);
                }


            }catch (\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=>$e->getMessage(),
                ]);
            }
    
          
        }else{
            return response()->json([
                'status'=>false,
                'message'=>'Mgs91 Not Integrated',
            ]);
        }
    
    }
    // verify OTP while register
    public function registerverifyOtp(Request $request) {

        $validator=Validator::make($request->all(),[
            'phone'=>'required|digits:10|unique:users,contact',
            'otp'=>'required',
            'name'=>'required',
            'email'=>'required|string|email|max:100|unique:users',
            'password'=>'required|min:6',
            'fcm_token'=>'required',
         

        ]);
        if ($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=> $validator->errors()->first(),
            ]);
        }

      

        $checkMsg91=msg91::where('company_id',env('SAAS_KEY'))->first();
        if($checkMsg91){
     
            $authKey = $checkMsg91->authKey;
     
            try{
                $response = Http::get('https://control.msg91.com/api/v5/otp/verify', [
                    'authkey' => $authKey,
                    'mobile' => '91' . $request->phone,
                    'otp' => $request->otp,
                ]);
            
                if (isset($response['type']) && $response['type'] == 'success') {
                    // OTP verified

                  
                    $userresult=User::create([
                        'name'=>$request->name,
                        'email'=>$request->email,
                        'password'=>Hash::make($request->password),
                        'contact'=>$request->phone,
                        'unique_hostal_id'=>env('SAAS_KEY'),
                        'role'=>'User',
                    ]);
        
                    $token = JWTAuth::fromUser($userresult); // Generate JWT token for the new user
        
                    $refreshToken = Str::random(60);
                    $userresult->refresh_token = hash('sha256', $refreshToken);
                    $userresult->device_fcm_token = $request->fcm_token;
                    $userresult->save();
        
                
                    $role = $userresult->role;
                
        
                    return response()->json([
                        'access_token' => $token,
                        'refresh_token' => $refreshToken,
                        'token_type' => 'bearer',
                        'role'=>$role,
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ]);
        


                    
                  
                  

                  
        
                } else {
                    // OTP invalid
        
                    return response()->json([
                        'status'=>false,
                        'message'=> $response['message'],
                    ]);
        
                }

            }catch(\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=> $e->getMessage(),
                ]);
            }
           
        }else{
            return response()->json([
                'status'=>false,
                'message'=> 'Mgs91 Not Integrated',
            ]);
        }
       
      
    }

    // login with mobile and email
    public function login(Request $req){

        $validator=Validator::make($req->all(),[
            'username'=>'required|string',
            'password'=>'required'
        ]);

        if($validator->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ],200);
            
        }
        $loginField = filter_var($req->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'contact';

        $credentials = [
            $loginField => $req->username,
            'password' => $req->password
        ];

        try{

            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Username or Password',
                ], 200);
            }

          

            $user = auth()->user();
            $role = auth()->user()->role;
            $refreshToken = Str::random(60); // Laravel helper for secure random string

            $user->refresh_token = hash('sha256', $refreshToken);
            $user->save();

            return response()->json([
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'role'=>$role,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        
            // return $this->resondedJwtToken($token);
        }catch(\Exception $e){
            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ],200);
        }
    


    }



    // Working web-login api user cant access
    public function web_login(Request $req){

        $validator=Validator::make($req->all(),[
            'username'=>'required|string',
            'password'=>'required',
            'fcm_token'=>'required',
        ]);


       


        if($validator->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ],200);
            
        }
        $loginField = filter_var($req->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'contact';

        $credentials = [
            $loginField => $req->username,
            'password' => $req->password
        ];

        try{

            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Username or Password',
                ], 200);
            }

          

            $user = auth()->user();
            $role = auth()->user()->role;

            if($role=="User"){
                return response()->json([
                    'status' => false,
                    'message' => 'User Not Allowed',
                ], 200);
            }
            $refreshToken = Str::random(60); // Laravel helper for secure random string

            $user->refresh_token = hash('sha256', $refreshToken);
            $user->web_fcm_token = $req->fcm_token;
            $user->save();

            return response()->json([
                'status'=>true,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'role'=>$role,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        
            // return $this->resondedJwtToken($token);
        }catch(\Exception $e){
            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ],200);
        }
    


    }

    //Working mobile_login api only user can access
    public function mobile_login(Request $req){

        $validator=Validator::make($req->all(),[
            'username'=>'required|string',
            'password'=>'required',
            'fcm_token'=>'required'
        ]);


       


        if($validator->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validator->errors()->first(),
                ],200);
            
        }
        $loginField = filter_var($req->username, FILTER_VALIDATE_EMAIL) ? 'email' : 'contact';

        $credentials = [
            $loginField => $req->username,
            'password' => $req->password
        ];

        try{

            if (!$token = auth()->attempt($credentials)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Username or Password',
                ], 200);
            }

          

            $user = auth()->user();
            $role = auth()->user()->role;

            if($role!="User"){
                return response()->json([
                    'status' => false,
                    'message' => 'Only User Can Access',
                ], 200);
            }
            $refreshToken = Str::random(60); // Laravel helper for secure random string

            $user->refresh_token = hash('sha256', $refreshToken);
            $user->device_fcm_token = $req->fcm_token;
            $user->save();

            return response()->json([
                'status'=>true,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'bearer',
                'role'=>$role,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        
            // return $this->resondedJwtToken($token);
        }catch(\Exception $e){
            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ],200);
        }
    


    }


     public function logout(){
      
        auth('api')->logout();


        return response()->json([
            'status'=>true,
            'message'=>'User Successfully Loggout'],200);
    }

    public function reset_password_link(Request $request){
              
        $validator=validator::make(request()->all(), [
                    'email'=>'required|email'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status'=>false,
                        'message'=> $validator->errors()->first(),
                    ],200);
                }

                try{

              

                $emailCheck=user::where('email','=', $request->email)
                ->first();

                if(!$emailCheck){
                    return response()->json([
                        'status'=>false,
                        'message'=>'Email id Not Registered'
                    ]);
                }

                if($emailCheck->deleted_at==1){
                    return response()->json([
                        'status'=>false,
                        'message'=>'Email id deleted'
                    ]);
                }

               
                    $otp = rand(100000, 999999); 

                    $emailCheck->update([
                        'email_otp'=> $otp,
                        'expired_time'=>Carbon::now()->addMinutes(5),
                    ]);
                   
    
                    $data=[
                        'user_data'=>$emailCheck,
                        'otp'=>$otp,
                        'exp'=>Carbon::now()->addMinutes(5),
                        'subject' => 'OTP for Verification',
                    ];
                    Mail::to($request->email)->send(new SendOtp($data));

                    return response()->json([
                        'status'=>true,
                        'message'=>'OTP send to your email id'
                    ]);

                  }
                  catch(\Exception $e){
                    return response()->json([
                        'status'=>false,
                        'message'=>$e->getMessage()
                    ]);
                  }

              


    }
    
    public function verify_otp_update_password(Request $request){
        $validator = Validator::make(request()->all(), [
            'email'=> 'required|email',
            'Otp'=>'required',
            'password'=>'required|min:6'
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validator->errors()->first(),
            ]);
        }

        try{

            $usermail=user::where('email',$request->email)->first();

            if(!$usermail){
                return response()->json([
                    'status'=>false,
                    'message'=>'Email id not registered',
                ]);
            }

            if($usermail->deleted_at==1){
                return response()->json([
                    'status'=>false,
                    'message'=>'Email id deleted',
                ]);
            }

            if($usermail->expired_time && now()->gt($usermail->expired_time)){
                // The expiration time is set and has already passed

                return response()->json([
                    'status'=>false,
                    'message'=>'Opt Expired',
                ]);
            }

            // if(!$usermail->email_otp){
            //     return response()->json([
            //         'status'=>false,
            //         'message'=>'Invalid OTP',
            //     ]);
            // }

            if($usermail->email_otp && $usermail->email_otp==$request->Otp){
               
                try{
                    $verifiedstatus=$usermail->update([
                   
                        'email_otp'=>null,
                        'expired_time'=>null,
                        'password'=>Hash::make($request->password),
                    ]);
    
                    if($verifiedstatus){
                        return response()->json([
                            'status'=>true,
                            'message'=>'OTP verified Successfully'
                        ]);
                    }else{
                        return response()->json([
                            'status'=>false,
                            'message'=>'Some thing went wrong please try again'
                        ]);
                    }

                }catch(\Exception $e){
                    return response()->json([
                        'status'=>false,
                        'message'=>$e->getMessage()
                    ]);
                }
               
               
           

            }
            else{
                return response()->json([
                            'status'=>false,
                            'message'=>'Invalid OTP',
                        ]);
            }
        }
        catch(\Exception $e){
            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ]);
        }

      


    }








   









    
}
