<?php

/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION:
*  Authentication module.
*  Handles the login information
*  Infocard's claims are extracted passed as attributes.
*/


class sspmod_InfoCard_Auth_Source_ICAuth extends SimpleSAML_Auth_Source {

	//The string used to identify our states.
	const STAGEID = 'sspmod_core_Auth_UserPassBase.state';


	//The key of the AuthId field in the state.
	const AUTHID = 'sspmod_core_Auth_UserPassBase.AuthId';

	
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);
	}
	
	
	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);
		$url = SimpleSAML_Module::getModuleURL('InfoCard/login-infocard.php');
		SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
	}
	

	public static function handleLogin($authStateId, $xmlToken) {
SimpleSAML_Logger::debug('ENTRA en icauth');
		assert('is_string($authStateId)');		

		$config = SimpleSAML_Configuration::getInstance();
		$autoconfig = $config->copyFromBase('logininfocard', 'config-login-infocard.php');
		$idp_key = $autoconfig->getValue('idp_key');
		$idp_pass = $autoconfig->getValue('idp_key_pass', NULL);
		$sts_crt = $autoconfig->getValue('sts_crt');
		$Infocard =   $autoconfig->getValue('InfoCard');

		$infocard = new sspmod_InfoCard_RP_InfoCard();
		$infocard->addIDPKey($idp_key, $idp_pass);
		$infocard->addSTSCertificate($sts_crt);	
		if (!$xmlToken)     
			SimpleSAML_Logger::debug("XMLtoken: ".$xmlToken);
    else
    	SimpleSAML_Logger::debug("NOXMLtoken: ".$xmlToken);
		$claims = $infocard->process($xmlToken);
 		if($claims->isValid()) {
//		if(false) {
			$attributes = array();
			foreach ($Infocard['requiredClaims'] as $claim => $data){
				$attributes[$claim] = array($claims->$claim);
			}
			foreach ($Infocard['optionalClaims'] as $claim => $data){
				$attributes[$claim] = array($claims->$claim);
			}	
			/* Retrieve the authentication state. */
			$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);
			/* Find authentication source. */
			assert('array_key_exists(self::AUTHID, $state)');
			$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
			if ($source === NULL) {
				throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
			}			
			$state['Attributes'] = $attributes;	
SimpleSAML_Logger::debug('VALIDA');
			unset($infocard);
			unset($claims);
			SimpleSAML_Auth_Source::completeAuth($state);
		} else {
SimpleSAML_Logger::debug('NO VALIDA ERROR:'.$claims->getErrorMsg());
			unset($infocard);
			unset($claims);
			return 'wrong_IC';
		}
	}

}

?>