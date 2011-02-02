<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');


class sspmod_oauth_OAuthSignatureMethodRSASHA1 extends OAuthSignatureMethod_RSA_SHA1 {
	protected $_store;
	
	public function __construct() {
		$this->_store = new sspmod_core_Storage_SQLPermanentStorage('oauth');
	}
	
	/**
	 * Returns the secret that was registered with a Consumer<br/>
	 * In case of RSA_SHA1, the consumer secret is initialized with the certificate containing the public key
	 * @param $request OAuthRequest instance of the request to be handled; must contain oauth_consumer_key parameter
	 * @return string value containing the public key that was registered with the consumer identified by 
	 * 			consumer_key from the request 
	 */
	protected function fetch_public_cert(&$request) {
		$consumer_key = @$request->get_parameter('oauth_consumer_key');
		
		$oConsumer = $this->_OAuthStore->lookup_consumer($consumer_key);
		
		if (! $oConsumer) {
			return NULL;
		}
		
		return $oConsumer->secret;
	}
}
?>