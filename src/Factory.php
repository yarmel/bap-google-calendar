<?php

namespace Yarmel\Bap\GoogleCalendar;

use Google_Client;
use Google_Service_Calendar;
use Phalcon\DiInterface;
use Yarmel\Platform\Services\App;

class Factory
{
    public static function createForCalendarId($calendarId)
    {
        $client = new Google_Client();
        $client_json = PUBLIC_DIR.'files'.DS.App::DI()->get('config')->get('services')['google_calendar']['client_json'];
        $credentials = $client->loadServiceAccountJson(
            $client_json,
            'https://www.googleapis.com/auth/calendar'
        );

        $client->setAssertionCredentials($credentials);

        $service = new Google_Service_Calendar($client);

        return new Calendar($service, $calendarId);
    }
}
