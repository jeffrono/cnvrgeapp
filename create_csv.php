<?php
session_start();
require_once('dbFunctions.php');
$link = db_connect();

$event_id = $_GET['id'];

/////////////////
// create the CSV file
/////////////////

// get list of participants
$query="select * from user where event_id = $event_id order by user.fname asc;";
$result=mysqli_query($link,$query);

// delete file and set up handle
$myFile = "Participants.csv";
unlink($myFile);
$fh = fopen($myFile, 'a') or die("can't open file");

// create header
$csv_array = array('Name', 'Bio', 'Email', '# People Met', 'Round #', 'Person Met', 'Location');
fputcsv($fh, $csv_array);

// loop through all participants
while($row = mysqli_fetch_array($result)) {
	$a_fname = $row['fname'];
	$a_bio = $row['bio'];
	$a_email = $row['email'];
	
	// for this participant, get all of the ppl he/she met
	$query = "select user_met.meeting_order, user.fname, user.bio, locations.name
					from user_met
					join locations on user_met.location_id = locations.id
					join user on user_met.met_user_id = user.id
					where user_id = ". $row['id'];
	$resulta =mysqli_query($link,$query);
	
	// how many ppl did this participant meet
	$count = mysqli_num_rows($resulta);
	
	// insert participant into csv
	$csv_array = array($a_fname, $a_bio, $a_email, $count);
	fputcsv($fh, $csv_array);

	while($rowa = mysqli_fetch_array($resulta)) {
		$csv_array = array('','','','', $rowa['meeting_order'], $rowa['fname'], $rowa['name']);
		fputcsv($fh, $csv_array);
	}
} //while

fclose($fh);

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$extra = 'download_csv.php';
header("Location: http://$host$uri/$extra");
?>