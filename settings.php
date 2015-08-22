<?php
	session_start();
	require_once('dbFunctions.php');
	require_once('Eventbrite.php'); // does this work?
	db_connect();

	// get event info
	$event_id = $_GET['id'];
	$query= "select * from event where id = $event_id;";
	$result=mysql_query($query);
	$row = mysql_fetch_array($result);
	$event_status = $row['status'];	// status of event
	$event_name = $row['name'];	// event name
	$event_phone = $row['phone_number'];	// event phone number
	$num_locs = $row['num_locs'];	// number of locations
	$meet_duration = $row['meet_duration'];	// meeting duration
	$email_on = $row['email_on'];	// send emails
	$sms_on = $row['sms_on'];	// send sms
	$event_email = $row['event_email'];	// send sms
	$question = $row['question'];	// the question prompt ie "3 word bio"
	
	$eventbrite_id = $row['eventbrite_id'];	// eventbrite id
	
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
            <div data-theme="a" data-role="header">
                <h3>
                    Settings
                </h3>
                <a data-role="button" data-inline="true" data-theme="b" href="event.php?id=<?php echo $event_id; ?>" data-icon="arrow-l" data-iconpos="left" class="ui-btn-left">
                    Back
                </a>
            </div>
            <div data-role="content">
							<form data-ajax="false" action="update_event.php" method="POST">
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="textinput1">
                            Name:
                        </label>
                        <input name="name" id="textinput1" placeholder="" value="<?php echo $event_name; ?>" type="text" />
                    </fieldset>
                </div>
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="textinput3">
                            Email:
                        </label>
                        <input name="event_email" id="textinput3" placeholder="" value="<?php echo $event_email; ?>" type="text" />
                    </fieldset>
                </div>
								<div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="textinput3">
                            Question:
                        </label>
                        <input name="question" id="textinput3" placeholder="" value="<?php echo $question; ?>" type="text" />
                    </fieldset>
                </div>
								
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="slider1">
                            Meeting Length (in mins):
                        </label>
                        <input name="meet_duration" value="<?php echo $meet_duration; ?>" min="4" max="15" data-highlight="true" data-theme="b" type="range" />
                    </fieldset>
                </div>
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="email_on">
                            Send Emails:
                        </label>
                        <select name="email_on" id="email_on" data-theme="b" data-role="slider">
                            <option value="0" <?php echo ($email_on) ? "": "selected"; ?>>
                                Off
                            </option>
                            <option value="1" <?php echo ($email_on) ? "selected": ""; ?>>
                                On
                            </option>
                        </select>
                    </fieldset>
                </div>
                <div data-role="fieldcontain">
                    <fieldset data-role="controlgroup">
                        <label for="sms_on">
                            Send SMS:
                        </label>
                        <select name="sms_on" id="sms_on" data-theme="b" data-role="slider">
                            <option value="0" <?php echo ($sms_on) ? "": "selected"; ?>>
                                Off
                            </option>
                            <option value="1" <?php echo ($sms_on) ? "selected": ""; ?>>
                                On
                            </option>
                        </select>
                    </fieldset>
                </div>
								<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
								<input data-icon="info" data-iconpos="left" value="Update" name="updateevent" type="submit" >
							</form>
						        
				
				<form data-ajax="false" action="reset.php" method="POST">
					<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
					<input data-icon="gear" data-iconpos="left" type="submit" name="reset" value="Reset Event" />
				</form>

					<form data-ajax="false" action="demomode.php" method="POST">
						<input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />
						<select data-inline="true" name="num_accts">
							<option value="17">17</option>
							<option value="50">50</option>
							<option value="123">123</option>
						</select>
						<input data-inline="true" data-icon="gear" data-iconpos="left" type="submit" name="demo" value="Demo Mode" />
					</form>
				
				<a data-role="button" data-inline="true" data-theme="b" href="https://www.eventbrite.com/oauth/authorize?response_type=code&client_id=KVQWVOYBKAROXTKG2P&id=<?php echo $event_id; ?>" data-icon="gear" data-iconpos="left" class="ui-btn">
					Sync
        </a>
				<?php
					$eb_code = $_GET['code'];
					if($eb_code) {
					
					//set POST variables
					$url = 'https://www.eventbrite.com/oauth/token';
					$fields = array(
											'code'=>urlencode($eb_code),
											'client_secret'=>urlencode('2DHI4WMNSWSBKQIYRR7GAJK52W6SRERNHDWFHKJDSVLIFAFIWO'),
											'client_id'=>urlencode('KVQWVOYBKAROXTKG2P'),
											'grant_type'=>urlencode('authorization_code')
									);

					//url-ify the data for the POST
					foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
					rtrim($fields_string,'&');

					//open connection
					$ch = curl_init();

					//set the url, number of POST vars, POST data
					curl_setopt($ch,CURLOPT_URL,$url);
					curl_setopt($ch,CURLOPT_POST,count($fields));
					curl_setopt($ch,CURLOPT_POSTFIELDS,$fields_string);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE); // delivers html without auto echo
					$result = curl_exec($ch);
					curl_close($ch); // close connection
					$obj = json_decode($result);
					$access_token = $obj->{'access_token'};
					echo "access: $access_token -- eb id: $eventbrite_id";
					
					$eb_client = new Eventbrite( array('access_token'=> $access_token )); 
					$resp = $eb_client->event_get( array('id' => $eventbrite_id ) );
					echo "ok: $resp";
					
					
					}
				?>
				
				</div>
				
				<script>
            //App custom javascript
        </script>
    </body>
</html>