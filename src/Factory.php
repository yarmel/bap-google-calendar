<?php

namespace Yarmel\Bap\GoogleCalendar;

use Google_Client;
use Google_Service_Calendar;
use Phalcon\DiInterface;

class Factory
{
    public static function createForCalendarId(DiInterface $di, $calendarId)
    {
        $client_json = PUBLIC_DIR.'files'.DS.$di->get('config')->get('services')['google_calendar']['client_json'];
        $client = new Google_Client();

        $credentials = $client->loadServiceAccountJson(
            $client_json,
            'https://www.googleapis.com/auth/calendar'
        );

        $client->setAssertionCredentials($credentials);

        $service = new Google_Service_Calendar($client);

        return new Calendar($service, $calendarId);
    }
}
