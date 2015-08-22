<?php
session_start();
require_once('dbFunctions.php');
db_connect();

$event_id = $_POST['event_id'];
$num_accts = $_POST['num_accts'];

// reset event info
$query="update event set status = 1 where id = $event_id;";
$result=mysql_query($query);

$query="delete from meeting where event_id = $event_id;";
$result=mysql_query($query);

$query="delete from user_met where user_id in (select id from user where event_id = $event_id;);";
$result=mysql_query($query);

$query="delete from sms_log where user_id in (select id from user where event_id = $event_id;);";
$result=mysql_query($query);

$query="delete from skipped where user_id in (select id from user where event_id = $event_id;);";
$result=mysql_query($query);

$query="delete from meeting_status where event_id = $event_id;";
$result=mysql_query($query);

$query="delete from user where event_id = $event_id;";
$result=mysql_query($query);

// loop through the number of demo accounts to create
for ($i = 100; $i <= $num_accts +99; $i++) {
	$query = "INSERT INTO user (event_id, fname, bio, twilio, status, email) VALUES ($event_id, 'person$i', 'bio_$i', '+13473388651', 3, 'jeffnovich+$i@gmail.com')";
	$result=mysql_query($query);
}

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>