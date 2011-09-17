<?php



try {
	
	$djconfig = SimpleSAML_Configuration::getOptionalConfig('discojuicecentral.php');
	$options = $djconfig->getConfigItem('discojuice.options');
	$enableCountryAPI = $options->getValue('country', FALSE);
	
	if ($enableCountryAPI !== TRUE) {
		throw new Exception('Use of the DiscoJuice Country API is disabled.');
	}

	$result = array('status' => 'ok');

	$c = new sspmod_discojuice_Country();
	$region = $c->getRegion();
	
	if (preg_match('|^(.*?)/(.*?)$|', $region, $matches)) {
		if (!empty($matches[1])) $result['country'] = $matches[1];
		if (!empty($matches[2])) $result['region'] = $matches[2];
	}
	
	$geo = $c->getGeo();

	if (preg_match('|^(.*?),(.*?)$|', $geo, $matches)) {
		$result['geo'] = array('lat' => (float) $matches[1], 'lon' => (float)$matches[2]);
	}



	if(preg_match('/^[0-9A-Za-z_\-]+$/', $_REQUEST['callback'], $matches)) {
		header('Content-type: application/javascript; utf-8');
		echo $_REQUEST['callback'] . '(' . json_encode($result) . ');';
	} else {
		header('Content-type: application/json; utf-8');
		echo json_encode($result);
	}

	
} catch(Exception $e) {
	
	echo json_encode(array('status' => 'error', 'error' => $e->getMessage()));
	
}

