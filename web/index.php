<?php

/**
 *
 * Twilio twimlet for forwarding inbound calls
 * to the on-call engineer as defined in PagerDuty
 *
 * Designed to be hosted on Heroku
 *
 * (c) 2014 Vend Ltd.
 *
 *  Forked and modified by Brandon Foth for use at MediaMonks B.V.
 *  (28-8-2014)
 *
 */

require __DIR__ . '/../vendor/autoload.php';

// Set these Heroku config variables
$scheduleID = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken   = getenv('PAGERDUTY_API_TOKEN');
$domain     = getenv('PAGERDUTY_DOMAIN');

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $domain);

$userID = $pagerduty->getOncallUserForSchedule($scheduleID);

if (null !== $userID) {
    $user = $pagerduty->getUserDetails($userID);

    $attributes = array(
        'voice' => 'alice',
        'language' => 'en-GB'
    );

    $twilioResponse = new Services_Twilio_Twiml();
    $response = sprintf("Welcome to MediaMonks Hosting. "
        . "This number is only for priority 1 issues. "
        . "If you have a priority 1 issue please stay on the line. "
        );

    $response2 = sprintf("Connecting you, please wait")

    $twilioResponse->say($response, $attributes);
    $twilioResponse->pause(". . . . . . . . . . . . . . . . . . . . . . . . ."); //Pause for 5 seconds
    $twilioResponse->say($response2, $attributes);
    $twilioResponse->dial( $user['phone_number'], $attributes);

    // send response
    if (!headers_sent()) {
        header('Content-type: text/xml');
    }

    echo $twilioResponse;
}
