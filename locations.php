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
	$query="select * from locations where event_id = $event_id order by number asc limit $num_locs;";
	$result=mysqli_query($link,$query);
	$num_locs = (mysqli_num_rows($result)) ? mysqli_num_rows($result): 0;

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
                    <?php echo $num_locs; ?> Locations
                </h3>
                <a data-role="button" data-inline="true" data-theme="b" href="event.php?id=<?php echo $event_id; ?>" data-icon="arrow-l" data-iconpos="left" class="ui-btn-left">
                    Back
                </a>
            </div>
            <div data-role="content">
							<form data-ajax="false" action="update_locations.php" method="POST">
							<div data-role="fieldcontain">
								<label for="textinput1" data-mini="true" data-inline="true">Locations</label>
								<input name="num_locs" id="textinput1" data-inline="true" data-mini="true" value="<?php echo $num_locs; ?>">
								<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" >
								<input data-inline="true" data-icon="gear" data-iconpos="left" value="Update" data-mini="true" type="submit">
							</div>
							</form>
							
							<form data-ajax="false" action="update_locations.php" method="POST">
								This will be displayed in the phrase:
								"Go meet Jon at (the)..."
								<div data-role="fieldcontain">
							<?php
								//$query="select * from locations where event_id = $event_id order by number asc limit $num_locs;";
								//$result=mysqli_query($link,$query);
								while($row = mysqli_fetch_array($result)) {
									$name = $row["name"];
									$i = $row["number"];
							?>
								<input type="text" iter="<?php echo $i; ?>" name="<?php echo $i; ?>" id="<?php echo $i; ?>" value="<?php echo $name; ?>" data-inline="true" data-mini="true" />
							<?php
								} // while
							?>
								</div>
								<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" >
								<input type="submit" name="updatelocations" value="Update Locations" data-inline="true" data-icon="gear" data-iconpos="left" data-mini="true">
							</form>
							
						</div>
						
						<form data-ajax="false" action="reset_locations.php" method="POST">
							<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
							<input type="submit" name="reset" value="Reset Locations" data-inline="true" data-icon="alert" data-iconpos="left" data-mini="true">
						</form>
						
        </div>
    </body>
</html>