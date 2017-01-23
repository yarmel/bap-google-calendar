<?php

namespace Yarmel\Bap\GoogleCalendar;

use DateTime;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Yarmel\Platform\Helpers\Arr;

class Event
{
    /** @var Google_Service_Calendar_Event */
    public $googleEvent;

    /** @var int */
    protected $calendarId;

    public static function createFromGoogleCalendarEvent(Google_Service_Calendar_Event $googleEvent, $calendarId)
    {
        $event = new static();

        $event->googleEvent = $googleEvent;

        $event->calendarId = $calendarId;

        return $event;
    }

    public static function create(array $properties, $calendarId = null)
    {
        $event = new static();

        $event->calendarId = static::getGoogleCalendar($calendarId)->getCalendarId();

        foreach ($properties as $name => $value) {
            $event->$name = $value;
        }

        return $event->save('insertEvent');
    }

    public function __construct()
    {
        $this->googleEvent = new Google_Service_Calendar_Event();
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $name = $this->getFieldName($name);

        if ($name === 'sortDate') {
            return $this->getSortDate();
        }
    
        $value = Arr::get($this->googleEvent, $name);

        if (in_array($name, ['start.date', 'end.date']) && $value) {
            $value = DateTime::createFromFormat('Y-m-d', $value)->setTime(0,0,0);
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime']) && $value) {
            $value = DateTime::createFromFormat(DateTime::RFC3339, $value);
        }

        return $value;
    }

    public function __set($name, $value)
    {
        $name = $this->getFieldName($name);

        if (in_array($name, ['start.date', 'end.date', 'start.dateTime', 'end.dateTime'])) {
            $this->setDateProperty($name, $value);

            return;
        }
    
        Arr::set($this->googleEvent, $name, $value);
    }

    public function exists()
    {
        return $this->id != '';
    }

    public function isAllDayEvent()
    {
        return is_null($this->googleEvent['start']['dateTime']);
    }

    public static function get($startDateTime = null, $endDateTime = null, array $queryParameters = [], $calendarId = null)
    {
        $googleCalendar = static::getGoogleCalendar($calendarId);

        $googleEvents = $googleCalendar->listEvents($startDateTime, $endDateTime, $queryParameters);

        $googleEvents = array_map(function(Google_Service_Calendar_Event $event) use ($calendarId) {
            return Event::createFromGoogleCalendarEvent($event, $calendarId);
        },$googleEvents);
        
        usort($googleEvents, function($a,$b){
            if ($a->sortDate == $b->sortDate) {
                return 0;
            }
            return ($a->sortDate < $b->sortDate) ? -1 : 1;
        });
        
        return array_values($googleEvents);
    }

    /**
     * @param string $eventId
     * @param string $calendarId
     *
     * @return \Yarmel\Bap\GoogleCalendar\Event
     */
    public static function find($eventId, $calendarId = null)
    {
        $googleCalendar = static::getGoogleCalendar($calendarId);

        $googleEvent = $googleCalendar->getEvent($eventId);

        return static::createFromGoogleCalendarEvent($googleEvent, $calendarId);
    }

    public function save($method = null)
    {
        $method = $method ? $method : ($this->exists() ? 'updateEvent' : 'insertEvent');

        $googleCalendar = $this->getGoogleCalendar($this->calendarId);

        $googleEvent = $googleCalendar->$method($this);

        return static::createFromGoogleCalendarEvent($googleEvent, $googleCalendar->getCalendarId());
    }

    /**
     * @param string $eventId
     */
    public function delete($eventId = null)
    {
        $this->getGoogleCalendar($this->calendarId)->deleteEvent($eventId ?: $this->id);
    }

    /**
     * @param string $calendarId
     *
     * @return \Yarmel\Bap\GoogleCalendar\Calendar
     */
    protected static function getGoogleCalendar($calendarId)
    {

        return Factory::createForCalendarId($calendarId);
    }

    /**
     * @param string         $name
     */
    protected function setDateProperty( $name, DateTime $date)
    {
        $eventDateTime = new Google_Service_Calendar_EventDateTime();

        if (in_array($name, ['start.date', 'end.date'])) {
            $eventDateTime->setDate($date->format('Y-m-d'));
            $eventDateTime->setTimezone($date->getTimezone());
        }

        if (in_array($name, ['start.dateTime', 'end.dateTime'])) {
            $eventDateTime->setDateTime($date->format(DateTime::RFC3339));
            $eventDateTime->setTimezone($date->getTimezone());
        }

        if (strpos($name, 'start') === 0) {
            $this->googleEvent->setStart($eventDateTime);
        }

        if (strpos($name, 'end') === 0) {
            $this->googleEvent->setEnd($eventDateTime);
        }
    }

    protected function getFieldName( $name)
    {
        $arr = [
            'name'          => 'summary',
            'description'   => 'description',
            'startDate'     => 'start.date',
            'endDate'       => 'end.date',
            'startDateTime' => 'start.dateTime',
            'endDateTime'   => 'end.dateTime',
        ];
    
        return empty($arr[$name]) ? $name : $arr[$name];
    }

    public function getSortDate()
    {
        if ($this->startDate) {
            return $this->startDate;
        }

        if ($this->startDateTime) {
            return $this->startDateTime;
        }

        return '';
    }
}
