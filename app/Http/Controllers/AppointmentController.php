<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\appiontment;
use App\Models\child;
use App\Models\service;
use App\Models\assignedAppointment;
use carbon\Carbon;

class AppointmentController extends Controller
{


       
         // store appointments
       public function store(Request $req){
     
       
                    $user = auth()->user();
                    $user_id = $user->id;

                    $rules = [
                        'appointment_for' => 'required|in:self,child',
                    ];

                    if ($req->appointment_for === 'self') {
                        $rules += [
                            'age' => 'required|numeric',
                            'appointment_mode' => 'required|in:online,offline',
                            'appointment_request_date' => 'required|date|after_or_equal:today',
                            // 'service_name' => 'required',
                            'service_id' => 'required',
                            'days' => 'required|numeric',
                            'amount' => 'required|numeric',
                            'address' => 'required|string',
                            'calender_days' => 'required|array|size:5',
                            'calender_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
                        ];

                        $validator = Validator::make($req->all(), $rules);
                        if ($validator->fails()) {
                            return response()->json([
                                'status' => false,
                                'message' => $validator->errors()->first(),
                            ], 200);
                        }

                        $check_patient = Child::where('parent_id', $user_id)
                        ->where('is_parent', true)
                        ->first();

                        $get_service_details=service::find($req->service_id);

                        //   dd($get_service_details);
                    
                        if(!$get_service_details){
                            return response()->json([
                                'status'=>false,
                                'message'=>'invalid Service id'
                            ]);    

                        } 
                        
                        

                        if (!$check_patient) {
                            $check_patient = new Child();
                            $check_patient->name = $user->name;
                            $check_patient->age = $req->age;
                            $check_patient->parent_id = $user_id;
                            $check_patient->is_parent = true;
                            $check_patient->save();
                        }


                        // dd($req->calender_days);
                        $create_patient = Appiontment::create([
                            'name' => $user->name,
                            'phone' => $user->contact,
                            'appointment_for' => $req->appointment_for,
                            'age' => $req->age,
                            'appointment_mode' => $req->appointment_mode,
                            'user_id' => $user_id,
                            'patient_id' => $check_patient->id,
                            'appointment_request_date' => $req->appointment_request_date,
                            'service_name' => $get_service_details->name,
                            'service_id' => $get_service_details->id,
                            'calender_days' =>json_encode($req->calender_days),
                            'days' => $req->days,
                            'amount' => $req->amount,
                            'address' => $req->address,
                        ]);

                        return response()->json([
                            'status' => $create_patient ? true : false,
                            'message' => $create_patient ? 'Appointment Request Created' : 'Appointment Not Created',
                        ]);



                    }
                    else{
                        $rules += [
                            'patient_id' => 'required|numeric',
                            'appointment_mode' => 'required|in:online,offline',
                            'appointment_request_date' => 'required|date|after_or_equal:today',
                            // 'service_name' => 'required',
                            'service_id' => 'required',
                            'days' => 'required|numeric',
                            'amount' => 'required|numeric',
                            'address' => 'required|string',
                            'calender_days' => 'required|array|size:5',
                            'calender_days.*' => 'in:mon,tue,wed,thu,fri,sat,sun',
                        ];

                        $validator = Validator::make($req->all(), $rules);
                        if ($validator->fails()) {
                            return response()->json([
                                'status' => false,
                                'message' => $validator->errors()->first(),
                            ], 200);
                        }

                        $check_patient = Child::find($req->patient_id);
                        if($check_patient && $check_patient->is_parent==false ){
                           
                            // dd( $check_patient);

                            $get_service_details=service::find($req->service_id);

                            //   dd($get_service_details);
                        
                            if(!$get_service_details){
                                return response()->json([
                                    'status'=>false,
                                    'message'=>'invalid Service id'
                                ]);    
    
                            } 
                            


                            $create_patient = Appiontment::create([
                                'name' => $check_patient->name,
                                'phone' => $user->contact,
                                'appointment_for' => $req->appointment_for,
                                'age' => $check_patient->age,
                                'appointment_mode' => $req->appointment_mode,
                                'user_id' => $user_id,
                                'patient_id' => $check_patient->id,
                                'appointment_request_date' => $req->appointment_request_date,
                                'service_name' => $get_service_details->name,
                                'service_id' => $get_service_details->id,
                                'days' => $req->days,
                                'calender_days' => $req->calender_days,
                                'amount' => $req->amount,
                                'address' => $req->address,
                            ]);
    
                            return response()->json([
                                'status' => $create_patient ? true : false,
                                'message' => $create_patient ? 'Appointment Request Created' : 'Appointment Not Created',
                            ]);


                          }else{
                            
                            return response()->json([
                                'status' => false,
                                'message' => 'Invalid Patient id',
                            ], 200);


                          }

                      
    

                    }

           
              
                  



        }

       // disaplay all details
       public function index(Request $request,$id=null){
        try {
            $query = appiontment::query();
    
            // Filter by ID if passed
            if ($id) {
               
                $query->find($id);
            }
    
            // If "type" is present, validate and apply it
            if ($request->has('status')) {
                if (in_array($request->status, ['open', 'assigned','complete','cancelled'])) {
                    $query->where('status', $request->status);
                } else {
                 
                    return response()->json([
                        'status' => true,
                        'data' => [],
                    ]);
                }
            }
    
            $results = $query->get();
    
            return response()->json([
                'status' => true,
                'data' => $results,
            ]);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 200);
        }
          
    
    
       }

       // assigned 
       public function assign_to_staff(Request $request,$id){

        if (!$id) {
            return response()->json([
                'status'=>false,
                'message' => 'ID is required for update'], 400);
        }
    
     
       
                    $appointment = appiontment::where('id', $id)
                    // ->where('user_id', auth()->user()->id)
                    ->where('status','open')
                    ->first();
    
                if (!$appointment) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Appointment not found'
                    ], 200);
                }
    
              $validator=Validator::make($request->all(),[
                'staff_id'=>'required',
                'starting_date'=>'required|date',
                'starting_time' => 'required|date_format:H:i',
                
                // 'ending_time'=>'required|date_format:H:i',
              ]);

              if($validator->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validator->errors()->first()
                ]);
              }
    
               
    
                // $appointment->update([
                //     'status' => 'assigned',
                //     'staff_id'=>$request->staff_id
                   
                // ]);

                $calendarDays = json_decode($appointment->calender_days, true);
                // dd($calendarDays);
                // $calendarDays = $appointment->calender_days; // e.g., ['mon', 'tue', 'wed']
                $calendarDays = array_map('strtolower', $calendarDays); // Ensure lowercase for consistency

                $dayCount = $appointment->days;
                $startDate = Carbon::parse($request->starting_date);
                $selectedDates = [];

                while (count($selectedDates) < $dayCount) {
                    $dayName = strtolower($startDate->format('D')); // 'mon', 'tue', etc.
                    
                    if (in_array($dayName, $calendarDays)) {
                        $selectedDates[] = $startDate->toDateString();
                    }
                    
                    $startDate->addDay();
                }


                $starting_time = Carbon::createFromFormat('H:i', $request->starting_time);
                $ending_time = $starting_time->copy()->addHours(1)->addMinutes(30)->format('H:i');

                assignedAppointment::create([
                    'app_id' => $id,
                    'staff_id' => $request->staff_id,
                    'starting_date' => $selectedDates[0],
                    'ending_date' => end($selectedDates),
                    'days' => $appointment->days,
                    'calender_days' => $appointment->calender_days,
                    'starting_time' => $request->starting_time,
                    'ending_time' => $ending_time,
                ]);




                // $assigned=assignedAppointment::create([
                //     'app_id'=>$id,
                //     'staff_id'=>$request->staff_id,
                //     'starting_date'=>$request->starting_date,
                //     'ending_date'=>$request->starting_date,
                //     'days'=>$appointment->days,
                // ]);



    
                return response()->json([
                    'status' => true,
                    'message' => 'appointment Assigned successfully',
                    'data' => $appointment,
                ]);
       



       }


 


   
}
