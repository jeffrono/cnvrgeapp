<?php
session_start();
require_once('dbFunctions.php');
$link = db_connect();

$event_id = $_POST['event_id'];

// reset location info
$query="delete from locations where event_id = $event_id;";
$result=mysqli_query($link,$query);

for ($i = 1; $i <= 100; $i++) {
	$query="insert into locations (number, name, event_id) value ($i, 'Location $i', $event_id);";
	$result=mysqli_query($link,$query);
}

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'locations.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>