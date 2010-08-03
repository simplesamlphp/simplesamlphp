<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 13-FEB-09
* DESCRIPTION:
*		User flow controller.
*		Displays the template and request a non null xmlToken
*/



/* Load the configuration. */
$config = SimpleSAML_Configuration::getInstance();
$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');

$server_key = $autoconfig->getValue('server_key');
$server_crt = $autoconfig->getValue('server_crt');
$IClogo = $autoconfig->getValue('IClogo');
$Infocard =   $autoconfig->getValue('InfoCard');
$cardGenerator =   $autoconfig->getValue('CardGenerator');
$sts_crt = $autoconfig->getValue('sts_crt');
$help_desk_email_URL = $autoconfig->getValue('help_desk_email_URL');
$contact_info_URL = $autoconfig->getValue('contact_info_URL');


/* Load the session of the current user. */
$session = SimpleSAML_Session::getInstance();


if (!array_key_exists('AuthState', $_REQUEST)) {
SimpleSAML_Logger::debug('NO AUTH STATE');
SimpleSAML_Logger::debug('ERROR: NO AUTH STATE');
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
} else {
	$authStateId = $_REQUEST['AuthState'];
SimpleSAML_Logger::debug('AUTH STATE:  '.$authStateId);
}

if(array_key_exists('xmlToken', $_POST) && ($_POST['xmlToken']!=NULL)  ) {
SimpleSAML_Logger::debug('HAY XML TOKEN');
	$error = sspmod_InfoCard_Auth_Source_ICAuth::handleLogin($authStateId, $_POST['xmlToken']);
}else {
SimpleSAML_Logger::debug('NO HAY XML TOKEN');
	$error = NULL;
}

unset($_POST); //Show the languages bar if reloaded
 
//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'InfoCard:temp-login.php', 'InfoCard:dict-InfoCard'); //(configuracion, template, diccionario)
$t->data['header'] = 'simpleSAMLphp: Infocard login';
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['IClogo'] = $IClogo;
$t->data['InfoCard'] = $Infocard;
$t->data['InfoCard']['issuer'] = $autoconfig->getValue('tokenserviceurl');//sspmod_InfoCard_Utils::getIssuer($sts_crt);
$t->data['CardGenerator'] = $cardGenerator;
$t->data['help_desk_email_URL'] = $help_desk_email_URL;
$t->data['contact_info_URL'] = $contact_info_URL;
$t->data['error'] = $error;
$t->show();
exit();
?>
