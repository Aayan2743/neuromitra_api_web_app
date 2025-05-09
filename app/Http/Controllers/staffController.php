<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Hash;
use app\Models\User;
use Illuminate\Validation\Rule;

class staffController extends Controller
{
    //

    public function store(Request $request){

        $validate=Validator::make($request->all(),[
            'name'=>'required',
            'email' => 'required|email|unique:users,email',
             'contact' => 'required|digits:10|unique:users,contact',
            'password'=>'required|min:6',
            'role' => 'required|in:Therapist,Counceller',
            'gender' => 'required|in:Male,Female',
        ],[

            'role.in'=>'Role Should be Therapist or Counceller'
        ]);

        if($validate->fails()){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $validate->errors()->first(),
                ],
               200);
        }


        try{

            $create_staff=User::create([
                'name'=>$request->name,
                'email'=>$request->email,
                'contact'=>$request->contact,
                'password'=>Hash::make($request->password),
                'role'=>$request->role,
                'gender'=>$request->gender,
                'unique_hostal_id'=>env('SAAS_KEY'),
            ]);

            if($create_staff){
                return response()->json(
                    [
                        'status'=>true,
                        'message'=> $request->role.'  Created',
                    ],
                   200);
            }else{
                return response()->json(
                    [
                        'status'=>false,
                        'message'=> 'Some thing went wrong please try again',
                    ],
                   200);

            }


        }catch(\Exception $e){
            return response()->json(
                [
                    'status'=>false,
                    'message'=> $e->getMessage(),
                ],
               200);
        }
    

    }

    public function viewStaff(Request $request, $id = null)
{
    try {
        if ($id) {
            $staff = User::find($id);

            if (!$staff) {
                return response()->json([
                    'status' => false,
                    'message' => 'Staff not found'
                ], 200);
            }

            if ($staff->role === 'Admin' || $staff->role === 'User') {
                return response()->json([
                    'status' => false,
                    'message' => 'Staff not found'
                ], 200);
            }

            if ($staff->deleted_at !== null) {
                return response()->json([
                    'status' => false,
                    'message' => 'Staff Deleted'
                ], 200);
            }

            return response()->json([
                'status' => true,
                'data' => $staff
            ], 200);
        }

        $query = User::whereIn('role', ['Therapist', 'Counceller'])
                              ->where('deleted_at', 0) ;

                  

        if ($request->filled('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $getStaff = $query->get();

        return response()->json([
            'status' => true,
            'data' => $getStaff
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 200);
    }
}



    public function update(Request $request, $id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $staff = User::find($id);

    if (!$staff) {
        return response()->json([
            'status'=>false,
            'message' => 'Staff not found'], 200);
    }

    if($staff->deleted_at!=null){
        return response()->json([
            'status'=>false,
            'message' => 'Staff Deleted'], 200);
    }

    if (in_array($staff->role, ['Admin', 'User'])) {
        return response()->json([
            'status' => false,
            'message' => 'Role does not belong to staff'
        ], 200);
    }

    $validate = Validator::make($request->all(), [
        'name' => 'required',
        'email' => [
            'required',
            'email',
            Rule::unique('users')->ignore($id),
        ],
        'contact' => [
            'required',
            'digits:10',
            Rule::unique('users')->ignore($id, 'id'),
        ],
       'password' => 'nullable|min:6',
        'role' => 'required|in:Therapist,Counceller',
    ], [
        'role.in' => 'Role should be Therapist or Counceller',
    ]);

    if ($validate->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validate->errors()->first(),
        ], 200);
    }

    if ($request->filled('password')) {
        $request->merge(['password' => Hash::make($request->password)]);
    } else {
        $request->request->remove('password'); // Don't update password if not sent
    }

    $staff->update($request->all());

    return response()->json([
        'status'=>true,
        'message' => 'Staff updated successfully',
        'data' => $staff
    ]);

    }

    public function deleted($id = null)
    {

        

    if (!$id) {
        return response()->json([
            'status'=>false,
            'message' => 'ID is required for update'], 400);
    }

    $staff = User::find($id);

    if (!$staff) {
        return response()->json([
            'status'=>false,
            'message' => 'Staff not found'], 200);
    }

    if($staff->deleted_at!=null){
        return response()->json([
            'status'=>false,
            'message' => 'Staff Already Deleted'], 200);
    }

    if (in_array($staff->role, ['Admin', 'User'])) {
        return response()->json([
            'status' => false,
            'message' => 'Role does not belong to staff'
        ], 200);
    }

   

  

    $staff->update([
        'deleted_at'=>1
    ]);

    return response()->json([
        'status'=>true,
        'message' => 'Staff deleted successfully',
       
    ]);



    // $staff->update($request->all());

    // return response()->json(['message' => 'Staff updated successfully', 'data' => $staff]);
    }



}
