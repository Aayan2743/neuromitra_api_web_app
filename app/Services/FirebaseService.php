<?php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {
        $factory = (new Factory)
            ->withServiceAccount(storage_path('app/firebase/firebase_credentials.json'));

        $this->messaging = $factory->createMessaging();
    }

    public function sendNotification($deviceToken, $title, $body)
    {
        $notification = Notification::create($title, $body);

        $message = CloudMessage::withTarget('token', $deviceToken)
            ->withNotification($notification);

        return $this->messaging->send($message);


        // $notification = Notification::create($title, $body);
        // $message = CloudMessage::new()->withNotification($notification);
    
        // return $this->messaging->sendMulticast($message, $deviceToken);


        // $notification = Notification::create($title, $body);
        // $message = CloudMessage::new()->withNotification($notification);
    
        // $report = $this->messaging->sendMulticast($message, $deviceTokens);
    
        // // Optionally log success/failure
        // $failedTokens = [];
    
        // foreach ($report->failures()->getItems() as $failure) {
        //     $failedTokens[] = $failure->target()->value(); // The invalid token
        // }
    
        // if (!empty($failedTokens)) {
        //     // Optional: delete or log invalid tokens
        //     // Example:
        //     // User::whereIn('web_fcm_token', $failedTokens)->update(['web_fcm_token' => null]);
        // }
    
        // return [
        //     'successCount' => $report->successes()->count(),
        //     'failureCount' => $report->failures()->count(),
        //     'invalidTokens' => $failedTokens,
        // ];




    }
}
