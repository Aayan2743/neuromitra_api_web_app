<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FirebaseService;

class NotificationController extends Controller
{
    //
    protected $firebase;

    public function __construct(FirebaseService $firebase)
    {
        $this->firebase = $firebase;
    }

    public function send()
    {   
        // for mobile
        // $deviceToken = 'efb_H0pMT56Ne5vZcPczfH:APA91bHZM5cNHegT5rKuept7FJxEmwlnymDgAJ-SBLNzrmKg-DxqtOpVXO5lli8dzqLD1RVA4Y3s8Z0ynAohvcbm_Q-MQCFSwy6BCFNOhhtGQyt3kBjM1_g'; // Replace with actual FCM token
        
       // for web     
        $deviceToken = 'et5_U9fdET5ML1Gh27ZROs:APA91bEkITthREggJzIzcTLKKmDIciBWKw2yDOEq2ekME7WvMhEab9zDNnNqH61yqn9mx0QaQKv7w9cN0c7Q_GrjAwcOEJF6eKCzq880mlGFNJLlwmvI2Xs'; // Replace with actual FCM token
        $title = 'Hello';
        $body = 'This is a test notification';

        $response = $this->firebase->sendNotification($deviceToken, $title, $body);

        return response()->json(['message' => $response]);
    }

}
