<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\appiontment;
use App\Models\testcase;
use App\Models\child;
use App\Models\service;
use App\Models\assignedAppointment;
use Illuminate\Validation\ValidationException;
use carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Carbon\CarbonPeriod;

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
                            'gender' => 'required|in:Male,Female',
                            'appointment_mode' => 'required|in:online,offline',
                            'appointment_request_date' => 'required|date|after_or_equal:today',
                            // 'service_name' => 'required',
                            'service_id' => 'required',
                            'days' => 'required|numeric',
                            'amount' => 'required|numeric',
                            'address' => 'required|string',
                            'calender_days' => 'required|array|size:5',
                            'calender_days.*' => 'in:Mon,Tue,Wed,Thu,Fri,Sat,Sun',
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
                            $check_patient->gender = $req->gender;
                            $check_patient->save();
                        }


                        // dd($req->calender_days);
                        $create_patient = Appiontment::create([
                            'name' => $user->name,
                            'phone' => $user->contact,
                            'appointment_for' => $req->appointment_for,
                            'age' => $req->age,
                            'gender' => $req->gender,
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

  




        // new approach to assign working best

        public function create_appointment(Request $request)
        {
            
        $validator = Validator::make($request->all(), [
            'app_id' => 'required',
            'starting_date' => 'required|date',
            'starting_time' => 'required',
            'ending_time' => 'required|after:starting_time',
            'staff_id' => 'required|exists:users,id',
        ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

                $get_calender_days = appiontment::find($request->app_id);


                if ($get_calender_days == null) {
                    return response()->json([
                        'status' => false,
                        'message' => "No data available"
                    ]);
                }

                if($get_calender_days->status!='open'){
                         return response()->json([
                        'status' => false,
                        'message' => "Appointment not open Status"
                    ]);
                }

$calendarDays = json_decode($get_calender_days->calender_days, true);
$calendarDays = array_map('strtolower', $calendarDays);

$calendarDaysJson = json_encode($calendarDays);



$totalDays = $get_calender_days->days;
$startDate = Carbon::parse($request->starting_date);
$startTime = $request->starting_time;
$endTime = $request->ending_time;
$staffId = $request->staff_id;

$validDates = [];
$currentDate = $startDate->copy();

$loopLimit = 365; // 1 year safety cap
$iteration = 0;
// dd($calendarDays);
while (count($validDates) < $totalDays) {
    if (in_array(strtolower($currentDate->format('D')), $calendarDays)) {
        $validDates[] = [
            'date' => $currentDate->toDateString(),
            'day' => $currentDate->format('D')
        ];
    }
    $currentDate->addDay();

}




 foreach ($validDates as $entry) {
        $overlap = assignedAppointment::where('staff_id', $staffId)
            ->where('starting_date', $entry['date'])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->whereBetween('starting_time', [$startTime, $endTime])
                  ->orWhereBetween('ending_time', [$startTime, $endTime])
                  ->orWhere(function ($q2) use ($startTime, $endTime) {
                      $q2->where('starting_time', '<=', $startTime)
                         ->where('ending_time', '>=', $endTime);
                  });
            })->exists();

        if ($overlap) {
            return response()->json([
                'status' => false,
                'message' => "Overlap found for {$entry['date']} ({$entry['day']})"
            ], 409);
        }

       $assignemnt_id= assignedAppointment::create([
            'app_id' => $request->app_id,
            'staff_id' => $staffId,
            'starting_date' => $entry['date'],
            'calender_days' => $entry['day'],
            'starting_time' => $startTime,
            'ending_time' => $endTime,
        ]);


        $get_calender_days->update([
            'status'=>'assigned',
            'staff_id'=> $staffId,
            'assigned_id'=> $assignemnt_id->id,
        ]);
    }

    return response()->json([
        'status' => true,
        'message' => 'Appointments successfully assigned.',
        'data' => $validDates
    ]);




 



    // Check for overlapping appointments
  
        }

        // available days new table

            public function check_availability(Request $request): JsonResponse
    {


            
$validator = Validator::make($request->all(), [
    'staff_id'   => 'required|exists:users,id',
    'start_date' => 'required|date|date_format:Y-m-d',
     'end_date'   => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
   
]);

if ($validator->fails()) {
    return response()->json([
        'status' => false,
        'message' => $validator->errors()->first()
    ]);
}


        $staffId   = $request->staff_id;
        $startDate = Carbon::createFromFormat('Y-m-d', $request->start_date)->startOfDay();
        $endDate   = Carbon::createFromFormat('Y-m-d', $request->end_date)->startOfDay();
        
        // 1) fetch all open appointments in the window
        $booked = assignedAppointment::where('staff_id', $staffId)
         
            ->whereBetween('starting_date', [
                $startDate->toDateString(),
                $endDate->toDateString(),
            ])
            ->get(['starting_date','starting_time','ending_time'])
            ->map(fn($a) => (object)[
              'start' => Carbon::parse("{$a->starting_date->toDateString()} {$a->starting_time}"),
                     'end'   => Carbon::parse("{$a->starting_date->toDateString()} {$a->ending_time}"),
            ]);

        // 2) define your working window & slot length

        // dd(env('DAYSTART'));
        $dayStart = env('DAYSTART');
        $dayEnd   = env('DAYEND');
        $slotLen  = env('DURATION');
           
        // 3) generate all candidate slots & filter out booked
        $rawSlots = collect();
        for ($day = $startDate->copy(); $day->lte($endDate); $day->addDay()) {
            $cursor = $day->copy()->setTimeFromTimeString($dayStart);
            $limit  = $day->copy()->setTimeFromTimeString($dayEnd)->subMinutes($slotLen);

            while ($cursor->lte($limit)) {
                $slotStart = $cursor->copy();
                $slotEnd   = $cursor->copy()->addMinutes($slotLen);

                $overlap = $booked->first(fn($b) =>
                    $slotStart->lt($b->end) && $slotEnd->gt($b->start)
                );

                if (! $overlap) {
                    $rawSlots->push([
                        'date'  => $day->toDateString(),
                        'start' => $slotStart->format('h:i A'),
                        'end'   => $slotEnd->format('h:i A'),
                    ]);
                }

                $cursor->addMinutes($slotLen);
            }
        }

        // 4) group by date & include the weekday name
        $grouped = $rawSlots
            ->groupBy('date')
            ->map(fn($daySlots, $date) => [
                'day'   => Carbon::parse($date)->format('l'),
                'slots' => $daySlots->values(),
            ])
            ->mapWithKeys(fn($data, $date) => [ $date => $data ]);

        return response()->json([
            'status'=>true,
            'data'=>$grouped,

        ]);
        // return response()->json($grouped);
            }




       }


 


   

