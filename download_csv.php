<?php
header('Content-type: application/csv');
header('Content-Disposition: attachment; filename=Participants.csv');
header('Pragma: no-cache');
readfile('Participants.csv');
?>