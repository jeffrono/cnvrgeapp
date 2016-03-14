<?php
	session_start();
	require_once('dbFunctions.php');
	$link = db_connect();
	// get event info
	$event_id = $_GET['id'];
	$query= "select * from event where id = $event_id;";
	$result=mysqli_query($link,$query);
	$row = mysqli_fetch_array($result);
	$event_status = $row['status'];	// status of event
	$event_name = $row['name'];	// event name
	$event_phone = $row['phone_number'];	// event phone number
	$num_locs = $row['num_locs'];	// number of locations
	$meet_duration = $row['meet_duration'];	// meeting duration
	$email_on = $row['email_on'];	// send emails
	$sms_on = $row['sms_on'];	// send sms
	$event_email = $row['event_email'];	// send sms
	
	// get # of participants checked in
	$query= "select * from user where event_id = $event_id order by fname asc;";
	$result=mysqli_query($link,$query);
	$total_checkin = (mysqli_num_rows($result)) ? mysqli_num_rows($result): 0;
?>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>
        </title>
        <link rel="stylesheet" href="https://ajax.aspnetcdn.com/ajax/jquery.mobile/1.1.1/jquery.mobile-1.1.1.min.css" />
        <link rel="stylesheet" href="my.css" />
        <style>
            /* App custom styles */
        </style>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js">
        </script>
        <script src="https://ajax.aspnetcdn.com/ajax/jquery.mobile/1.1.1/jquery.mobile-1.1.1.min.js">
        </script>
        <script src="my.js">
        </script>
    </head>
    <body>
        <!-- Home -->
        <div data-role="page" id="page1">
            <div data-theme="b" data-role="header">
                <h3>
                    <?php echo $event_name; ?>
                </h3>
                <h2>
                    <?php echo $event_phone; ?>
                </h2>
            </div>
            <div data-role="content">
              <?php 
								switch ($event_status) {
									case 1: // event has NOT started yet
										if($total_checkin >= 3) {
								?>
							<a data-role="button" data-transition="pop" href="send_next_text.php?id=<?php echo $event_id; ?>" data-icon="check" data-iconpos="left">
								Start Event
              </a>
              <?php
								} // if
								else {
							?>
								<h3>There are <?php echo $total_checkin; ?> active participants. Wait until there are at least 3 before starting.</h3>
							<?php 
								} // else
								break;
							case 2: // event has begun, show timer
								// get meeting length
								$query="select meet_duration from event where id = $event_id;";
								$result=mysqli_query($link,$query);
								$row = mysqli_fetch_array($result);
								$meet_duration = $row["meet_duration"];
								
								$query="select order_num, TIME_TO_SEC(TIMEDIFF((DATE_ADD(meeting_status.meeting_time, INTERVAL $meet_duration MINUTE)), now())) as time_remaining from meeting_status where event_id = $event_id;";

								$result=mysqli_query($link,$query);
								$row = mysqli_fetch_array($result);
								$this_round = $row["order_num"];
								$time_remaining = $row["time_remaining"];
						?>
						Currently on meeting <b>#<?php echo $this_round; ?>.</b><br>
						
						
						<div data-role="collapsible-set" >
							<div data-role="collapsible" data-collapsed="false">
									<h3>
											Current Meetings:
									</h3>
									<ol>
									<?php
										// order by participant's name
										// NAME - Meeting Location
										$query="
										select tb1.Afname, tb1.Bfname, l.name, tb1.no_show 
										from (
											(select u1.fname as Afname, u2.fname as Bfname, m1.location_id, m1.no_show
											from meeting m1
											join user u1 on m1.A_user_id=u1.id 
											join user u2 on m1.B_user_id=u2.id
											where m1.event_id=$event_id and m1.meeting_order = $this_round)
											union
											(select u3.fname as Afname, u4.fname as Bfname, m2.location_id, m2.no_show
											from meeting m2
											join user u3 on m2.B_user_id=u3.id 
											join user u4 on m2.A_user_id=u4.id
											where m2.event_id=$event_id and m2.meeting_order = $this_round)
										) tb1
										join locations l on l.id = tb1.location_id
										order by tb1.Afname asc;";
										$result=mysqli_query($link,$query);
										while($row = mysqli_fetch_array($result)) {
									?>
										<li><b><?php echo $row["Afname"]; ?></b> -> <?php echo $row["Bfname"] . " @ " . $row["name"]; ?><?php if($row["no_show"]) { echo " <b>NO SHOW</b>"; } ?></li>
									<?php
										}
									?>
									</ol>
									
							</div>
							<div data-role="collapsible" data-collapsed="true">
									<h3>
											Checked-In w/o Meetings:
									</h3>
									
									<ol>
									<?php
										$query = "
										select fname
										from user
										where status < 4
										and event_id = $event_id
										and user.id not in
											( select A_user_id from meeting where meeting_order = $this_round and event_id = $event_id)
										and user.id not in
											( select B_user_id from meeting where meeting_order = $this_round and event_id = $event_id);";
										$result=mysqli_query($link,$query);
										while($row = mysqli_fetch_array($result)) {
									?>
										<li><b><?php echo $row["fname"]; ?></b></li>
									<?php
										}
									?>
									</ol>
									
							</div>
							<div data-role="collapsible" data-collapsed="true">
									<h3>
											Checked-Out:
									</h3>
									
									<ol>
									<?php
										$query = "
										select fname
										from user
										where status = 4
										and event_id = $event_id;";
										$result=mysqli_query($link,$query);
										while($row = mysqli_fetch_array($result)) {
									?>
										<li><b><?php echo $row["fname"]; ?></b></li>
									<?php
										}
									?>
									</ol>
									
							</div>
					</div>

						<h2>Time left in this meeting: 
						<div id="countdown"></div>
						<div id="notifier"></div>

						<script type="text/javascript">

							function display( notifier, str ) {
								document.getElementById(notifier).innerHTML = str;
							}
							
							function toMinuteAndSecond( x ) {
								return Math.floor(x/60) + ":" + x%60;
							}
							
							function setTimer( remain, actions ) {
								(function countdown() {
									 display("countdown", toMinuteAndSecond(remain));		
									 actions[remain] && actions[remain]();
									 (remain -= 1) >= 0 && setTimeout(arguments.callee, 1000);
								})();
							}

							setTimer(<?php echo $time_remaining; ?>, {
								 0: function () { window.parent.location="http://www.cnvrge.com/send_next_text.php?id=<?php echo $event_id; ?>" }
							});	

						</script>
						</h2>
						
						<a data-role="button" data-transition="pop" data-ajax="false" id ="next" href="send_next_text.php?id=<?php echo $event_id; ?>" data-icon="check" data-iconpos="left">
								Go to next meeting
            </a>

						<form data-ajax="false" action="pause_meetings.php" method="POST">
							OPTIONAL message for recepients (keep to under 140 chars): <textarea cols=40 rows=3 name="message" value="" />Your organizer just paused <?php echo $event_name; ?>.</textarea>
							<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
							<input type="submit" name="pause" value="Pause Meetings" >
						</form>

						<form data-ajax="false" action="end_meetings.php" method="POST">
							OPTIONAL message for recepients (keep to under 140 chars): <textarea cols=40 rows=3 name="message" value="" />That's a wrap! <?php echo $event_name; ?> is done, but feel free to continue hanging out.</textarea>
							<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
							<input type="submit" name="end" value="End Event" >
						</form>
						
						
						<?php 
								break;
							case 3: // event is paused
						?>
						<br>
						Event is PAUSED!
						<br>

						<a data-ajax="false" data-role="button" data-transition="pop" id ="start" href="send_next_text.php?id=<?php echo $event_id; ?>" data-icon="check" data-iconpos="left">
								Restart Meetings
            </a>
						
						<form action="end_meetings.php" method="POST">
							OPTIONAL message for recepients (keep to under 140 chars): <textarea cols=40 rows=3 name="message" value="" />That's a wrap! <?php echo $event_name; ?> is done, but feel free to continue hanging out.</textarea>
							<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
							<input type="submit" name="end" value="End Event" >
						</form>

						<?php
								break;
							case 4: // event is OVER
						?>

						<br>
						The event is over!<br>
						<a href = "create_csv.php?id=<?php echo $event_id; ?>" data-ajax="false">Download a CSV</a> of all the participants. Do not spam them. This is just for reference.<br>
						
						<?php
								break;
							} // end switch
						?>

							
								
								
                <ul data-role="listview" data-divider-theme="b" data-inset="true">
                    <li data-role="list-divider" role="heading">
                        Info
                    </li>
                    <li data-theme="c">
                        <a href="participants.php?id=<?php echo $event_id; ?>" data-transition="slide">
                            <?php echo $total_checkin; ?> Participants
                        </a>
                    </li>
                    <li data-theme="c">
                        <a href="locations.php?id=<?php echo $event_id; ?>" data-transition="slide">
                            <?php echo $num_locs; ?> Locations
                        </a>
                    </li>
                    <li data-theme="c">
                        <a href="settings.php?id=<?php echo $event_id; ?>" data-transition="slide">
                            Settings
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <script>
            //App custom javascript
        </script>
    </body>
</html>