<?php


#sleep(2);

$feed = new sspmod_discojuice_Feed();
$datajson = $feed->read();	

$data = json_decode($datajson, TRUE);


header('Content-Type: text/plain; charset=utf-8');

# print_r($data); exit;

foreach($data AS $key => $e) {
	
	if ($e['country'] == 'SE') {
		print_r($e);
	}
	
	if (empty($e['geo'])) {
		#print_r($e);
		echo "Entity [" . $e['entityid'] . "] is missing geo-coordinates\n";
	}
	
	
}





