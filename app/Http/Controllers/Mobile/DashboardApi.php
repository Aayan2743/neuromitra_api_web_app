<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\health_daily_reports;
use Validator;
use Carbon\Carbon;

class DashboardApi extends Controller
{
    //
    public function randomQuote()
    {
        $quotes = [
            "Your mental health is a priority. Your happiness is essential. Your self-care is a necessity. — Ron",
            "You don't have to control your thoughts. You just have to stop letting them control you. — Dan Millman",
            "Healing takes time, and asking for help is a courageous step. — Mariska Hargitay",
            "Self-care is how you take your power back. — Lalah Delia",
            "You are stronger than you think, and you are not alone. — Harry",
            "Mental health… is not a destination, but a process. It's about how you drive, not where you're going. — Noam Shpancer",
            "Your illness does not define you. Your strength and courage do. — Jessica",
            "Rest when you're weary. Refresh and renew yourself, your body, your mind, your spirit. Then get back to work. — Ralph Marston",
            "It's okay to not be okay, as long as you are not giving up. — Karen Salmansohn",
            "You, yourself, as much as anybody in the entire universe, deserve your love and affection. — Buddha",
        ];
        $randomQuote = $quotes[array_rand($quotes)];  
        $randomQuote = mb_convert_encoding($randomQuote, 'UTF-8', 'UTF-8');
        
        return response()->json(['quote' => $randomQuote], 200, [], JSON_UNESCAPED_UNICODE);
    }

    // daily health feedback
    public function feedbackHealth(Request $request){
         
      
        // dd($userid->id);
        
       try {
        $userid= auth()->user();
        $validate=Validator::make($request->all(),[
            'message'=>'required|string|max:500',
        ]);

        if($validate->fails()){
            return response()->json([
                'status'=>true,
                'message'=>$validate->errors()->first()
            ],200);
        }
        
       
    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' =>$e->getMessage()
          
        ],200);
    }
        
        
        $existingFeedback = health_daily_reports::where('uid', $userid->id)
            ->whereDate('dateoffeedback', Carbon::today()) 
            ->exists();
        
        if ($existingFeedback) {
            return response()->json([
                
                'status'=>false,
                'message' => 'You can submit feedback only once per day.'], 200);
        }
        
        // If no feedback exists today, create a new one
        $createfeedback = health_daily_reports::create([
            'uid' => $userid->id,
            'message'=>$request->message,
            'status' => 1,
        ]);
        
        return response()->json([
            'status'=>true,
            'message' => 'Feedback submitted successfully.']);
        
    }
 

}
