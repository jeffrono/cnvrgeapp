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
		$from='From: "Team Cnvrge" <info@cnvrge.com>';
		$title = "Intros from $event_name";
		$mess =  "Hi $user_name,\nHere is a list of all the folks you met at $event_name.\n\n$email_digest\n\nEnjoy!\nTeam Cnvrge";
		mail($to, $title, $mess, $from);
	}
}

/////////////////
// send organizer a digest email
/////////////////

if($event_email) {
	$query="select * from user where event_id = $event_id;";
	$result=mysqli_query($query);
	while($row = mysqli_fetch_array($result)) {
		$email_text .= $row['fname']. "\t" . $row['bio']. "\t" . $row['email']. "\n";
		
		$query = "select user_met.meeting_order, user.fname, user.bio, locations.name
						from user_met
						join locations on user_met.location_id = locations.id
						join user on user_met.met_user_id = user.id
						where user_id = ". $row['id'];
		$resulta =mysqli_query($query);
		$count = mysqli_num_rows($resulta);
		$email_text .= "\tMet the following $count people:\n";
		while($rowa = mysqli_fetch_array($resulta)) {
			$email_text .= "\tRound # " . $rowa['meeting_order'] . ": " .  $rowa['fname'] . " (at " . $rowa['name'] . ")\n";
		}	
	} //while
	$to = '"' . $event_name . '" <' . $event_email . '>';
	$from='From: "Team Cnvrge" <info@cnvrge.com>';
	$title = "Participants from $event_name (care of Cnvrge)";
	$mess =  "Hi,\nHere is a list of all the folks who participated at $event_name using Cnvrge.  (Do not spam them. This is just for reference.)\n\n$email_text\n\nEnjoy!\nThe CNVRGE Team";
	mail($to, $title, $mess, $from);
}

// update status to END (4)
$query="update event set status = 4 where id = $event_id;";
$result=mysqli_query($query);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>