<?php

$json = file_get_contents('php://input');
$obj = json_decode($json);

$transcript = $obj->{'result'}->{'transcription'};


error_log($transcript);

# URL: cnvrgeapp.herokuapp.com/tropo.php

#get the transcript post from tropo


# post it as a document note to HMH


#ngrok.io
#postman


?>
