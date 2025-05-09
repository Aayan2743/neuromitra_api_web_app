<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\appiontment;
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

       // assigned 
       public function assign_to_staff_workling_fine(Request $request,$id){

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
                $ending_time = $starting_time->copy()->addMinutes(45)->format('H:i');




                $assigned_id=assignedAppointment::create([
                    'app_id' => $id,
                    'staff_id' => $request->staff_id,
                    'starting_date' => $selectedDates[0],
                    'ending_date' => end($selectedDates),
                    'days' => $appointment->days,
                    'calender_days' => $appointment->calender_days,
                    'starting_time' => $request->starting_time,
                    'ending_time' => $ending_time,
                ]);

                
                $appointment->update([
                    'status' => 'assigned',
                    'staff_id'=>$request->staff_id,
                    'assigned_id'=>$assigned_id->id,
                   
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

       // new validaton

       public function assign_to_staff(Request $request)
       {
       
            // 1) Validate incoming payload, including appointment_id as variable
        
            $validator = Validator::make($request->all(), [
                'appointment_id' => 'required|integer|exists:appiontments,id',
                'staff_id'       => 'required|integer|exists:users,id',
                'starting_date'  => 'required|date',
                'starting_time'  => 'required|date_format:H:i',
            ]);
    
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
    
            // 2) Load the appointment (must be open)
            $appointment = appiontment::where('id', $request->appointment_id)
                                      ->where('status', 'open')
                                      ->first();
            if (! $appointment) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Appointment not found or not open',
                ], 404);
            }
    
            // 3) Parse requested start and compute end time (45m slot)
            $newStartTime = Carbon::createFromFormat('H:i', $request->starting_time);
            $newEndTime   = $newStartTime->copy()->addMinutes(45);
    
            // 4) Define the forbidden window (05:25–06:10)
            $blockStart = Carbon::createFromTime(5, 25);
            $blockEnd   = Carbon::createFromTime(6, 10);
    
            // 5) Reject if new block overlaps the forbidden window
            if ($newStartTime->lt($blockEnd) && $newEndTime->gt($blockStart)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Assignments may not overlap the restricted window 05:25–06:10.',
                ], 422);
            }
    
            // 6) Compute the assignment’s dates (single-day here)
            $startDate = $request->starting_date;
            $endDate   = $startDate;
    
            // 7) Overlap check: no existing assignment on the same date may overlap in time
            $conflict = AssignedAppointment::where('app_id',        $appointment->id)
                ->where('staff_id',      $request->staff_id)
                ->where('starting_date', $startDate)
                ->where('ending_date',   $endDate)
                ->whereTime('starting_time', '<', $newEndTime->format('H:i'))
                ->whereTime('ending_time',   '>', $newStartTime->format('H:i'))
                ->exists();
    
            if ($conflict) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This time slot overlaps an existing assignment.',
                ], 422);
            }
    
            // 8) Create the new assignment
            $assigned = AssignedAppointment::create([
                'app_id'         => $appointment->id,
                'staff_id'       => $request->staff_id,
                'starting_date'  => $startDate,
                'ending_date'    => $endDate,
                'starting_time'  => $newStartTime->format('H:i'),
                'ending_time'    => $newEndTime->format('H:i'),
                'days'           => $appointment->days,
                'calender_days'  => $appointment->calender_days,
            ]);
    
            // 9) Update the appointment’s status
            $appointment->update([
                'status'      => 'assigned',
                'staff_id'    => $request->staff_id,
                'assigned_id' => $assigned->id,
            ]);
    
            return response()->json([
                'status'  => true,
                'message' => 'Appointment assigned successfully.',
                'data'    => $assigned,
            ], 201);
        }
       
       
       
  




    
      

 


       // free days
       public function free_days($staffId, Request $request)
       {
       
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $from = Carbon::parse($request->query('from'));
        $to   = Carbon::parse($request->query('to'));

        // 2) Fetch any schedule rows overlapping [from…to]
        $schedules = assignedAppointment::where('staff_id', $staffId)
            ->whereDate('ending_date',   '>=', $from)
            ->whereDate('starting_date', '<=', $to)
            ->get();

        $results = [];

        // Business-day bounds as Carbon times
        $businessStart = Carbon::createFromTime(6, 0);   // 6:00 AM
        $businessEnd   = Carbon::createFromTime(20, 0);  // 8:00 PM

        foreach ($schedules as $sched) {
            // 3) Compute the overlap window for this schedule row
            $startDate = $from->greaterThan($sched->starting_date)
                         ? $from
                         : $sched->starting_date;

            $endDate   = $to->lessThan($sched->ending_date)
                         ? $to
                         : $sched->ending_date;

            // 4) Iterate each day in that overlap
            foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
                // Only consider days listed in calendar_days
                if (! in_array(strtolower($day->format('D')), $sched->calender_days)) {
                    continue;
                }

                // 5) Parse the booked (scheduled) times
                $bookedStart = Carbon::createFromFormat('H:i', $sched->starting_time);
                $bookedEnd   = Carbon::createFromFormat('H:i', $sched->ending_time);

                $freeSlots = [];

                // 6) Before booked slot (from businessStart → bookedStart)
                if ($bookedStart->greaterThan($businessStart)) {
                    $freeSlots[] = [
                        'start' => $businessStart->format('g:i A'),
                        'end'   => $bookedStart->format('g:i A'),
                    ];
                }

                // 7) After booked slot (from bookedEnd → businessEnd)
                if ($bookedEnd->lessThan($businessEnd)) {
                    $freeSlots[] = [
                        'start' => $bookedEnd->format('g:i A'),
                        'end'   => $businessEnd->format('g:i A'),
                    ];
                }

                $results[] = [
                    'date'       => $day->toDateString(),
                    'day'        => $day->format('l'),        // e.g. "Monday"
                    'free_slots' => $freeSlots,
                ];
            }
        }

        return response()->json([
            'staff_id' => (int)$staffId,
            'results'  => $results,
        ]);


        
        }

        // yoonga days

        public function staff_engagged($staffId, Request $request)
        {
            
            $request->validate([
                'from' => 'required|date',
                'to'   => 'required|date|after_or_equal:from',
            ]);
    
            $from = Carbon::parse($request->query('from'));
            $to   = Carbon::parse($request->query('to'));
    
            // 1) Fetch schedule rows overlapping [from…to]
            $schedules = assignedAppointment::with('appointment')
                ->where('staff_id', $staffId)
                ->whereDate('ending_date',   '>=', $from)
                ->whereDate('starting_date', '<=', $to)
                ->get();
    
            $results = [];
    
            foreach ($schedules as $sched) {
                // 2) Determine the overlap window
                $startDate = $from->greaterThan($sched->starting_date)
                             ? $from
                             : $sched->starting_date;
                $endDate   = $to->lessThan($sched->ending_date)
                             ? $to
                             : $sched->ending_date;
    
                // 3) Iterate each date in that window
                foreach (CarbonPeriod::create($startDate, $endDate) as $day) {
                    if (! in_array(strtolower($day->format('D')), $sched->calender_days)) {
                        continue;
                    }
    
                    $appt = $sched->appointment; // may be null
                    $clientName = $appt ? $appt->name : null;
    
                    $results[] = [
                        'date'        => $day->toDateString(),
                        'day'         => $day->format('l'),
                        'start_time'  => Carbon::createFromFormat('H:i', $sched->starting_time)
                                                 ->format('g:i A'),
                        'end_time'    => Carbon::createFromFormat('H:i', $sched->ending_time)
                                                 ->format('g:i A'),
                        'client_name' => $clientName,
                    ];
                }
            }
    
            return response()->json([
                'staff_id' => (int)$staffId,
                'results'  => $results,
            ]);

          
        }

       }


 


   

