<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\appiontment;
use App\Models\testcase;
use App\Models\child;
use App\Models\service;
use App\Models\assignedAppointment;
use App\Models\userAddress;
use App\Models\session_tracking_details;
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
                            'address' => 'required|exists:user_address,id',
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
            'meet_url' => 'required|url',
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
            'meet_url'=>$request->meet_url
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

            // enggaged appointment details using date and  search by name

               public function staffAppointments(Request $request): JsonResponse
                 {
                     
                      $v = Validator::make($request->all(), [
        'staff_id' => 'required|exists:users,id',
        'search'   => 'nullable|string',
    ]);

    if ($v->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $v->errors()->first(),
        ], 200);
    }

    $staffId = $request->input('staff_id');
    $search  = $request->input('search');
    $now     = Carbon::now();

    // 2) build base query
    $q = AssignedAppointment::with('appointment')
        ->where('staff_id', $staffId);

    // 3) if a search term was provided, filter by customer name or date
    if ($search) {
        $q->whereHas('appointment', function($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%");
            })
          ->orWhere('starting_date', 'like', "%{$search}%");
    }

    // 4) finalize ordering & execute
    $appointments = $q->orderBy('starting_date')
        ->orderBy('starting_time')
        ->get()
        ->map(function($a) use ($now) {
            $start = Carbon::parse("{$a->starting_date} ");
            $end   = Carbon::parse("{$a->starting_date} ");

            if ($now->gt($end)) {
                $status = 'complete';
            } elseif ($now->between($start, $end)) {
                $status = 'inprocess';
            } else {
                $status = 'upcoming';
            }

            return [
                'appointment_id' => $a->app_id,
                'customer'       => $a->appointment->name,
                'date'           => $a->starting_date,
                'start_time'     => $a->starting_time,
                'end_time'       => $a->ending_time,
                'status'         => $status,
            ];
        });

    return response()->json([
        'status' => true,
        'data'   => $appointments,
    ]);

       
                }

                // user userAppointments

                  public function  userAppointments(Request $request): JsonResponse
                    {
                        // 1) validate
                         $v = Validator::make($request->all(), [
        'search' => 'nullable|string',
    ]);
    if ($v->fails()) {
        return response()->json([
            'status'  => false,
            'message' => $v->errors()->first(),
        ], 422);
    }
    $search = $request->input('search');

    // 2) Determine which date to show: search‐date (if valid) or today
    $today      = Carbon::today()->toDateString();
    $dateFilter = preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)
        ? $search
        : $today;

    $now = Carbon::now();

    // 3) Base query: only appointments of this user that have a slot on $dateFilter
    $q = Appiontment::with([
            'assignedSlots' => function($slotQ) use ($dateFilter) {
                $slotQ->whereDate('starting_date', $dateFilter)
                      ->with('staff');
            }
        ])
        ->where('user_id', auth()->id())
        ->whereHas('assignedSlots', function($slotQ) use ($dateFilter) {
            $slotQ->whereDate('starting_date', $dateFilter);
        });

    // 4) If `search` is *not* a date, apply mode+staff-name filters
    // if ($search && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
    //     $q->where(function($q2) use ($search) {
    //         $q2->where('appointment_mode', 'like', "%{$search}%")
    //            ->orWhereHas('assignedSlots.staff', function($q3) use ($search) {
    //                $q3->where('name', 'like', "%{$search}%");
    //            });
    //     });
    // }

    if ($search && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $search)) {
    $q->where(function($q2) use ($search) {
        $q2
            // 1) Match the appointment’s PK directly
            ->where('id', $search)

            // 2) Or match by mode
            ->orWhere('appointment_mode', 'like', "%{$search}%")

            // 3) Or match by staff name on the slot
            ->orWhereHas('assignedSlots.staff', function($q3) use ($search) {
                $q3->where('name', 'like', "%{$search}%");
            })

            // 4) Or match by the slot’s app_id column
            ->orWhereHas('assignedSlots', function($q4) use ($search) {
                $q4->where('app_id', $search);
            });
    });
}

    // 5) Fetch & map
    $appointments = $q->get()
        ->flatMap(function($appt) use ($now) {
            return $appt->assignedSlots->map(function($slot) use ($appt, $now) {
                $start  = Carbon::parse("{$slot->starting_date} ");
                $end    = Carbon::parse("{$slot->starting_date} ");
                $status = $now->gt($end)
                    ? 'complete'
                    : ($now->between($start, $end) ? 'inprocess' : 'upcoming');

                return [
                    
                    'appointment_id'   => $appt->id,
                    'appointment_mode' => $appt->appointment_mode,
                    'date'             => Carbon::parse($slot->starting_date)->format('Y-m-d'),
                    'start_time'       => Carbon::parse($slot->starting_time)->format('H:i'),
                    'end_time'         => Carbon::parse($slot->ending_time)->format('H:i'),
                    'status'           => $slot->status === null
                                           ? 'Still Not Started'
                                           : $slot->status,
                    'staff' => [
                        'id'    => $slot->staff->id,
                        'name'  => $slot->staff->name,
                        'email' => $slot->staff->email,
                    ],
                    'url'=>$slot->meet_url ?? "N/A"
                ];
            });
        })
        ->values();

    // 6) Return
    return response()->json([
        'status' => true,
        'data'   => $appointments,
    ]);


                

                    }

                // staff upcoming appointments
                
                 public function staffUpcomingAppointments(Request $request): JsonResponse
    {
      
             $staff = auth()->user();
    $today = Carbon::today()->toDateString();
    $now   = Carbon::now();

    // 2) Validate the optional search parameter
    $request->validate([
        'search' => 'nullable|string',
    ]);
    $search = $request->input('search');

    // 3) Build the base query: only this staff's future slots
    $q = AssignedAppointment::with(['appointment.user'])
        ->where('staff_id', $staff->id)
        ->whereDate('starting_date', '>=', $today);

    // 4) Apply search filters if a search term was provided
    if ($search) {
        $q->where(function($q2) use ($search) {
            // a) If it's purely numeric, match exactly on app_id
            if (ctype_digit($search)) {
                $q2->where('app_id', $search);
            }

            // b) Match partial dates
            $q2->orWhere('starting_date', 'like', "%{$search}%")
               // c) Match customer name
               ->orWhereHas('appointment.user', function($q3) use ($search) {
                   $q3->where('name', 'like', "%{$search}%");
               })
               // d) Match appointment mode
               ->orWhereHas('appointment', function($q4) use ($search) {
                   $q4->where('appointment_mode', 'like', "%{$search}%");
               });
        });
    }

    // 5) Fetch, sort and map to the response shape
    $slots = $q->orderBy('starting_date')
        ->orderBy('starting_time')
        ->get()
        ->map(function($slot) use ($now) {
            // Determine status based on dates/times
            $start = Carbon::parse("{$slot->starting_date}");
            $end   = Carbon::parse("{$slot->starting_date} ");

            if ($now->gt($end)) {
                $status = 'complete';
            } elseif ($now->between($start, $end)) {
                $status = 'inprocess';
            } else {
                $status = 'upcoming';
            }

            return [
                'slot_id'=>$slot->id,
                'appointment_id' => $slot->app_id,
                'mode'           => $slot->appointment->appointment_mode,
                'customer_id'    => $slot->appointment->user->id,
                'customer_name'  => $slot->appointment->user->name,
                'date'           => $start->format('Y-m-d'),
                'start_time'     => $slot->starting_time,
                'end_time'       => $slot->ending_time,
                'status'         => $status,
                'url'            => $slot->meet_url ?? 'N/A',
            ];
        });

    // 6) Return the JSON response
    return response()->json([
        'status' => true,
        'data'   => $slots->values(),
    ]);



                }


                // search by app_id and get all slote from user side only

                 public function slotsByAppointment(Request $request, $app_id): JsonResponse
    {

         $v = Validator::make(['app_id' => $app_id], [
            'app_id' => 'required|integer|exists:appiontments,id',
        ]);
        if ($v->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid appointment ID',
            ], 422);
        }

        $now = Carbon::now();

        // 2) Fetch all slots, eager-loading both staff and the appointment's user
        $slots = AssignedAppointment::with([
                'staff',
                'appointment.user'  // make sure Appiontment model has user() relationship
            ])
            ->where('app_id', $app_id)
            ->orderBy('starting_date')
            ->orderBy('starting_time')
            ->get()
            ->map(function($slot) use ($now) {
                // compute status
                $start = Carbon::parse("{$slot->starting_date} ");
                $end   = Carbon::parse("{$slot->starting_date} ");
                if ($now->gt($end)) {
                    $computed = 'complete';
                } elseif ($now->between($start, $end)) {
                    $computed = 'inprocess';
                } else {
                    $computed = 'upcoming';
                }

                // pull the appointment’s user
                $user = $slot->appointment;
 $appt = $slot->appointment;
                
                return [
                    'slot_id'         => $slot->id,
                    'date'            => Carbon::parse($slot->starting_date)->format('Y-m-d'),
                    'start_time'      => Carbon::parse($slot->starting_time)->format('H:i'),
                    'end_time'        => Carbon::parse($slot->ending_time)->format('H:i'),
                    'raw_status'      => $slot->status,
                    'computed_status' => $computed,
                    'url' => $slot->meet_url ?? "N/A",

                    'staff' => [
                        'id'    => $slot->staff->id,
                        'name'  => $slot->staff->name,
                        'email' => $slot->staff->email,
                    ],

                    'user' => [
                        'id'     => $user->id,
                        'name'   => $user->name,
                        'age'    => $user->age,
                        'gender' => $user->gender,
                    ],
                      'meet_url'        => $appt->meet_url,
                ];
            });

        return response()->json([
            'status' => true,
            'data'   => $slots,
        ]);
      
                 }
                 
                 // tracker
                public function tracker(Request $request){
                  


                             $validator = Validator::make($request->all(), [
                               'slot_id' => 'required|integer|exists:assigned,id',
                               'text' => 'required|string',
                            ]);

                                if ($validator->fails()) {
                                    return response()->json([
                                        'status' => false,
                                        'message' => $validator->errors()->first()
                                    ]);
                                }


                     

                            // 2) Fetch the slot
                            $slot = AssignedAppointment::find($request->input('slot_id'));

                            // 3) Return the response
                            if ($slot) {    

                                if($slot->session_trackling_status==true){
                                    return response()->json([
                                    'status'  => false,
                                    'message' => 'Already Tracking created on this session',
                                    
                                ]);
                                }
                                //   dd($slot);  
                                $add_tracking=session_tracking_details::create([
                                    'staff_id'=> $slot->staff_id,
                                    'assigned_id'=> $slot->id,
                                    'app_id'=> $slot->app_id,
                                    'text'=> $request->text,
                                ]);


                              $slot->update([
                                'session_trackling_status' => 1,  // ← correct spelling
                            ]);

                                return response()->json([
                                    'status'  => true,
                                    'message' => 'Tracking Updated',
                                   
                                ]);
                            } else {
                            
                                return response()->json([
                                    'status'  => false,
                                    'message' => 'Invalid slot ID',
                                ], 200);
                            }


                       




                }

       }


 


   

