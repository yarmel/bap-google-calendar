<?php

namespace Yarmel\Bap\GoogleCalendar;

use DateTime;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class Calendar
{
    /** @var \Google_Service_Calendar */
    protected $calendarService;

    /** @var string */
    protected $calendarId;

    public function __construct(Google_Service_Calendar $calendarService, $calendarId)
    {
        $this->calendarService = $calendarService;

        $this->calendarId = $calendarId;
    }

    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * @param array          $queryParameters
     *
     * @link https://developers.google.com/google-apps/calendar/v3/reference/events/list
     *
     * @return array
     */
    public function listEvents($startDateTime = null, $endDateTime = null,array $queryParameters = [])
    {
        $parameters = ['singleEvents' => true];

        if (is_null($startDateTime)) {
            $startDateTime = new DateTime();
            $startDateTime->setTime(0,0,0);
        }

        $parameters['timeMin'] = $startDateTime->format(DateTime::RFC3339);

        if (is_null($endDateTime)) {
            $endDateTime = new DateTime();
            $endDateTime->setTime(23,59,59);
        }
        $parameters['timeMax'] = $endDateTime->format(DateTime::RFC3339);

        $parameters = array_merge($parameters, $queryParameters);

        return $this
            ->calendarService
            ->events
            ->listEvents($this->calendarId, $parameters)
            ->getItems();
    }

    /**
     * Get a single event.
     *
     * @param string $eventId
     *
     * @return \Google_Service_Calendar_Event
     */
    public function getEvent($eventId)
    {
        return $this->calendarService->events->get($this->calendarId, $eventId);
    }

    /**
     * Insert an event.
     *
     * @param \Yarmel\Bap\GoogleCalendar\Event|Google_Service_Calendar_Event $event
     *
     * @link https://developers.google.com/google-apps/calendar/v3/reference/events/insert
     *
     * @return \Google_Service_Calendar_Event
     */
    public function insertEvent($event)
    {
        if ($event instanceof Event) {
            $event = $event->googleEvent;
        }

        return $this->calendarService->events->insert($this->calendarId, $event);
    }

    /**
     * @param \Yarmel\Bap\GoogleCalendar\Event|Google_Service_Calendar_Event $event
     *
     * @return \Google_Service_Calendar_Event
     */
    public function updateEvent($event)
    {
        if ($event instanceof Event) {
            $event = $event->googleEvent;
        }

        return $this->calendarService->events->update($this->calendarId, $event->id, $event);
    }

    /**
     * @param string|\Yarmel\Bap\GoogleCalendar\Event $eventId
     */
    public function deleteEvent($eventId)
    {
        if ($eventId instanceof Event) {
            $eventId = $eventId->id;
        }

        $this->calendarService->events->delete($this->calendarId, $eventId);
    }

    public function getService()
    {
        return $this->calendarService;
    }
}
