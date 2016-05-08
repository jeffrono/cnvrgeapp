<?php
error_log("hello, this is a test!");

$json = file_get_contents('php://input');

error_log($json);

$obj = json_decode($json);

error_log($obj);

# URL: cnvrge.herokuapp.com/tropo.php

#get the transcript post from tropo


# post it as a document note to HMH



?>
