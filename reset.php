<?php
session_start();
require_once('dbFunctions.php');
db_connect();

$event_id = $_POST['event_id'];

// reset event info
$query="update event set status = 1;";
$result=mysql_query($query);

$query="delete from meeting;";
$result=mysql_query($query);

$query="delete from user_met;";
$result=mysql_query($query);

$query="delete from sms_log;";
$result=mysql_query($query);

$query="delete from skipped;";
$result=mysql_query($query);

$query="delete from meeting_status;";
$result=mysql_query($query);

$query="delete from user;";
$result=mysql_query($query);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>