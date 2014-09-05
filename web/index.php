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

    //get incoming caller's number
    $callerNumber = ($_REQUEST['From']);

    //sends an email to pagerduty which will create an incident with the client's phone number within
    header('Content-type: text/xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    $to = "hosting@mediamonks.pagerduty.com";
    $subject = "New client call from {$_REQUEST['From']} at {$_REQUEST['To']}";
    $message = "A call has been placed to the Hosting Support Line from the number {$_REQUEST['From']}.";
    $headers = "From: mediamonks@twilio.com";

    mail($to, $subject, $message, $headers);

    $attributes = array(
        'voice' => 'alice',
        'language' => 'en-GB'
    );
    //set length of pause (5 seconds)
    $pauseLength = array(
        'length' => 5
    );
    //sets the callerID as the Hosting Support Line instead of the client's number
    $dialAttribute = array(
        'callerId' => +14242066657
    );

    $twilioResponse = new Services_Twilio_Twiml();
    $response = sprintf("Welcome to MediaMonks Hosting. "
        . "This number is only for priority 1 issues. "
        . "If you have a priority 1 issue please stay on the line. "
    );

    $response2 = sprintf("Connecting you, please wait");

    $twilioResponse->say($response, $attributes);
    $twilioResponse->pause("", $pauseLength); //Pause for 5 seconds
    $twilioResponse->say($response2, $attributes);
    $twilioResponse->dial($user['phone_number'], $dialAttribute);

    // send response
    if (!headers_sent()) {
        header('Content-type: text/xml');
    }

    echo $twilioResponse;
}
