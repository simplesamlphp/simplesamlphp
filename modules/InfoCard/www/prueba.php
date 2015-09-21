<?php

$DB_host = 'localhost';
$DB_port = '5432';
$DB_dbname = 'db1';
$DB_user = 'user1';
$DB_password = 'pass1';


$username = 'enrique';
$card_id = '1234567';
$dbconn = pg_connect("host=$DB_host  port=$DB_port  dbname=$DB_dbname user=$DB_user  password=$DB_password ");
$result = pg_fetch_all(pg_query_params($dbconn, 'SELECT * FROM connected_users WHERE name = $1', array("$username")));
if ($result[0]){
	pg_update($dbconn, 'connected_users', array('card_id'=>$card_id), array('name'=>$username));
	print_r ($result);
} else {
	echo 'error';
}


// echo pg_last_error($dbconn);
// if (!$result) {
// 	echo 'FALLO';
// } else {
// 	print "result: $result </br>";
// 	$row=pg_fetch_all($result);
// 	print "ROW: $row </br>";
// // 	print_r ($result);
// 	print_r ($row);
// }

pg_close($dbconn);


// $handle = fopen(SimpleSAML_Utilities::getTempDir() . '/prueba2.txt','w');
// fwrite($handle, 'prueba');
// fclose ($handle);


// 
// phpinfo();
// 
// 
// $config = SimpleSAML_Configuration::getInstance();
// $autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
// 
// $certificates =   $autoconfig->getValue('certificates');
// 
// 
// 
// 
// 
// 
// 
// function takePublicKey($cert) {
// 	$pkey = openssl_get_publickey(file_get_contents($cert));
// 	$keyData = openssl_pkey_get_details($pkey);
// 	$key = $keyData['key'];
// 	$key = str_replace('-----BEGIN PUBLIC KEY-----', '', $key);
// 	$key = str_replace('-----END PUBLIC KEY-----', "", $key);
// 	$key = str_replace("\n", "", $key);
// 	return $key;
// }
// 
// /*CASE 1 AND 2
// * -Has Organization
// * -And chains to a trusted root CA
// */
// function calculate_RP_PPID_Seed_2_2007 ($certs) {
// 	$check_cert = openssl_x509_read(file_get_contents($certs[0]));
// 	$array = openssl_x509_parse($check_cert);
// 	openssl_x509_free($check_cert);
// 	$OrgIdString = ('|O="'.$array['subject']['O'].'"|L="'.$array['subject']['L'].'"|S="'.$array['subject']['ST'].'"|C="'.$array['subject']['C'].'"|');
// 	print_r ($array);
// 	print '<br>';
// 	
// 	$numcerts = sizeof($certs);
// 	for($i=1;$i<$numcerts;$i++){
// 		$check_cert = openssl_x509_read(file_get_contents($certs[$i]));
// 		$array = openssl_x509_parse($check_cert);
// 		openssl_x509_free($check_cert);
// 		$tmpstring = '|ChainElement="CN='.$array['subject']['CN'].', OU='.$array['subject']['OU'].', O='.$array['subject']['O'].', L='.$array['subject']['L'].', S='.$array['subject']['ST'].', C='.$array['subject']['C'].'"';
// 		$OrgIdString = $tmpstring.$OrgIdString;
// 	}
// 	
// 	print '<br>CALCULADA'.iconv("UTF-8", "ISO-8859-1", $OrgIdString).'<br>';
// 	print '<br>VERDADERA = |ChainElement="CN=Autoridad de Certificación de pruebas, OU=aut, O=UAH, L=Alcalá de Henares, S=Madrid, C=ES"|O="UAH"|L="Alcalá de Henares"|S="Madrid"|C="ES"|<br>';
// 	$OrgIdBytes = iconv("UTF-8", "UTF-16LE", $OrgIdString);
// 	$RPPPIDSeed = hash('sha256', $OrgIdBytes,TRUE);
// 	return $RPPPIDSeed;
// }
// 
// 
// /*CASE 1 AND 2
// * -Has Organization
// * -And chains to a trusted root CA
// */
// function calculate_RP_PPID_Seed_2008 ($rp_cert) {
// 	$check_cert = openssl_x509_read(file_get_contents($rp_cert));
// 	$array = openssl_x509_parse($check_cert);
// 	openssl_x509_free($check_cert);
// 	$OrgIdString = ('|O="'.$array[subject][O].'"|L="'.$array[subject][L].'"|S="'.$array[subject][ST].'"|C="'.$array[subject][C].'"|');
// 	print_r ($array);
// 	$OrgIdBytes = iconv("ISO-8859-1", "UTF-16LE", $OrgIdString);
// 	$RPPPIDSeed = hash('sha256', $OrgIdBytes,TRUE);
// 	return $RPPPIDSeed;
// }
// 
// 
// /*CASE 3
// * -Has empty or NO Organization value
// * -And has an empty or no Common Name (CN)
// * -Or does not chain to a trusted root CA
// */
// function calculate_RP_PPID_Seed_3 ($rp_cert) {
//   $pubKey = base64_decode(takePublicKey($rp_cert));
//   $RPPPIDSeed = hash('sha256',$pubKey );
// 	return $RPPPIDSeed;
// }
// 
// 
// /*CASE 4
// * -Has empty or NO Organization value
// * -And has a non-empty Common Name (CN) value
// * -And chains to a trusted root CA
// */
// function calculate_RP_PPID_Seed_4 ($rp_cert) {
// 	$check_cert = openssl_x509_read(file_get_contents($rp_cert));
// 	$array = openssl_x509_parse($check_cert);
// 	openssl_x509_free($check_cert);
// 	$CnIdString = '|CN="'.$array['subject']['CN'].'"|';
// 	print $CnIdString;
// 	$CnIdBytes = iconv("ISO-8859-1", "UTF-16LE", $CnIdString);
// 	$RPPPIDSeed = hash('sha256', $CnIdBytes, TRUE);
// 	return $RPPPIDSeed;
// }
// 
// 
// function calculate_PPID($cardid, $rp_cert) {
// 	$CardIdBytes = iconv("ISO-8859-1", "UTF-16LE", $cardid);
// 	$CanonicalCardId = hash('sha256', $CardIdBytes,TRUE);
// 	$RPPPIDSeed = calculate_RP_PPID_Seed_2_2007($rp_cert);
// 	print "<br> rp seed ".base64_encode($RPPPIDSeed)."<br>";
// 	print "<br> canonical cardid ".base64_encode($CanonicalCardId)."<br>";
// 	$PPID = hash('sha256', $RPPPIDSeed.$CanonicalCardId,TRUE);
// 	return $PPID;
// }
//  
// 
// function get_OrgIdString($cert){
// }
//  
//  //PPID: nQIBQqEnme/4SytR1GMxMJUdzU7NdzyYnaHas8fzekc=
//  
//   //Cardid: urn:uuid:bbe3ecf5-900b-d249-b9a7-e7c261fdf189, ... VRL-QVCK-GHF
//  	//PPID: +8mxdRW+9Trqxd3CwQZUKGlYZBjdgmHpgA7/PsQM5yA=
//  print base64_encode(calculate_PPID('urn:uuid:bbe3ecf5-900b-d249-b9a7-e7c261fdf189', $certificates));
// 
// // 	print base64_encode(pack('H*','0939625DA3A93E44F52D72AE4246EE54DE265D84'));
//  	
?>
