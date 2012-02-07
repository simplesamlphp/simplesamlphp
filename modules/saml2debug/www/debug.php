<?php

$config = SimpleSAML_Configuration::getInstance();


function getValue($raw) {
	
	$val = $raw;
	
	$url = parse_url($raw, PHP_URL_QUERY);
	if (!empty($url)) $val = $url;
	
	$arr = array();
	$query = parse_str($val, $arr);

	#echo('<pre>');print_r($arr);
	
	if (array_key_exists('SAMLResponse', $arr)) return $arr['SAMLResponse'];
	if (array_key_exists('SAMLRequest', $arr)) return $arr['SAMLRequest'];
	if (array_key_exists('LogoutRequest', $arr)) return $arr['LogoutRequest'];
	if (array_key_exists('LogoutResponse', $arr)) return $arr['LogoutResponse'];

	return rawurldecode(stripslashes($val));
}

function decode($raw) {
	$message = getValue($raw);
	#echo 'using value: ' . $message; exit;
	
	$base64decoded = base64_decode($message);
	$gzinflated = gzinflate($base64decoded);
	if ($gzinflated != FALSE) {
		$base64decoded = $gzinflated;
	}
	$decoded = htmlspecialchars($base64decoded);
	return $decoded;
}

function encode($message) {
	if (!array_key_exists('binding', $_REQUEST)) throw new Exception('missing binding');
	if ($_REQUEST['binding'] === 'redirect') {
		return urlencode(base64_encode(gzdeflate(stripslashes($message))));
	} else {
		return base64_encode(stripslashes($message));
	}
}


$decoded = '';
$encoded = 'fZJNT%2BMwEIbvSPwHy%2Fd8tMvHympSdUGISuwS0cCBm%2BtMUwfbk%2FU4zfLvSVMq2Euv45n3fd7xzOb%2FrGE78KTRZXwSp5yBU1hpV2f8ubyLfvJ5fn42I2lNKxZd2Lon%2BNsBBTZMOhLjQ8Y77wRK0iSctEAiKLFa%2FH4Q0zgVrceACg1ny9uMy7rCdaM2%2Bs0BWrtppK2UAdeoVjW2ruq1bevGImcvR6zpHmtJ1MHSUZAuDKU0vY7Si2h6VU5%2BiMuJuLx65az4dPql3SHBKaz1oYnEfVkWUfG4KkeBna7A%2Fxm6M14j1gZihZazBRH4MODcoKPOgl%2BB32kFz08PGd%2BG0JJIkr7v46%2BhRCaEpod17DCRivYZCkmkd4N28B3wfNyrGKP5bws9DS6PKDz%2FMpsl36Tyz%2F%2Fax1jeFmi0emcLY7C%2F8SDD0Z7dobcynHbbV3QVbcZW0TlqQemNhoqzJD%2B4%2Fn8Yw7l8AA%3D%3D';

$activeTab = 0;

if (array_key_exists('encoded', $_REQUEST)) {
	$decoded = decode($_REQUEST['encoded']); 
	$activeTab = 1;
}
if (array_key_exists('decoded', $_REQUEST)) {
	$encoded = encode($_REQUEST['decoded']);
}

$t = new SimpleSAML_XHTML_Template($config, 'saml2debug:debug.tpl.php');
$t->data['encoded'] = $encoded;
$t->data['decoded'] = $decoded;
$t->data['activeTab'] = $activeTab;
$t->show();

?>