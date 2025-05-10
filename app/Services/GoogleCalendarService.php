<?php
namespace App\Services;

use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;

class GoogleCalendarService
{
    protected Google_Service_Calendar $calendar;

    public function __construct()
    {
      
         $client = new Google_Client();

        // 2) Load your JSON creds
        $path = config('services.google.credentials');
        if (! file_exists($path)) {
            throw new RuntimeException("Google credentials not found at: {$path}");
        }
        $client->setAuthConfig($path);

        // 3) Set scopes & options
        $client->addScope(Google_Service_Calendar::CALENDAR);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        // 4) **Initialize** the calendar property here
        $this->calendar = new Google_Service_Calendar($client);



    }

    /**
     * Create an event with a Meet link.
     *
     * @param  string    $summary
     * @param  \DateTime $start
     * @param  \DateTime $end
     * @param  array     $attendeesEmails
     * @return Google_Service_Calendar_Event
     */
    public function createEventWithMeet(
        string $summary,
        \DateTime $start,
        \DateTime $end,
        array $attendeesEmails = []
    ): Google_Service_Calendar_Event {
        // Build the event
        $event = new Google_Service_Calendar_Event([
            'summary'     => $summary,
            'start'       => ['dateTime' => $start->format(\DateTime::RFC3339)],
            'end'         => ['dateTime' => $end  ->format(\DateTime::RFC3339)],
            'attendees'   => array_map(fn($e) => ['email' => $e], $attendeesEmails),
            'conferenceData' => [
                'createRequest' => [
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    'requestId'             => uniqid('meet_'),
                ],
            ],
        ]);

        // Insert with conferenceDataVersion=1 or 2
        return $this->calendar
            ->events
            ->insert(
                config('services.google.calendar_id'),
                $event,
                ['conferenceDataVersion' => 1]
            );
    }
}
