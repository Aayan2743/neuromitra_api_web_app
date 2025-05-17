<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\health_daily_reports;
use Validator;
use Storage;
use Hash;
use Carbon\Carbon;

class ProfileController extends Controller
{
    //

    public function viewProfiles(){
        $id=auth()->user()->id;

        
        $get_profile_details=User::find($id);

           // Get today's date
         $today = Carbon::today();

    // Fetch today's health feedback
         $healthFeedback = health_daily_reports::where('uid', $id)
                        ->whereDate('dateoffeedback', $today)
                        ->exists();


        if($get_profile_details){
            return response()->json([
                'status'=>true,
                'message'=>$get_profile_details,
                'health_feedback' =>  ['status' => $healthFeedback]
            ]);
        }

        

    }


    //update profile
        
        public function update(Request $request)
        {
    
            // dd($request->all());
            $id=auth()->user()->id;
            $user=auth()->user();
    
        $validator=Validator::make($request->all(),[
         'name'=>'required|string',
         'email' => 'required|email|unique:users,email,' . $id, 
         'contact'=>'required|digits:10|unique:users,contact,'.$id,
         'profile_pic' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
         'password' => 'sometimes|nullable|min:6',
         ]);
 
         if($validator->fails()){
             return response()->json([
                 'status'=>false,
                 'message'=>$validator->errors()->first()
             ]);
         }
 
        //  $data = $request->only(['name', 'email', 'contact', 'profile_pic', 'password']);
 
        
 
       
         $user->name = $request->name;
         $user->email = $request->email;
         $user->contact = $request->contact;

         if ($request->hasFile('profile_pic')) {
            // Delete old image if exists
            if ($user->profile_pic && Storage::disk('public')->exists($user->profile_pic)) {
                Storage::disk('public')->delete($user->profile_pic);
            }
    
            $image = $request->file('profile_pic');
            $name = time() . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('uploads/profiles', $name, 'public');
            $user['profile_pic'] = $path;
        }

    
        // Update password if provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
    
        $user->save();
    
 
    
        return response()->json([
            'status'=>true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    
        }
    

}
