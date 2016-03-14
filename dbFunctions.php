<?php

// get twilio vars

$AccountSid = getenv("TWILIO_ACCOUNT_SID");
$AuthToken = getenv("TWILIO_AUTH_TOKEN");

//test

// *** DATABASE FUNCTIONS ***
function db_connect() {
	$url = parse_url(getenv("CLEARDB_DATABASE_URL"));	
	$server = $url["host"];
	$username = $url["user"];
	$password = $url["pass"];
	$db = substr($url["path"], 1);
	
	$link= new mysqli($server, $username, $password, $db);
	#if ($link->connect_errno) echo "Error - Failed to connect to MySQL: " . $link->connect_error;
	
	return $link;
}

?>