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
 *  Initial fork created 28-8-2014
 *
 */

require __DIR__ . '/../vendor/autoload.php';

// Set these Heroku config variables
$scheduleID = getenv('PAGERDUTY_SCHEDULE_ID');
$APItoken   = getenv('PAGERDUTY_API_TOKEN');
$domain     = getenv('PAGERDUTY_DOMAIN');

$callFrom = $_REQUEST['To'];

$pagerduty = new \Vend\Phoneduty\Pagerduty($APItoken, $domain);

$userID = $pagerduty->getOncallUserForSchedule($scheduleID);

if (null !== $userID) {
    $user = $pagerduty->getUserDetails($userID);

    //get incoming caller's number
    $callerNumber = ($_REQUEST['From']);

    //Create array of numbers to use for the call. $callerNumber is the on-duty technician and will not be used as a secondary call
    $numbers = array($callerNumber, "+31629703976", "+31629710644", "+31611721250");


    $number_index = isset($_REQUEST['number_index']) ? $_REQUEST['number_index'] : "0";
    $DialCallStatus = isset($_REQUEST['DialCallStatus']) ? $_REQUEST['DialCallStatus'] : "";

    header('Content-type: text/xml');

    //Creates a PagerDuty incident when callerNumber is set, which is as soon as a call is made
    if (isset($callerNumber)) {

        $data = array(
            "service_key" => "e854881c889048248cd5b4b4c2c05edb",
            "event_type" => "trigger",
            "description" => "Support Line Call from {$_REQUEST['From']}",
            "client" => "Twilio",
            "details"=> "Number: {$_REQUEST['From']}"
        );
        $data_string = json_encode($data);

        $ch = curl_init('https://events.pagerduty.com/generic/2010-04-15/create_event.json');
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );

        $result = curl_exec($ch);
    }//end of PagerDuty incident creation

    //Call attributes allocated
    $attributes = array(
        'voice' => 'alice',
        'language' => 'en-GB'
    );
    //set a pause of length 5 seconds
    $pauseLength = array(
        'length' => 5
    );

    //sets the callerID as the Hosting Support Line instead of the client's number
    $dialAttribute = array(
        'timeout' => 28,
        'callerId' => $callFrom
    );

    if($DialCallStatus!="completed" && $number_index<count($numbers))
    {
//        if($numbers[$number_index] == $callerNumber)
//        {
//            $number_index+1;
//        }
//        if($numbers[$number_index] >= 4)
//        {
//            $number_index = 0;
//        }
        ?>
        <Response>
        <Say>Welcome to MediaMonks Support.
            This number is only for priority 1 issues.
            If you have a priority 1 issue please stay on the line.
        </Say>
        <Pause length="5"/>
        <Say>Connecting you, please wait</Say>

            <Dial action="index.php?number_index=<?php echo $number_index+1 ?>">
                <Number url="screen_for_machine.php">
                    <?php echo $numbers[$number_index] ?>
                </Number>
            </Dial>
        </Response>

    <?php
        $number_index+1;
    }//end of if statement

    else
    {?>
    <Response>
        <Say>We're sorry, but our on duty technicians are currently busy.
             We are aware of the issue and will be returning your call as soon as possible.
             Thank you for calling MediaMonks Support.
        </Say>
        <Hangup/>
    </Response>


<?php
    }//end of else statement

}
