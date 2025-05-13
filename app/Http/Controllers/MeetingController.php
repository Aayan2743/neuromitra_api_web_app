<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GoogleCalendarService;
use Validator;
use Google_Client;
use Google_Service_Calendar;
use MacsiDigital\Zoom\Facades\Zoom;
use Carbon\Carbon;
use App\Services\ZoomService;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendar;

use Illuminate\Support\Facades\Session;

class MeetingController extends Controller
{


      public function __construct(protected ZoomService $zoom) {}

    public function createOnCustomDates(Request $request)
    {
        // If “dates” came in as a JSON-encoded string, decode it:
        if (is_string($request->input('dates'))) {
            $request->merge(['dates' => json_decode($request->input('dates'), true)]);
        }

        $data = $request->validate([
            'topic'      => 'required|string',
            'dates'      => 'required|array',
            'dates.*'    => 'required|date_format:d-m-Y',
            'start_time' => 'required|date_format:H:i',
            'end_time'   => 'required|date_format:H:i|after:start_time',
        ]);

        $duration = Carbon::parse($data['start_time'])
                          ->diffInMinutes(Carbon::parse($data['end_time']));

        $results = [];
        foreach ($data['dates'] as $date) {
            $startIso = Carbon::createFromFormat(
                'd-m-Y H:i',
                "{$date} {$data['start_time']}",
                'Asia/Kolkata'
            )->toIso8601String();

            // build the payload
            $payload = [
                'topic'      => "{$data['topic']} ({$date})",
                'type'       => 2,               // scheduled meeting
                'start_time' => $startIso,
                'duration'   => $duration,
                'timezone'   => 'Asia/Kolkata',
                'settings'   => [
                    'host_video'        => true,
                    'participant_video' => true,
                    'waiting_room'      => true,
                    'join_before_host'  => false,
                ],
            ];

            // call Zoom
            $results[] = $this->zoom->createMeeting($payload);
        }

        return response()->json($results);
    }

    // nnnn
     public function redirectToGoogle()
    {
        $client = new GoogleClient();
        // Point this at the JSON you downloaded from Cloud Console:
        $client->setAuthConfig(config('services.google.credentials_json'));
        $client->addScope(GoogleCalendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setRedirectUri('http://localhost:8000/api/google/callback');

       dd($client->createAuthUrl());

    return redirect()->away($client->createAuthUrl());

        // Laravel’s redirect helper will send them to Google

    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new GoogleClient();
        $client->setAuthConfig(config('services.google.credentials_json'));
        $client->addScope(GoogleCalendar::CALENDAR);
        $client->setAccessType('offline');

        // Exchange the authorization code for an access token
        $token = $client->fetchAccessTokenWithAuthCode($request->input('code'));

        if (isset($token['error'])) {
            abort(500, 'Google OAuth error: ' . $token['error_description']);
        }

        // $token contains:
        // - access_token
        // - expires_in
        // - refresh_token (only on first consent)
        // Persist this to your database (or session) tied to the logged‐in user
        auth()->user()->update([
            'google_access_token'  => $token['access_token'],
            'google_refresh_token' => $token['refresh_token'] ?? null,
            'google_token_expires' => now()->addSeconds($token['expires_in']),
        ]);


        return response()->json([
            'status'=>true,
            'message'=>'Google Calendar linked'
        ]);
        // return redirect('/')->with('success', 'Google Calendar linked!');
    }

 



    
   















}
