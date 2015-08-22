<?php
require_once('dbFunctions.php');
$link = db_connect();

$event_id = $_POST['event_id'];

$query="update event set $key = '$value' where id = $event_id;";

while (list($key,$value) = each($_POST)){
	if($key == 'num_locs') {
		$query="update event set $key = '$value' where id = $event_id;";
	}
	else {
		$query="update locations set name = '$value' where number = $key and event_id = $event_id;";
	}
	
	$result=mysqli_query($link,$query);
} // while
		
$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'locations.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");

?>