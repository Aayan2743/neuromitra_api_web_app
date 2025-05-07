<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\appiontment;

class AppointmentController extends Controller
{
    //

    public function store(Request $req){
     
       
       $user_id = auth()->user()->id;

       try{
           $validator=Validator::make($req->all(),[
           
               'name'=>'required',
               'phone'=>'required|digits:10',
               'appointment_for'=>'required|in:self,child',
               'patient_id'=>'required',
               'user_id'=>'required',
               'age'=>'required|numeric',
               'appointment_mode'=>'required|in:online,offline',
               'appointment_request_date'=>'required|date|after_or_equal:today',
               'service_name'=>'required',
               'service_id'=>'required',
               'days'=>'required|numeric',
               'amount' => 'required|numeric',
               'address' => 'required|string',
              
           ]);
   
         if ($validator->fails()) {
            return response()->json([
                'status'=>false,
                'message' => $validator->errors()->first()
            ], 200);
            }

           $create_patient=appiontment::create([
               'name'=>$req->name,
               'phone'=>$req->phone,
               'appointment_for'=>$req->appointment_for,
               'age'=>$req->age,
               'appointment_mode'=>$req->appointment_mode,
               'user_id'=>$user_id,
               'patient_id'=>$req->patient_id,
               'appointment_request_date'=>$req->appointment_request_date,
               'service_name'=>$req->service_name,
               'service_id'=>$req->service_id,
               'days'=>$req->days,
               'amount'=>$req->amount,
               'address'=>$req->address,
               ]);

             
           
                   if($create_appointment){
                       
                           
                       
                       return response()->json([
                           'message'=>'Appointment Booked Successfully',
                           'status'=>true
                       ]);
                   }else{
           
                       return response()->json([
                           'message'=>'Appointment Not Booked Successfully',
                           'status'=>false
                       ]);
                   }

                   
               }


       }
       
       
       catch(\Exception $e)
       {
           return response()->json([
               'message'=>'error found'.$e->getMessage(),
               'status'=>false
           ]);
       }
       
       

       

    }
}
