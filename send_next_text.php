<?php
session_start();
require_once('dbFunctions.php');
require './twilio-php/Services/Twilio.php';
$ApiVersion = "2010-04-01";
$client = new Services_Twilio($AccountSid, $AuthToken);
$link = db_connect();

$event_id = $_GET['id'];

$comments_on= 0;

// get event info
$query="select * from event where id = $event_id;";
$result=mysqli_query($link,$query);
$row = mysqli_fetch_array($result);
$outgoing_twilio = $row['phone_number'];
$event_name = $row['name'];
$sms_on = $row['sms_on'];
$num_locs = $row['num_locs'];

// update status to LIVE (2)
$query="update event set status = 2 where id = $event_id;";
$result=mysqli_query($link,$query);

// get this round from meeting table
$query="select max(meeting_order) as last_round from meeting where event_id = $event_id;";
$result=mysqli_query($link,$query);
$row = mysqli_fetch_array($result);
$this_round = $row['last_round'] +1;

// get event info
$query="select * from event where id = $event_id;";
$result=mysqli_query($link,$query);
$row = mysqli_fetch_array($result);
$outgoing_twilio = $row['phone_number'];

// CHECK HERE TO SEE IF THIS ROUND WAS INCOMPLETE - ONLY PARTIAL DATA? DELETE it

// get the number of meetings we want to do this round
$query="select 1 from user where event_id = $event_id and `status` < 4;";
$result=mysqli_query($link,$query);
$num_meetings = mysqli_num_rows($result) / 2;
$num_meetings = floor($num_meetings);

// put all locations into an array
$locations = array();
$location_ids = array();
$query="select * from locations where number <= $num_locs and event_id = $event_id;";
$result=mysqli_query($link,$query);
while($row = mysqli_fetch_array($result)) {
	$location_id = $row['id'];
	$location_name = $row['name'];
	
	// the lookup table for a loc_id and the location name (from DB)
	$locations[$location_id] = $location_name;
	
	// create array of just the IDs
	array_push($location_ids, $location_id);
}

// loop through the max possible meetings
// took this out where a.status = 3 and b.status = 3

// LOG
if($comments_on) {
	echo "checking num_meetings = $num_meetings<br>";
}

for ($i = 1; $i <= $num_meetings; $i++) {
	// get all pairs of users who havent yet met each other, and aren't occupied in another meeting
	$query="select a.id as aid, a.fname as afname, a.twilio as atwilio, a.bio as abio,
	b.id as bid, b.fname as bfname, b.twilio as btwilio, b.bio as bbio,
	a.email as aemail, b.email as bemail 
	from user a
	inner join user b on a.id < b.id
	where a.status < 4 and b.status < 4
	and a.event_id = $event_id
	and b.event_id = $event_id
	and not exists (
		select *
		from meeting c
		where (c.A_user_id = a.id
		  and c.B_user_id = b.id) 
			or (c.A_user_id = b.id
		  and c.B_user_id = a.id)
			and c.no_show <> 1
			and c.event_id = $event_id
			)
	and not exists (
		select *
		from meeting d
		where (
			(d.A_user_id = a.id or d.B_user_id = a.id
			or d.A_user_id = b.id or d.B_user_id = b.id) 
			and d.meeting_order = $this_round
			and d.event_id = $event_id
		)
	)
	order by a.id *rand()
	limit 1;";
	
	$resultb=mysqli_query($link,$query);
	
	// LOG
	if($comments_on) {
		echo "QUERYING: $i<br>$query<br>";
		echo "done<br><br>";
	}
	
	// if there are no more available meetings, break from this while loop
	if( mysqli_num_rows($resultb) < 1 ) {
		break;
	}
	
	$rowb = mysqli_fetch_array($resultb);
	
	$A_user_id = $rowb["aid"];
	$A_fname = $rowb["afname"];
	
	$A_user_phone = $rowb["atwilio"];
	$A_bio = mysqli_real_escape_string($link,$rowb["abio"]);
	
	$A_email = ($rowb["aemail"]) ? $rowb["aemail"]: "User did not provide email";
	$B_email = ($rowb["bemail"]) ? $rowb["bemail"]: "User did not provide email";

	$B_user_id = $rowb["bid"];
	$B_fname = $rowb["bfname"];
	$B_user_phone = $rowb["btwilio"];
	$B_bio = mysqli_real_escape_string($link, $rowb["bbio"]);
	
	// get a random location for this meeting
	$rand_id = array_rand($location_ids); // changed
	$location_id = $location_ids[$rand_id]; //changed
	$location_name = $locations[$location_id]; // changeda
	
	// LOG
	if($comments_on) {
		echo ">> $rand_id, $location_id, $location_name<br>";
	}
	
	// THIS DOESNT fix the problem much - to help get more consistent locations, we'll now add additional locations for all but the one that was just selected
	// so next time we select a random location, the ones that weren't picked yet will be more likely to be selected
	// this may or may not be a smart idea...
	
	// loop through the array
	// remember, we are using location_id as the actual id that's in the DB
	
	// this is NOT necessary, but is trying to help randomize locations so the one previously selected is less likely to get selected again
	// foreach ($location_ids as $key=>$value) {
		// echo "[$key][$value] --> $location_id<br>";
		// // skip if the location is the one that was selected
		// if($location_id == $value) continue;
		// // push each location into the array
		// array_push($location_ids, $value);
	// }
	
	// LOG
	if($comments_on) {
		echo ">> $location_ids, $value<br>";
	}
	
	// create text message for user A
	$A_sms = "Go to $location_name and meet $B_fname: $B_bio. (Round # $this_round).";
	$B_sms = "Go to $location_name and meet $A_fname: $A_bio. (Round # $this_round).";
	
	// old version of message
	//$A_sms = "Meeting # $this_round - Go meet $B_fname ($B_bio) at $location_name.";		
	
	if($sms_on) {
		// send texts
		$response = $client->account->messages->create(array(
				"To" => $A_user_phone,
				"From" => $outgoing_twilio,
				"Body" => $A_sms
			));
				
		echo "Sent message {$response->sid}";
		
		$response = $client->account->messages->create(array(
				"To" => $B_user_phone,
				"From" => $outgoing_twilio,
				"Body" => $B_sms
			));
		echo "Sent message {$response->sid}";
	}
	
	// insert into sms_log (even if sms is off)
	$query = "insert into sms_log (event_id, user_id, sent_to, sent_from, sms_body) values 
	($event_id, $A_user_id, '$A_user_phone', '$outgoing_twilio', '$A_sms'),
	($event_id, $B_user_id, '$B_user_phone', '$outgoing_twilio', '$B_sms');";
	mysqli_query($link,$query);

	// insert into user_met table
	$query = "insert into user_met (event_id, user_id, met_user_id, meeting_order, location_id) values 
	($event_id, $A_user_id, $B_user_id, $this_round, $location_id),
	($event_id, $B_user_id, $A_user_id, $this_round, $location_id);";
	mysqli_query($link,$query);
	
	// insert into meeting table
	$query = "insert into meeting 
	(A_user_id, A_user_phone, B_user_id, B_user_phone, event_id, location_id, meeting_order, A_sms, B_sms) values
	($A_user_id, '$A_user_phone', $B_user_id, '$B_user_phone', $event_id, $location_id, $this_round, '$A_sms', '$B_sms');";
	mysqli_query($link,$query);
	
	//////////////
	// append to email digest here
	//////////////////
		
	// Meeting # 4 (near bar): John Smith (jonmsith@gmail.com), 3 word bio\n
	$A_digest_email = "Meeting # $this_round ($location_name): $A_fname ($A_email), $A_bio\n";
	$B_digest_email = "Meeting # $this_round ($location_name): $B_fname ($B_email), $B_bio\n";
	
	$query = "update user set email_digest = CONCAT( email_digest, '$A_digest_email' ) where id = $B_user_id";
	mysqli_query($link,$query);
	$query = "update user set email_digest = CONCAT( email_digest, '$B_digest_email' ) where id = $A_user_id";
	mysqli_query($link,$query);
	
}  // while

///////////////////////////////
// TAKE CARE OF ODD NUMBER PROBLEM and all participants who do not have ppl to meet during this round
// there can be more than 1 person without a meeting
// direct them to a meeting where at least 1 person is new
////////////////////////////

// users who do not have a meeting this round
$query = "select *
from user
where user.status < 4
and user.id not in (
	select A_user_id from meeting where meeting_order = $this_round and event_id = $event_id)
and user.id not in (
	select B_user_id from meeting where meeting_order = $this_round and event_id = $event_id)
	and user.event_id = $event_id;";
$result = mysqli_query($link,$query);

// loop through each extra person missing a meeting
while($row = mysqli_fetch_array($result)) {
	$A_user_phone = $row["twilio"];
	$A_fname = $row["fname"];
	$A_user_id = $row["id"];
	$A_bio = mysqli_real_escape_string($link, $row["bio"]);
	$A_email = ($row["email"]) ? $row["email"]: "User did not provide email";
	
	// find an existing meeting with at least one person they havent met
	$query = "select * 
						from meeting 
						where meeting_order = $this_round 
						and three_person = 0 
						and event_id = $event_id
						and no_show <> 1 
						and (
							(A_user_id not in
								(select met_user_id from user_met where user_id = $A_user_id and event_id = $event_id)
							) OR
							(B_user_id not in
								(select met_user_id from user_met where user_id = $A_user_id and event_id = $event_id)
							)
						)
						order by rand()
						limit 1;";
	$resultb = mysqli_query($link,$query);
	
	// has this person simply met everyone?
	if(!(mysqli_num_rows($resultb) > 0) && ($sms_on)) {
		// send them a text that they met everyone...
		$A_sms = 'Heya. Well, it looks like you met every active participant! You deserve to celebrate.';
		$response = $client->account->messages->create(array(
				"To" => $A_user_phone,
				"From" => $outgoing_twilio,
				"Body" => $A_sms
			));
			
		echo "Sent message {$response->sid}";
		
		continue;
	}
	
	$rowb = mysqli_fetch_array($resultb);
	
	// is A the stranger?
	$A_meet_id = $rowb['A_user_id'];
	$location_id = $rowb['location_id'];
	
	$query = "select 1 from user_met where user_id = $A_user_id and met_user_id = $A_meet_id and event_id = $event_id;";
	$resultb = mysqli_query($link,$query);
	
	// just swapped A for B below	
	
	if(mysqli_num_rows($resultb) > 0) {
		// the B_user_id is the stranger
		$B_user_phone = $rowb['B_user_phone'];
		$B_user_id = $rowb['B_user_id'];
		$A_sms = mysqli_real_escape_string($link, $rowb['A_sms']);
	}
	
	else {
		// the A_user_id is the stranger
		$B_user_phone = $rowb['A_user_phone'];
		$B_user_id = $rowb['A_user_id'];
		$A_sms = mysqli_real_escape_string($link, $rowb['B_sms']);
	}

	// set this meeting to a 3person meeting
	$meeting_id = $rowb['id'];
	$query = "update meeting set three_person = 1 where id = $meeting_id;"; // this should NOT require 'and event_id = $event_id'
	mysqli_query($link,$query);
	
	// message to the stranger
	$B_sms = "Heads up: You will also be meeting $A_fname ($A_bio) who will be joining your meeting to make the trifecta.";
	
	//////////////////////
	if($sms_on) {
		
		// text oddball
		$response = $client->account->messages->create(array(
				"To" => $A_user_phone,
				"From" => $outgoing_twilio,
				"Body" => $A_sms
			));
		
		echo "Sent message {$response->sid}";
		
		// text to stranger
		$response = $client->account->messages->create(array(
				"To" => $B_user_phone,
				"From" => $outgoing_twilio,
				"Body" => $B_sms
			));
			
		echo "Sent message {$response->sid}";
		
	}		
		
			// insert into sms_log
			$query = "insert into sms_log (event_id, user_id, sent_to, sent_from, sms_body) values ($event_id, $A_user_id, '$A_user_phone', '$outgoing_twilio', '$A_sms');";
			mysqli_query($link,$query);
			$query = "insert into sms_log (event_id, user_id, sent_to, sent_from, sms_body) values ($event_id, $B_user_id, '$B_user_phone', '$outgoing_twilio', '$B_sms');";
			mysqli_query($link,$query);
					
	// insert into user_met table
	$query = "insert into user_met (event_id, user_id, met_user_id, meeting_order, location_id) values ($event_id, $A_user_id, $B_user_id, $this_round, $location_id);";
	mysqli_query($link,$query);
	$query = "insert into user_met (event_id, user_id, met_user_id, meeting_order, location_id) values ($event_id, $B_user_id, $A_user_id, $this_round, $location_id);";
	mysqli_query($link,$query);
	
	// insert into meeting table
	$query = "insert into meeting 
	(A_user_id, A_user_phone, B_user_id, B_user_phone, event_id, location_id, meeting_order, A_sms, B_sms, three_person) values
	($A_user_id, '$A_user_phone', $B_user_id, '$B_user_phone', $event_id, $location_id, $this_round, '$A_sms', '$B_sms', 1);";
	//echo "$query<br>";
	mysqli_query($link,$query);
	
	//////////////
	// append to email digest here
	// need to append if this was a no_show (this happens when a user texts)
	//////////////////
	
	// get location name
	$query = "select * from locations where id = $location_id"; // dont think we NEED "and event_id=$event_id;"
	$resultb = mysqli_query($link,$query);
	$rowb = mysqli_fetch_array($resultb);
	$location_name = $rowb['name'];
	
	// Meeting # 4 (near bar): John Smith (jonmsith@gmail.com), 3 word bio\n
	$A_digest_email = "Meeting # $this_round ($location_name): $A_fname ($A_email), $A_bio\r\n";
	$B_digest_email = "Meeting # $this_round ($location_name): $B_fname ($B_email), $B_bio\r\n";
	
	$query = "update user set email_digest = CONCAT( email_digest, '$A_digest_email' ) where id = $B_user_id";
	mysqli_query($link,$query);
	$query = "update user set email_digest = CONCAT( email_digest, '$B_digest_email' ) where id = $A_user_id";
	mysqli_query($link,$query);

} // end while loop

// update the meet_status table
$query = "replace into meeting_status (event_id, order_num, status, meeting_time) values ($event_id, $this_round, 1, now());";
mysqli_query($link,$query);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");

?>