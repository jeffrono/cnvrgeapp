<?php

session_start();
require_once('dbFunctions.php');
$link = db_connect();

// get event info
$event_id = $_GET['id'];
$event_id = 3609;

$query= "select * from event where id = $event_id;";
$result=mysqli_query($link,$query);
$row = mysqli_fetch_array($result);
$eventbrite_token = $row['eventbrite_token'];	// eventbrite token
$eventbrite_id = $row['eventbrite_id'];	// eventbrite id
$eventbrite_key = $row['eventbrite_key'];	// eventbrite id

//https://www.eventbrite.com/json/event_list_attendees?access_token=3TJHB7VNXDAETHMSMRPQ&id=4900299929
$url    = 'https://www.eventbrite.com/json/event_list_attendees?' . 
						'access_token='. $eventbrite_token . 
						'&id='. $eventbrite_id . 
						'&app_key='. $eventbrite_key;

$response = file_get_contents($url, true);

echo $url . '<p>' . $response . '<p>';

  // connect to database

$json = json_decode($response, true);
foreach ($json['attendees']['attendee'] as $m) {
	$name = $m['first_name'] . ' ' . $m['last_name'];
	$email = $m['email'];
	
	echo "$name<br>$email<br><br>";
		
}
?>