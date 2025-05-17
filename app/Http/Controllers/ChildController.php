<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\child;
use Validator;

class ChildController extends Controller
{
    //

    public function index(Request $request, $id=null){

        // dd(auth()->user()->id);
        try {
            $query = child::query();

            // Filter by ID if passed
            if ($id) {
                $query->where('id', $id);
            }
        
            // Filter by parent_id (auth user)
            $query->where('parent_id', auth()->user()->id);
        
            $results = $query->paginate(10);
        
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

    // add new child

    public function store(Request $request){
            $validator=validator::make($request->all(),[
                'name'=>'required',
                'age'=>'required|numeric',
                'gender'=>'required|in:Male,Female',
               
            ]);
            if($validator->fails()){
                return response()->json([
                    'status'=>false,
                    'message'=>$validator->errors()->first()
                ]);
            }

            try{
                $create_child=child::create([
                    'name'=>$request->name,
                    'age'=>$request->age,
                    'gender'=>$request->gender,
                    'parent_id'=>auth()->user()->id,
                    'is_parent'=>false
                ]);

                if($create_child){
                    return response()->json([
                        'status'=>true,
                        'message'=>'Child Added Successfully',
                    ]);
                }else{
                    return response()->json([
                        'status'=>true,
                        'message'=>'Child Not Added Successfully',
                    ]);
                }

            }catch(\Exception $e){
                return response()->json([
                    'status'=>false,
                    'message'=>$e->getMessage()
                ]);
            }
        
    }

    // updated child
    public function update(Request $request, $id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    // $child = child::find($id);
   
                $child = child::where('id', $id)
                ->where('parent_id', auth()->user()->id)
                ->first();

            if (!$child) {
                return response()->json([
                    'status' => false,
                    'message' => 'Child not found'
                ], 200);
            }

            $validate = Validator::make($request->all(), [
                'name' => 'required',
                'age' => 'required|numeric',
                'gender' => 'required|in:Male,Female',
                
            ]);

            if ($validate->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validate->errors()->first(),
                ], 200);
            }

            $child->update([
                'name' => $request->name,
                'age' => $request->age,
                'gender' => $request->gender,
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Child updated successfully',
                'data' => $child,
            ]);





    }

     //delete child
     public function delete($id = null)
     {   
 
        $child = child::where('id', $id)
        ->where('parent_id', auth()->user()->id)
        ->first();

    if (!$child) {
        return response()->json([
            'status' => false,
            'message' => 'Child not found'
        ], 200);
    }


    $child->delete();

    return response()->json([
        'status' => true,
        'message' => 'Child Deleted successfully',
       
    ]); 
 
     


 
 
 
   
     }


}
