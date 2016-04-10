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

	// get number of checked in ppl
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
        <div data-role="page" id="page1">
            <div data-theme="a" data-role="header">
                <h3>
                    <?php echo $total_checkin; ?> Participants
                </h3>
                <a data-role="button" data-inline="true" data-theme="b" href="event.php?id=<?php echo $event_id; ?>" data-icon="arrow-l" data-iconpos="left" class="ui-btn-left">
                    Back
                </a>
            </div>
            <div data-role="content">
							<div data-role="collapsible-set">
								<?php
									// show "partially checked in" if relevant
									$query= "select * from user where event_id = $event_id and status < 3 order by fname asc;";
									$result=mysqli_query($link,$query);
									if( mysqli_num_rows($result) > 0) {
									?>
										<div data-role="collapsible" data-collapsed="false">
											<h3><?php echo mysqli_num_rows($result); ?> Partial Check-Ins</h3>
											<ol>
											<?php
												while( $row = mysqli_fetch_array($result) ) {
											?>
												<li>
													<?php
														// list out the participant
														echo $row['fname']; ?> (<?php echo $row['email']; ?>) // <?php echo $row['twilio']; ?>: <?php echo $row['bio'];
													?>
													<form data-ajax="false" action="update_user.php" method="POST">
														<input type="hidden" name="user_id" value="<?php echo $row['id']; ?>" />
														<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
														<input data-inline="true" data-theme="b" data-mini="true" data-icon="delete" data-iconpos="left" type="submit" name="deleteuser" value="X" >
													</form>
												</li>
											<?php 
												} // while 
											?>
											</ol>
										</div>
									<?php 
										} // if 
									?>
									
									<?php
									// show "fully checked in" if relevant
									$query= "select * from user where event_id = $event_id and status = 3 order by fname asc;";
									$result=mysqli_query($link,$query);
									if( mysqli_num_rows($result) > 0) {
									?>
										<div data-role="collapsible" data-collapsed="false">
											<h3><?php echo mysqli_num_rows($result); ?> Complete Check-Ins</h3>
											<ol>
											<?php
												while( $row = mysqli_fetch_array($result) ) {
											?>
												<li>
													<?php
														// list out the participant
														echo $row['fname']; ?> (<?php echo $row['email']; ?>) // <?php echo $row['twilio']; ?>: <?php echo $row['bio'];
													?>
													<form data-ajax="false" action="update_user.php" method="POST">
														<input type="hidden" name="user_id" value="<?php echo $row['id']; ?>" />
														<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
														<input data-inline="true" data-theme="b" data-mini="true" data-icon="delete" data-iconpos="left" type="submit" name="deleteuser" value="X" >
														<input data-inline="true" data-theme="b" data-mini="true" data-icon="gear" data-iconpos="left" type="submit" name="deactivate" value="OUT" >
													</form>
												</li>
											<?php 
												} // while 
											?>
											</ol>
										</div>
									<?php 
										} // if 
									?>
									
									
									<?php
									// show "checked out" if relevant
									$query= "select * from user where event_id = $event_id and status = 4 order by fname asc;";
									$result=mysqli_query($link,$query);
									if( mysqli_num_rows($result) > 0) {
									?>
										<div data-role="collapsible" data-collapsed="false">
											<h3><?php echo mysqli_num_rows($result); ?> Check-Outs</h3>
											<ol>
											<?php
												while( $row = mysqli_fetch_array($result) ) {
											?>
												<li>
													<?php
														// list out the participant
														echo $row['fname']; ?> (<?php echo $row['email']; ?>) // <?php echo $row['twilio']; ?>: <?php echo $row['bio'];
													?>
													<form data-ajax="false" action="update_user.php" method="POST">
														<input type="hidden" name="user_id" value="<?php echo $row['id']; ?>" />
														<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
														<input data-inline="true" data-theme="b" data-mini="true" data-icon="delete" data-iconpos="left" type="submit" name="deleteuser" value="X" >
														<input data-inline="true" data-theme="c" data-mini="true" data-icon="gear" data-iconpos="left" type="submit" name="activate" value="IN" >
													</form>
												</li>
											<?php 
												} // while 
											?>
											</ol>
										</div>
									<?php 
										} // if 
									?>
									
									
							</div>
						</div>
        </div>
    </body>
</html>