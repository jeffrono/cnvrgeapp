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
$result=mysqli_query($query);
$row = mysqli_fetch_array($result);
$outgoing_twilio = $row['phone_number'];
$event_name = $row['name'];
$event_email = $row['event_email'];
$sms_on = $row['sms_on'];
$email_on = $row['email_on'];

////////////////////
// tell all users event is OVER
/////////////////////

// get correct message to send
$message = substr($message, 0 , 158);

// notify all active users
// also email digest of all meetings
$query="select * from user where event_id = $event_id;";
$result=mysqli_query($query);
while($row = mysqli_fetch_array($result)) {
	$user_twilio = $row["twilio"];
	$user_name = $row["fname"];
	$user_email = $row["email"];
	$email_digest = $row["email_digest"];
	
	
	if($sms_on) {
		// text the user
		$response = $client->request("/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
			"POST", array(
				"To" => $user_twilio,
				"From" => $outgoing_twilio,
				"Body" => $message
			));
	}
		
	// this user provided an email
	if(($user_email) && ($email_on)) {
		$to = '"' . $user_name . '" <' . $user_email . '>';
		$from='From: "' . $event_name . '" <' . $event_email . '>';
		$title = "Intros from $event_name";
		$mess =  "Hi $user_name,\nHere is a list of all the folks you met at $event_name.\n\n$email_digest\n\nEnjoy!";
		mail($to, $title, $mess, $from);
	}
}


// update status to END (4)
$query="update event set status = 4 where id = $event_id;";
$result=mysqli_query($query);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>