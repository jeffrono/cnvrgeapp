<?php
session_start();
require_once('dbFunctions.php');
$link = db_connect();

$event_id = $_POST['event_id'];
$num_accts = $_POST['num_accts'];

// reset event info
$query="update event set status = 1 where id = $event_id;";
$result=mysqli_query($link,$query);

$query="delete from meeting where event_id = $event_id;";
$result=mysqli_query($link,$query);

$query="delete from user_met where user_id in (select id from user where event_id = $event_id;);";
$result=mysqli_query($link,$query);

$query="delete from sms_log where user_id in (select id from user where event_id = $event_id;);";
$result=mysqli_query($link,$query);

$query="delete from skipped where user_id in (select id from user where event_id = $event_id;);";
$result=mysqli_query($link,$query);

$query="delete from meeting_status where event_id = $event_id;";
$result=mysqli_query($link,$query);

$query="delete from user where event_id = $event_id;";
$result=mysqli_query($link,$query);

$names = array("Zachary Feola", "Elouise Graydon", "Tania Dawe", "Mika Hotz", "Kristyn Garica", "Phuong Roessler", "Tressie Copeland", "Garth Juan", "Emile Juliano", "Bong Mcgarity", "Wilmer Aviles", "Chastity Bridgewater", "Sharie Weatherwax", "Phebe Lauderdale", "Dorotha Seidman", "Rhett Riggan", "Ceola Deloney", "June Nurse", "Jannie Gunia", "Jamel Buenrostro");

$bios = array("Hand Weaver", "All Terrain Vehicle Technician", "Aircraft Instrument Mechanic", "Preschool Director", "Procurement Manager", "Special Education Kindergarten Teacher", "Field Artillery Officer", "Equipment Maintenance Technician", "Animal Pathologist", "Boxing Trainer", "Scout Sniper", "Forestry Laborer", "Bottle Line Worker", "Research Anthropologist", "Long Wall Shear Operator", "Foreign Correspondent", "Naval Aircrewman", "Front-End Loader Operator", "Commercial Art Instructor", "Ambulance Attendant");

// loop through the number of demo accounts to create
for ($i = 0; $i <= $num_accts; $i++) {
	$query = "INSERT INTO user (event_id, fname, bio, twilio, status, email) VALUES ($event_id, '$names[$i]', '$bios[$i]', '+13473388651', 3, 'jeffnovich+$i@gmail.com')";
	$result=mysqli_query($link,$query);
}

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'event.php?id=' . $event_id;
header("Location: http://$host$uri/$extra");
?>