<?php
// need to fix this
// when user sends text, identify the TO number as the id for the event (all events will have unique phone numbers)


$sms = $_POST['Body'];
$sms = rtrim($sms);

$from = $_POST['From'];
$to = $_POST['To'];

require_once('dbFunctions.php');
require "twilio.php";
$ApiVersion = "2010-04-01";

$client = new TwilioRestClient($AccountSid, $AuthToken);
db_connect();

header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<Response>";

// get the event info
$query="select * from event where twilio_phone = '$to';";
$result=mysql_query($query);
$row = mysql_fetch_array($result);
$event_name = $row['name'];
$event_id= $row['id'];
$outgoing_twilio = $row['phone_number'];
$question = $row['question']; //"Now give a 3 word bio.";

// get event info
$query="select * from user where twilio = '$from' and event_id = $event_id limit 1;";
$result=mysql_query($query);
$row = mysql_fetch_array($result);
$u_status = 0;
$u_status = $row['status'];
$u_name = $row['fname'];
$A_user_id = $row['id'];

switch ($u_status) {
	case 0: // hasnt entered into the system
	
		// is this really long?
		if(strlen($sms) > 16) {
?>
		
			<Sms>Sorry, but that's a *really* long first name and last initial! Give me something shorter this time. Go.</Sms>
		
<?php
		} else {
			$query="select * from user where fname = '$sms' and event_id = $event_id;";
			$result=mysql_query($query);
			$num_rows = mysql_num_rows($result);
			if($num_rows > 0) {
?>
		
			<Sms>Sorry, we already have a '<?php echo $sms; ?>' at the event! We don't want to confuse people. Mind responding with your full name (ie Bill Johnson)?</Sms>
		
<?php
			} else {
				// check int'l number
				$intl = (strlen($from) == 12) ? '0': '1';
				// insert user
				$query="insert ignore into user (event_id, twilio, fname, status, intl) values ($event_id, '$from', '$sms', 1, $intl);";
				mysql_query($query);
				// if the person is int'l then we can't respond, so the event organizer better know about them
?>
		
			<Sms>Thanks, <?php echo $sms; ?>! We've got you at <?php echo $event_name; ?>. <?php echo $question; ?> (Step 2/3)</Sms>
		
<?php
			} // else name is NOT in the system
		} // else length is ok
		break;
	case 1: // has checked in, is texting BIO
		// if too long, reject it with funny message
		if(strlen($sms) > 55) {
?>
		
			<Sms>Sorry, but that's a *really* long answer for this question! Give me something shorter this time. Go.</Sms>
		
<?php
		} else {
			$query="update user set bio='$sms', status = 2 where twilio = '$from' and event_id = $event_id;";
			mysql_query($query);
?>
		
			<Sms>What's your email? It'll only be used for this event. At the end you'll get a list of everyone you met. (Respond "NO" to be excluded.) (Step 3/3)</Sms>
		
<?php
		}
		break;
	case 2: // is texting email
		if(strcasecmp($sms, 'no') == 0) {
			$query="update user set email ='', status = 3 where twilio = '$from' and event_id = $event_id;";
			mysql_query($query);
?>
		
			<Sms>Thx, get ready to meet some people! FYI you may text 'OUT' (to quit or take a break) and 'IN' (to jump back in for the next round).</Sms>
					
<?php
		}
		//if email is valid
		elseif(filter_var($sms, FILTER_VALIDATE_EMAIL)) {
			$query="update user set email='$sms', status = 3 where twilio = '$from' and event_id = $event_id;";
			mysql_query($query);
?>
		
			<Sms>Thx, get ready to meet some people! FYI you may text 'OUT' (to quit or take a break) and 'IN' (to jump back in for the next round).</Sms>
		
<?php
		} else {
?>
		
			<Sms>Sorry, this doesn't look like an email address. We need a valid email if you want to get a list of all the people you meet. Or respond 'no' to decline.</Sms>
		
<?php
		}
		break;
	case 3: // is texting OUT?
		if(strcasecmp($sms, 'out') == 0 OR strcasecmp($sms, 'pause') == 0) {
			$query="update user set status=4 where twilio = '$from' and event_id = $event_id;";
			mysql_query($query);
?>
		
			<Sms>You've been checked out.  You may text 'IN' if you would like to participate again.</Sms>
		
<?php
		} // if
		// if the OTHER person didnt show...
		elseif( (strcasecmp($sms, 'no show') == 0) OR (strcasecmp($sms, 'noshow') == 0) ) {
			// get this user's B
			$query="select *
				from meeting m join meeting_status s on m.meeting_order = s.order_num
				where (
					(m.A_user_id in (select id from user u where u.twilio = '$from' and u.event_id = $event_id) )
					OR
					(m.B_user_id in (select id from user u where u.twilio = '$from' and u.event_id = $event_id) )
					and u.event_id = $event_id
				);";
			$result=mysql_query($query);
			$row = mysql_fetch_array($result);
			$B_user_id = ($row['A_user_phone'] == $from) ? $row['B_user_id']: $row['A_user_id'];
			$B_user_phone = ($row['A_user_phone'] == $from) ? $row['B_user_phone']: $row['A_user_phone'];
			
			// check out B
			$query="update user set status=4 where twilio = '$B_user_phone' and event_id = $event_id;";
			mysql_query($query);
			
			// set this meeting "no_show" to 1, bc it never happened!
			// need to put this into query for subsequent rounds, and for email
			$query="update meeting set no_show=1 where id = " . $row['id'];
			mysql_query($query);
			
			// tell B they've been checked out.
			$B_sms = "You were supposed to meet $u_name this round, but you stood them up! So we've opted you out. Don't worry, to opt back in respond 'in'.";
						
			$response = $client->request("/$ApiVersion/Accounts/$AccountSid/SMS/Messages",
				"POST", array(
					"To" => $B_user_phone,
					"From" => $outgoing_twilio,
					"Body" => $B_sms
				));
			
			
			// insert this into sms log
			$B_sms = mysql_real_escape_string($B_sms);
			$query = "insert into sms_log (event_id, user_id, sent_to, sent_from, sms_body) values ($event_id, $B_user_id, '$B_user_phone', '$outgoing_twilio', '$B_sms');";
			mysql_query($query);
		
			// append to the email (one of you didn't show up to the previous meeting)
			$A_digest_email = mysql_real_escape_string("The previous meeting didn't happen because the other participant didn't show up.\n");
			$B_digest_email = mysql_real_escape_string("The previous meeting didn't happen because you didn't show up.\n");
			$query = "update user set email_digest = CONCAT( email_digest, '$A_digest_email' ) where id = $B_user_id";
			mysql_query($query);
			$query = "update user set email_digest = CONCAT( email_digest, '$B_digest_email' ) where id = $A_user_id";
			mysql_query($query);
			
			
			// find a new person and create a meeting - find someone who's status is 'stood up' - if there is at least 3 mins left, then send the text and connect them
			
			// if < 3 mins, tell this user to wait it out solo til the next meeting happens.
			
?>		
		
			<Sms>Sorry they didn't show up. We gave them the boot for now so they won't be in subsequent meetings. For now, just hang out until the next meeting.</Sms>
		
<?php
		} else {
?>
		
			<Sms>Sorry, didn't catch that. If you want to be excluded, text "out".</Sms>
		
<?php
		}
		break;
	case 4: // is texting IN?
		if(strcasecmp($sms, 'in') == 0) {
			$query="update user set status=3 where twilio = '$from' and event_id = $event_id;";
			mysql_query($query);
?>
		
			<Sms>Welcome back! You'll be included in the next round of meetings.</Sms>
		
<?php
		} else {
?>
		
			<Sms>Sorry, didn't catch that. If you want to be included, text "in".</Sms>
		
<?php
		}
		break;
		
} // switch
?>
</Response>