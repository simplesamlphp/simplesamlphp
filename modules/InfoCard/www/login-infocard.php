<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 1-DEC-08
* DESCRIPTION:
*  'login-infocard' module.
*  Allows an user to authenticate to the system with an Information Card.
*  Infocard's claims are extracted passed as attributes.
*/


/* Load the configuration. */
$config = SimpleSAML_Configuration::getInstance();
$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');

$server_key = $autoconfig->getValue('server_key');
$server_crt = $autoconfig->getValue('server_crt');
$IClogo = $autoconfig->getValue('IClogo');
$Infocard =   $autoconfig->getValue('InfoCard');
$cardGenerator =   $autoconfig->getValue('CardGenerator');


/* Load the session of the current user. */
$session = SimpleSAML_Session::getInstance();
if($session == NULL) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');
}


if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
}
$authStateId = $_REQUEST['AuthState'];


if(array_key_exists('xmlToken', $_POST) && ($_POST['xmlToken']!=NULL)  ) {
	$error = sspmod_InfoCard_Auth_Source_ICAuth::handleLogin($authStateId, $_POST['xmlToken']);
}else {
	$error = NULL;
}

//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'InfoCard:login-infocard.php', 'InfoCard:logininfocard'); //(configuracion, template, diccionario)
$t->data['header'] = 'simpleSAMLphp: Infocard login';
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['IClogo'] = $IClogo;
$t->data['InfoCard'] = $Infocard;
$t->data['CardGenerator'] = $cardGenerator;
$t->data['error'] = $error;
$t->show();
exit();
?>
