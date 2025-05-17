<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use App\Models\service;
use Storage;

class servicesController extends Controller
{
  
    // store
    public function store(Request $request){

        $validator=Validator::make($request->all(),[
            'name'=>'required|string',
            'type'=>'required|in:Therapy,Counselling',
            'amount'=>'required|numeric',
            'description'=>'required|string',
            'key_area_focus'=>'required|string',
            'benefits'=>'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'app_type' => 'required|in:Online,Offline',
        ],[
            'type.in'=>'type should be Therapy Or Counselling',
            'app_type.in'=>'Appointment type should be Online Or Offline'
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validator->errors()->first()
            ]);
        }

        try{

     

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $name = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('uploads/services', $name, 'public'); // stored in storage/app/public/uploads/images
    
            $imageUrl =$path;// URL to access
          
    
            
        }


        $create_service=service::create([
            'type'=>$request->type,
            'name'=>$request->name,
            'amount'=>$request->amount,
            'description'=>$request->description,
            'key_area_focus'=>$request->key_area_focus,
            'benefits'=>$request->benefits,
            'image'=>$imageUrl,
            'app_type'=>$request->app_type,
        ]);

        if($create_service){
            return response()->json([
                'status'=>true,
                'message'=>'New Service Added'
            ]);
        }else{
            return response()->json([
                'status'=>false,
                'message'=>'Something went wrong plase try again'
            ]);
        }
    }catch(\Exception $e){
        return response()->json([
            'status'=>false,
            'message'=> $e->getMessage()
        ]);
    }

    }

    // view therapy and service
    public function viewService(Request $request, $id=null){
           
        // try {
        //     $query = service::query();
    
        //     // Filter by ID if passed
        //     if ($id) {
               
        //         $query->find($id);
        //     }
    
        //     // If "type" is present, validate and apply it
        //     if ($request->has('type')) {
        //         if (in_array($request->type, ['Therapy', 'Counselling'])) {
        //             $query->where('type', $request->type);
        //         } else {
                 
        //             return response()->json([
        //                 'status' => true,
        //                 'data' => [],
        //             ]);
        //         }
        //     }
    
        //     $results = $query->paginate(10);
    
        //     return response()->json([
        //         'status' => true,
        //         'data' => $results,
        //     ]);
    
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'status' => false,
        //         'message' => $e->getMessage(),
        //     ], 200);
        // }
          

        try {
    $query = service::query();

    // 1. If ID is passed, return a single record (no pagination)
    if ($id) {
        $service = $query->find($id);

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'Service not found',
            ], 200);
        }

        return response()->json([
            'status' => true,
            'data' => $service, // single object
        ]);
    }

    // 2. If "type" is present, validate and apply it
    if ($request->has('type')) {
        if (in_array($request->type, ['Therapy', 'Counselling'])) {
            $query->where('type', $request->type);
        } else {
            return response()->json([
                'status' => true,
                'data' => [], // empty array if invalid type
            ]);
        }
    }

    // 3. Return paginated result (as array)
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

 
    //delete service
       public function deleteservice($id = null)
       {
   
           
   
       if (!$id) {
           return response()->json([
               'status'=>false,
               'message' => 'ID is required for delete'], 400);
       }
   
       $service = service::find($id);
   
       if (!$service) {
           return response()->json([
               'status'=>false,
               'message' => 'Service not found'], 200);
       }
   
     
   
   
       $service->delete();
      
   
     
   
     
       return response()->json([
           'status'=>true,
           'message' => 'Service deleted successfully',
          
       ]);
   
   
   
     
       }

       // update
       public function update(Request $request, $id = null)
       {
   
           
   
       if (!$id) {
           return response()->json([
               'status'=>false,
               'message' => 'ID is required for update'], 400);
       }
   
       $service = service::find($id);
   
       if (!$service) {
           return response()->json([
               'status'=>false,
               'message' => 'service not found'], 200);
       }
   
    
     
   
       $validator=Validator::make($request->all(),[
        'name'=>'required|string',
        'type'=>'required|in:Therapy,Counselling',
        'amount'=>'required|numeric',
        'description'=>'required|string',
        'key_area_focus'=>'required|string',
        'benefits'=>'required|string',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'app_type' => 'required|in:Online,Offline',
        ],[
            'type.in'=>'type should be Therapy Or Counselling',
            'app_type.in'=>'Appointment Type should be Online Or Online'
        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validator->errors()->first()
            ]);
        }

        $data = $request->only(['type', 'name', 'amount', 'description', 'key_area_focus', 'benefits','app_type']);

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($service->image && Storage::disk('public')->exists($service->image)) {
                Storage::disk('public')->delete($service->image);
            }
    
            $image = $request->file('image');
            $name = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('uploads/services', $name, 'public');
            $data['image'] = $path;
        }

      
        $service->update($data);

   
       return response()->json([
           'status'=>true,
           'message' => 'Service updated successfully',
           'data' => $service
       ]);
   
       }
   

   
}
