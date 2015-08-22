<?php
require_once('dbFunctions.php');
db_connect();

$user_id = $_POST['user_id'];
$event_id = $_POST['event_id'];

if($_POST['activate']) {
	$query = "update user set status=3 where id = $user_id;";
}
elseif($_POST['deleteuser']) {
	$query = "delete from user where id = $user_id;";
}
elseif($_POST['deactivate']) {
	$query = "update user set status=4 where id = $user_id;";
}

mysql_query($query);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'participants.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");

?>