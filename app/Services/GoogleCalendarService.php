<?php
namespace App\Services;

use Google\Client;

use Google\Service\Calendar;
use Google\Service\Calendar\Event;

use Google\Service\Calendar\ConferenceDataSource;
use Google\Service\Calendar\CreateConferenceRequest;

class GoogleCalendarService
{
   protected Calendar $calendar;

   public function __construct()
    {
        $client = new Client();
        $client->setAuthConfig(config('services.google.credentials_json'));
        $client->addScope(Calendar::CALENDAR);
        $client->setAccessType('offline');
        // If using a service account with domain-wide delegation:
        $client->setSubject('teamozrit@gmail.com');

        // $calendarService = new \Google\Service\Calendar($client);

        $this->calendar = new Calendar($client);
    }
     public function createMeeting(string $summary, \DateTime $start, \DateTime $end): string
    {
        // Build the event, including conferenceData as a plain array
        $event = new Event([
            'summary'        => $summary,
            'start'          => ['dateTime' => $start->format(\DateTime::RFC3339)],
            'end'            => ['dateTime' => $end->format(\DateTime::RFC3339)],
            'conferenceData' => [
                'createRequest' => [
                    'requestId'             => uniqid(),           // any unique string
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ]);

        // Insert the event; you MUST include conferenceDataVersion=1
        $created = $this->calendar->events->insert(
            config('services.google.calendar_id'),
            $event,
            ['conferenceDataVersion' => 1]
        );

        // Find the “video” entry point (that’s the Meet link)
        foreach ($created->getConferenceData()->getEntryPoints() as $entry) {
            if ($entry->getEntryPointType() === 'video') {
                return $entry->getUri();
            }
        }

        throw new \Exception('No Meet link was created.');
    }
   
}
