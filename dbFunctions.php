<?php

// get twilio vars

$AccountSid = parse_url(getenv("TWILIO_ACCOUNT_SID"));
$AuthToken = parse_url(getenv("TWILIO_AUTH_TOKEN"));

//test

// *** DATABASE FUNCTIONS ***
function db_connect() {
	$url = parse_url(getenv("CLEARDB_DATABASE_URL"));	
	$server = $url["host"];
	$username = $url["user"];
	$password = $url["user"];
	$db = substr($url["path"], 1);
	
	$link= new mysqli($server, $username, $password, $db);
	if (!$link) {
		die('Could not connect: ' . mysql_error());
	}
	else {
		mysql_select_db($database, $link);
		return $link;
	}
}

?>