<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\userAddress;

class UserAddressController extends Controller
{
  

    // store address
    public function createAddress(Request $request){

            $user_id=auth()->user()->id;

try {
    $validator = Validator::make($request->all(), [
        'flat_no' => 'required|string',
        'street' => 'required|string',
        'area' => 'required|string',
        'landmark' => 'required|string',
        'pincode' => 'required|numeric',
        'type_of_address' => 'required|in:0,1,2',
        'location_access' => 'required',
       
    ]);

    if ($validator->fails()) {
        $firstError = $validator->errors()->first();
        return response()->json([
            'status'=>false,
            'message' => $firstError
        ], 200);
    }

    // Check if the user has already added the maximum number of addresses
    $existing_prement_address = userAddress::where('uid', $user_id)->count();
    $existing_present = userAddress::where('uid', $user_id)->where('type_of_address', 0)->count();
    $existing_perement = userAddress::where('uid', $user_id)->where('type_of_address', 1)->count();

    // Conditions for limiting addresses
    if ($existing_prement_address >= 2) {
        return response()->json([
             'status' => false,
            'message' => 'User can only add two addresses.',
           
        ], 200); // 503 Service Unavailable (Can change to 409 Conflict)
    } elseif ($existing_present > 0 && $request->type_of_address == 0) {
        return response()->json([
             'status' => false,
            'message' => 'Present address already added.',
           
        ], 200); 
    } elseif ($existing_perement > 0 && $request->type_of_address == 1) {
        return response()->json([
             'status' => false,
            'message' => 'Permanent address already added.',
           
        ], 200);
    }

    // Create the new address if validations pass
    $create_address_present = userAddress::create([
        'uid' => $user_id,
        'Flat_no' => $request->flat_no,
        'street' => $request->street,
        'area' => $request->area,
        'landmark' => $request->landmark,
        'pincode' => $request->pincode,
        'type_of_address' => $request->type_of_address,
        'location_access' => $request->location_access,
      
    ]);

    // Success response
    return response()->json([
        'message' => 'Address added successfully',
        'status' => true,
        'address_id' => $create_address_present->id, // Returning the ID of the created address
    ], 200);

} catch (\Exception $e) {
    // Catch any exception and return an error response
    return response()->json([
       
        'status' => false,
         'message' => $e->getMessage(),
    ], 200);
}
    
    
    
    
    }

    // fetch addresss
    public function viewAddress(Request $request, $id=null){
            // dd(auth()->user()->id);
        try {
            $query = userAddress::query();
    
            // Filter by ID if passed
            if ($id) {
               
                $result = userAddress::find($id); // Use find() directly

                    return response()->json([
                        'status' => true,
                        'data' => $result,
                    ]);
            }
    
            // If "type" is present, validate and apply it
            if ($request->has('type_of_address')) {
                if (in_array($request->type_of_address, ['0', '1','2'])) {
                    $query->where('type_of_address', $request->type_of_address);
                } else {
                 
                    return response()->json([
                        'status' => true,
                        'data' => [],
                    ]);
                }
            }
    
            $results = $query->where('uid',auth()->user()->id)->paginate(5);
    
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

    // update address
     public function updateAddress(Request $request, $id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $address = userAddress::find($id);

    if (!$address) {
        return response()->json([
            'status'=>false,
            'message' => 'Address not found'], 200);
    }

  
     $validator = Validator::make($request->all(), [
        'flat_no' => 'required|string',
        'street' => 'required|string',
        'area' => 'required|string',
        'landmark' => 'required|string',
        'pincode' => 'required|numeric',
        'type_of_address' => 'required|in:0,1,2',
        'location_access' => 'required',
       
    ]);

    if ($validator->fails()) {
        $firstError = $validator->errors()->first();
        return response()->json([
            'status'=>false,
            'message' => $firstError
        ], 200);
    }

  

   
    $address->update($request->all());

    return response()->json([
        'status'=>true,
        'message' => 'Address updated successfully',
        'data' => $address
    ]);

    }
    //delete address

        public function deleted($id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $address = userAddress::find($id);

    if (!$address) {
        return response()->json([
            'status'=>false,
            'message' => 'Address not found'], 200);
    }

  


    $address->delete();
   

  

  
    return response()->json([
        'status'=>true,
        'message' => 'Address deleted successfully',
       
    ]);



  
    }
    
}
