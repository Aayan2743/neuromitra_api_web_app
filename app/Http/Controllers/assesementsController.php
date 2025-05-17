<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\assesementguideModel;
use App\Models\assementguideanswer;
use Validator;

class assesementsController extends Controller
{
   
     // store
    public function store(Request $request){

        $validator=validator::make($request->all(),[
                'assesement_for'=>'required|in:0,1',
                'category_name'=>'required',
                'question'=>'required'

        ],[
            'assesement_for.in'=>'please select 0 for Kids, 1 for Adults'
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validator->errors()->first()
            ],200);
        }

        try{
            $create_question=assesementguideModel::create([
                'section_name'=>$request->category_name,
                'Q1'=>$request->question,
                'for_whome'=>$request->assesement_for,
            ]);
    
            if($create_question){
                return response()->json([
                    'status'=>true,
                    'message'=>'Question Added successfully...!'
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

    // view and find questions filter by type  kids or audlts
    public function viewQuestions(Request $request, $id=null){
           
      try {
    // If ID is passed, return the single object
    if ($id) {
        $result = assesementguideModel::find($id);

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }

    // Build the query for other filters
    $query = assesementguideModel::query();

    if ($request->has('type')) {
        if (in_array($request->type, [0, 1])) {
            $query->where('for_whome', $request->type);
        } else {
            return response()->json([
                'status' => true,
                'data' => [],
            ]);
        }
    }

    $results = $query->paginate(10);

    return response()->json([
        'status' => true,
        'data' => $results,
    ]);

} catch (\Exception $e) {
    return response()->json([
        'status' => false,
        'message' => $e->getMessage(),
    ]);
}

    
    
    }

    // update
    public function update(Request $request, $id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $question = assesementguideModel::find($id);

    if (!$question) {
        return response()->json([
            'status'=>false,
            'message' => 'Question not found'], 200);
    }

 
  

    $validate = Validator::make($request->all(), [
        'assesement_for' => 'required|in:0,1',
        'category_name' => 'required',
        'question' => 'required',
       
    ], [
        'assesement_for.in' => 'please select 0 for Kids, 1 for Adults',
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->first(),
        ], 200);
    }

   
    $question->update([
        'for_whome'=>$request->assesement_for,
        'section_name'=>$request->category_name,
        'Q1'=>$request->question,
    ]);

    return response()->json([
        'status'=>true,
        'message' => 'Question updated successfully',
        'data' => $question
    ]);

    }

    // delete hard delete
    public function deleted($id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $question = assesementguideModel::find($id);

    if (!$question) {
        return response()->json([
            'status'=>false,
            'message' => 'Question not found'], 200);
    }

  


    $question->delete();
   

  

  
    return response()->json([
        'status'=>true,
        'message' => 'Question deleted successfully',
       
    ]);



  
    }


    // submission
    public function assesementsubmission(Request $request,$id=null){

        try {
            $query = assementguideanswer::with(['user:id,name']);
    
            // Filter by ID if passed
            if ($id) {
               
                $query->where('id', $id);
            }
    
            // If "type" is present, validate and apply it
            if ($request->has('type')) {
                if (in_array($request->type, [0, 1])) {
                    $query->where('for_whome', $request->type);
                } else {
                 
                    return response()->json([
                        'status' => true,
                        'data' => [],
                    ]);
                }
            }
    

            if ($request->has('status')) {
                if (in_array($request->status, [0, 1])) {
                    $query->where('status', $request->status);
                } else {
                 
                    return response()->json([
                        'status' => true,
                        'data' => [],
                    ]);
                }
            }
    


            // $results = $query->get();
           
            $results = $query->get()->map(function ($item) {
                // Decode 'child_info' JSON data
                $decoded = json_decode($item->data);
    
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Extract child info and map to final format
                    $totalScore =  $item->score;

                    if ($totalScore >= 41) {
                        $message = "✅ Development appears typical for age. No significant concerns.";
                     
                    } elseif ($totalScore >= 31) {
                        $message = "⚠️ Some areas of concern. Consider monitoring and engaging in activities to boost skills.";
                     
                    } elseif ($totalScore >= 21) {
                        $message = "❗ Potential developmental delays. A professional evaluation is recommended.";
                      
                    } else {
                        $message = "🚨 High likelihood of developmental concerns. Immediate professional consultation is advised.";
                      
                    }
        

                    $scoreMessages = [
                        [
                            'score' => '41-50',
                            'message' => '✅ Development appears typical for age. No significant concerns.',
                           
                        ],
                        [
                            'score' => '31-40',
                            'message' => '⚠️ Some areas of concern. Consider monitoring and engaging in activities to boost skills.',
                           
                        ],
                        [
                            'score' => '21-30',
                            'message' => '❗ Potential developmental delays. A professional evaluation is recommended.',
                          
                        ],
                        [
                            'score' => '0-20',
                            'message' => '🚨 High likelihood of developmental concerns. Immediate professional consultation is advised.',
                            
                        ],
                    ];


                    return [
                        'id' => $item->id, 
                        'child_name' => $decoded->child_info->name ?? null,  // Get the child name from decoded info
                        'age' => $decoded->child_info->age ?? null,  // Get the age
                        'gender' => $decoded->child_info->gender ?? null,  // Get the age
                        'whome' => $item->for_whome ?? null,  // Get the age
                        'parent_name' => $item->user->name ?? null ,
                        'submission' => $item->submission_date,  // Get the parent name (from the user model)
                        'status' => $item->status,  // Get the parent name (from the user model)
                        'all' => $item->data,  // Get the parent name (from the user model)
                        'score' => $item->score,  // Get the parent name (from the user model)
                        'score_message' => $message,  // Get the parent name (from the user model)
                        'score_guide' => $scoreMessages,  // Get the parent name (from the user model)
                    ];
                }
    
                return null;  // If child_info is invalid, return null or handle accordingly
            })->filter(); 

                

        

            
           

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



    // from user side show questions

      public function list(?int $id = null){


        try{
                  $query = assesementguideModel::query();

                    // this will fire for 0, 1, 2, … but not for null
                    if ($id !== null) {
                        $query->where('for_whome', $id);
                    }

                    $results = $query->paginate(5)->groupBy('section_name');

                    return response()->json([
                        'status' => true,
                        'data'   => $results,
                    ]);
      
        }catch(\Exception $e){
                return response()->json([
                        'status' => false,
                        'data'   => $e->getMessage()
                    ]);  
        }
      
       
      
      
      
      
      
      
        // $listofQuestion = assesementguideModel::where('for_whome',0)->orderBy('section_name')
        //           ->orderBy('id')
        //           ->get()
        //           ->groupBy('section_name');

            

        // return response()->json([
        //     'status'=>true,
        //     'data'=>$listofQuestion
        // ]);

      }

      // save answers
    public function submit_assesement(Request $request){

        $validator = Validator::make($request->all(), [
           
            'data' => 'required',
            'role' => 'required|in:0,1',


        ],[
           
            'role.in'=>'Role shoule be 0 for Kids and 1 for Adults ',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                
                'message' => $validator->errors()->first(),
            ], 200);
        }

        $user_id=Auth()->user()->id;

    

        if($request->role==0) //  for kids calucation
        {
            $data = json_decode($request->data, true);
            $scoreMap = [
                "No" => 0,
                "Sometimes" => 1,
                "Yes" => 2
            ];
            $categoryScores = [];
                $totalScore = 0;
    
                // Iterate over answers and calculate score per category
                foreach ($data['answers'] as $category => $questions) {
                    $categoryScores[$category] = 0;
                    foreach ($questions as $question) {
                        $answer = $question['answer'];
                        if (isset($scoreMap[$answer])) {
                            $categoryScores[$category] += $scoreMap[$answer];
                            $totalScore += $scoreMap[$answer]; // Add to total score
                        }
                    }
                }
                // Include total score in the output
                $categoryScores["Total Score"] = $totalScore;
    
                // Convert to JSON
                $score_for_kids = json_encode($categoryScores, JSON_PRETTY_PRINT);

                $saveanswers=assementguideanswer::create([
                    'user_id'=>$user_id,
                  
                    'data' => $request->data,
                    'for_whome' => $request->role,
                    'score' => $score_for_kids
                ]);
        
                if($saveanswers){
                    return response()->json([
                        'status'=>true,
                        'message'=>'added',
                        'result'=>$score_for_kids,
                        'role'=> $request->role,
                    ]);
                }


        }else{
            // for adults

            $data = json_decode($request->data, true);
            $scoreMap = [
                "Strongly Disagree" => 1,
                "Disagree" => 2,
                "Neutral" => 3,
                "Agree" => 4,
                "Strongly Agree" => 5
            ];
            $categoryScores = [];
                $totalScore = 0;
    
                // Iterate over answers and calculate score per category
                foreach ($data['answers'] as $category => $questions) {
                    $categoryScores[$category] = 0;
                    foreach ($questions as $question) {
                        $answer = $question['answer'];
                        if (isset($scoreMap[$answer])) {
                            $categoryScores[$category] += $scoreMap[$answer];
                            $totalScore += $scoreMap[$answer]; // Add to total score
                        }
                    }
                }
                // Include total score in the output
                $categoryScores["Total Score"] = $totalScore;
    
                // Convert to JSON
                $score_for_adults = json_encode($categoryScores, JSON_PRETTY_PRINT);

               // dd($score_for_adults);

                $saveanswers=assementguideanswer::create([
                    'user_id'=>$user_id,
                   
                    'data' => $request->data,
                    'for_whome' => $request->role,
                    'score' => $score_for_adults
                ]);
        
                if($saveanswers){
                    return response()->json([
                        'status'=>true,
                        'message'=>'added',
                        'result'=>$score_for_adults,
                        'role'=> $request->role,
                    ]);
                }



        }
      

          




       
       


    }
    
}
