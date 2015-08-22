<?php
require_once('dbFunctions.php');
$link = db_connect();

$event_id = $_POST['event_id'];

while (list($key,$value) = each($_POST)){
	$query="update event set $key = '$value' where id = $event_id;";
	$result=mysqli_query($link,$query);
	
	//echo "$key -> $value<br>";
	
} // while

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'settings.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");

?>