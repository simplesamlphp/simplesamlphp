<?php

require_once('../../www/_include.php');

require_once('SimpleSAML/Utilities.php');
require_once('SimpleSAML/Session.php');
require_once('SimpleSAML/Logger.php');
require_once('SimpleSAML/Metadata/MetaDataStorageHandler.php');
require_once('SimpleSAML/XHTML/Template.php');

$config = SimpleSAML_Configuration::getInstance();
$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
$session = SimpleSAML_Session::getInstance();
$logger = new SimpleSAML_Logger();

$logger->log(LOG_INFO, $session->getTrackID(), 'AUTH', 'radius', 'EVENT', 'Access', 'Accessing auth endpoint login');

$error = null;
$attributes = array();
	
if (isset($_POST['username'])) {


	try {
	
		$radius = radius_auth_open();
		// ( resource $radius_handle, string $hostname, int $port, string $secret, int $timeout, int $max_tries )
		if (! radius_add_server($radius, $config->getValue('auth.radius.hostname'), $config->getValue('auth.radius.port'), 
				$config->getValue('auth.radius.secret'), 5, 3)) {
				
			$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'radius', 'radius_strerror', radius_strerror($radius), 
				'Problem occured when connecting to Radius server');
			throw new Exception('Problem occured when connecting to Radius server: ' . radius_strerror($radius));
		}
	
		if (! radius_create_request($radius,RADIUS_ACCESS_REQUEST)) {
			$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'radius', 'radius_strerror', radius_strerror($radius), 
				'Problem occured when creating the Radius request');
			throw new Exception('Problem occured when creating the Radius request: ' . radius_strerror($radius));
		}
	
		radius_put_attr($radius,RADIUS_USER_NAME,$_POST['username']);
		radius_put_attr($radius,RADIUS_USER_PASSWORD, $_POST['password']);
	
		switch (radius_send_request($radius))
		{
			case RADIUS_ACCESS_ACCEPT:
				
				// GOOD Login :)
				
				$attributes = array( $config->getValue('auth.radius.URNForUsername') => array($_POST['username']));
				
				// get AAI attribute sets. Contributed by Stefan Winter, (c) RESTENA
				while ($resa = radius_get_attr($radius)) {
					
					if (! is_array($resa)) {
						printf ("Error getting attribute: %s\n",  radius_strerror($res));
						exit;
					}
					
					if ($resa['attr'] == RADIUS_VENDOR_SPECIFIC) {
						$resv = radius_get_vendor_attr($resa['data']);
						if (is_array($resv)) {
							$vendor = $resv['vendor'];
							$attrv = $resv['attr'];
							$datav = $resv['data'];
							printf("Got Vendor Attr:%d %d Bytes %s\n", $attrv, strlen($datav), bin2hex($datav));
							if ($vendor == $config->getValue('auth.radius.vendor') && $attrv == $config->getValue('auth.radius.vendor-attr'))
							   $attrib_name  = strtok ($datav,'=');
							   $attrib_value = strtok ('=');
							   $attributes = $attributes + array($attrib_name => array($attrib_value));
						}
					}
				}
				// end of contribution

				//$attributes = array('urn:mace:eduroam.no:username' => array($_POST['username']));
				
				$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'radius', 'OK', $_POST['username'], $_POST['username'] . ' successfully authenticated');
				
				$session->setAuthenticated(true, 'login-radius');
				$session->setAttributes($attributes);

				$returnto = $_REQUEST['RelayState'];
				SimpleSAML_Utilities::redirect($returnto);
				
	
			case RADIUS_ACCESS_REJECT:
			
				$logger->log(LOG_NOTICE, $session->getTrackID(), 'AUTH', 'radius', 'Fail', $_POST['username'], $_POST['username'] . ' failed to authenticate');
				throw new Exception('Radius authentication error: Bad credentials ');
				break;
			case RADIUS_ACCESS_CHALLENGE:
				$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'radius', 'radius_strerror', radius_strerror($radius), 
					'Challenge requested');
				throw new Exception('Radius authentication error: Challenge requested');
				break;
			default:
				$logger->log(LOG_CRIT, $session->getTrackID(), 'AUTH', 'radius', 'radius_strerror', radius_strerror($radius), 
					'General radius error');
				throw new Exception('Error during radius authentication: ' . radius_strerror($radius));
				
		}

	} catch (Exception $e) {
		
		$error = $e->getMessage();
		
	}
}


$t = new SimpleSAML_XHTML_Template($config, 'login.php');

$t->data['header'] = 'simpleSAMLphp: Enter username and password';	
$t->data['relaystate'] = $_REQUEST['RelayState'];
$t->data['error'] = $error;
if (isset($error)) {
	$t->data['username'] = $_POST['username'];
}

$t->show();


?>
