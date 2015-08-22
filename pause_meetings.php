<?php
session_start();
require_once('dbFunctions.php');
require "twilio.php";
$ApiVersion = "2010-04-01";


$client = new TwilioRestClient($AccountSid, $AuthToken);
db_connect();

$event_id = $_POST['event_id'];
$message = stripslashes($_POST['message']); // strip slash because otherwise twilio will send "it\'s"

// get event info
$query="select * from event where id = $event_id;";
$result=mysql_query($query);
$row = mysql_fetch_array($result);
$outgoing_twilio = $row['phone_number'];

// update status to PAUSE (3)
$query="update event set status = 3 where id = $event_id;";
$result=mysql_query($query);

// get correct message to send
	if(strlen($message) < 5 or strlen($message) > 80) {
		$message = "Let's take a little break! Your organizer just paused all meetings. Maybe something's about to happen? If they restart the event we'll buzz you...";
	}
	else {
		$message = "All meetings have been paused! From your organizer: $message";
		$message = substr($message, 0 , 158);
	}

////////////////////
// tell all users event is PAUSED
/////////////////////

// get active users
$query="select * from user where event_id = $event_id;";
$result=mysql_query($query);
while($row = mysql_fetch_array($result)) {
	$user_twilio = $row["twilio"];

	// text the user
	$response = $client->request("/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
		"POST", array(
			"To" => $user_twilio,
			"From" => $outgoing_twilio,
			"Body" => $message
		));
}

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>