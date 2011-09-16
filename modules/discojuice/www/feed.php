<?php

#sleep(2);

$feed = new sspmod_discojuice_Feed();

if (!empty($_REQUEST['refresh'])) {
	$feed->store();
	$data = $feed->read();	
} else {
	$data = $feed->read();	
}






if (!empty($_REQUEST['debug'])) {
	

	header('Content-Type: text/plain; charset=utf-8');
	print_r(json_decode($data, 'utf-8'));
	exit;
}

header('Content-Type: application/json; charset=utf-8');
	
if(isset($_REQUEST['callback'])) {
	echo $_REQUEST['callback'] . '(' . $data . ');';
} else {
	echo $data;
}







