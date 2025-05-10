<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleCalendarService;
use Validator;
use Google_Client;
use Google_Service_Calendar;

use Illuminate\Support\Facades\Session;

class MeetingController extends Controller
{
    //

     public function createMeet(Request $request, GoogleCalendarService $gcal)
    {
        $validator =Validator::make($request->all(),[
             'summary'    => 'required|string',
            'start_time' => 'required|date_format:Y-m-d H:i',
            'end_time'   => 'required|date_format:Y-m-d H:i|after:start_time',
            'attendees'  => 'array',
            'attendees.*'=> 'email',

        ]);

        if($validator->fails()){
            return response()->json([
                'status'=>false,
                'message'=>$validator->errors()->first()

            ]);
        }

      
          $client = new Google_Client();
        $client->setAuthConfig(config('services.google.credentials'));
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // 3) Attach the saved token
        $token = Session::get('google_token');
        if (! $token) {
            // no token? send them to authorize
            return redirect()->route('oauth2.redirect');
        }
        $client->setAccessToken($token);

        // 4) Refresh if expired
        if ($client->isAccessTokenExpired() && $client->getRefreshToken()) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            Session::put('google_token', $newToken);
            $client->setAccessToken($newToken);
        }

        // 5) Create the service & event
        $service = new Google_Service_Calendar($client);

        $event = $service->events->insert(
            config('services.google.calendar_id'),
            new \Google_Service_Calendar_Event([
                'summary'     => $request->summary,
                'start'       => ['dateTime' => (new \DateTime($request->start_time))->format(\DateTime::RFC3339)],
                'end'         => ['dateTime' => (new \DateTime($request->end_time))->format(\DateTime::RFC3339)],
                'attendees'   => array_map(fn($e)=>['email'=>$e], $data['attendees'] ?? []),
                'conferenceData' => [
                    'createRequest' => [
                        'conferenceSolutionKey' => ['type'=>'hangoutsMeet'],
                        'requestId'             => uniqid(),
                    ],
                ],
            ]),
            ['conferenceDataVersion'=>1]
        );

        // 6) Return the Meet link
        return response()->json([
            'meet_link' => $event
                ->getConferenceData()
                ->getEntryPoints()[0]
                ->uri,
        ]);


    }



    
    public function redirectToGoogle()
    {
        return app(\App\Http\Controllers\MeetingController::class)
            ->redirectToGoogle();
    }

    /**
     * Handle the OAuth callback.
     */
    public function handleGoogleCallback(Request $request)
    {
        return app(\App\Http\Controllers\MeetingController::class)
            ->handleGoogleCallback($request);
    }












      public function handleGoogleCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(config('services.google.credentials'));
        $client->setRedirectUri(route('oauth2.callback'));

        // Exchange the code for an access token
        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));
        if (isset($token['error'])) {
            return response()->json($token, 400);
        }

        // Store the token in session (or your database)
        Session::put('google_token', $token);
        $client->setAccessToken($token);

        return redirect('/')->with('status', 'Google Calendar connected!');
    }


}
