<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

/**
 * OAuth Store
 *
 * @author Andreas Åkre Solberg, <andreas.solberg@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_oauth_OAuthStore extends OAuthDataStore {

	private $store;
	private $config;

    function __construct() {
		$this->store = new sspmod_core_Storage_SQLPermanentStorage('oauth');			
		$this->config = SimpleSAML_Configuration::getOptionalConfig('module_oauth.php');
    }
	
	public function authorize($requestToken, $data) {
		# set($type, $key1, $key2, $value, $duration = NULL) {
		$this->store->set('authorized', $requestToken, '', $data, $this->config->getValue('requestTokenDuration', 60*30) );
	}
	
	public function isAuthorized($requestToken) {
		SimpleSAML_Logger::info('OAuth isAuthorized(' . $requestToken . ')');
		return $this->store->exists('authorized', $requestToken, '');
	}
	
	public function getAuthorizedData($token) {
		SimpleSAML_Logger::info('OAuth getAuthorizedData(' . $token . ')');
		$data = $this->store->get('authorized', $token, '');
		return $data['value'];
	}
	
	public function moveAuthorizedData($requestToken, $accessToken) {
		SimpleSAML_Logger::info('OAuth moveAuthorizedData(' . $requestToken . ', ' . $accessToken . ')');
		$this->authorize($accessToken, $this->getAuthorizedData($requestToken));
		$this->store->remove('authorized', $requestToken, '');
	}
	
    public function lookup_consumer($consumer_key) {
		
		SimpleSAML_Logger::info('OAuth lookup_consumer(' . $consumer_key . ')');
		if (! $this->store->exists('consumers', $consumer_key, ''))  return NULL;
		$consumer = $this->store->get('consumers', $consumer_key, '');
		// SimpleSAML_Logger::info('OAuth consumer dump(' . var_export($consumer, TRUE) . ')');
		return new OAuthConsumer($consumer['value']['key'], $consumer['value']['secret'], NULL);
    }

    function lookup_token($consumer, $tokenType = 'default', $token) {
		SimpleSAML_Logger::info('OAuth lookup_token(' . $consumer->key . ', ' . $tokenType. ',' . $token . ')');
		$data = $this->store->get($tokenType, $token, $consumer->key);
		if ($data == NULL) throw new Exception('Could not find token');
		return $data['value'];
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		SimpleSAML_Logger::info('OAuth lookup_nonce(' . $consumer . ', ' . $token. ',' . $nonce . ')');
		if ($this->store->exists('nonce', $nonce, $consumer->key))  return TRUE;
		$this->store->set('nonce', $nonce, $consumer->key, TRUE, $this->config->getValue('nonceCache', 60*60*24*14));
		return FALSE;
    }

    function new_request_token($consumer) {
		SimpleSAML_Logger::info('OAuth new_request_token(' . $consumer . ')');
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		$this->store->set('request', $token->key, $consumer->key, $token, $this->config->getValue('requestTokenDuration', 60*30) );
        return $token;
    }

    function new_access_token($requestToken, $consumer) {
		SimpleSAML_Logger::info('OAuth new_access_token(' . $requestToken . ',' . $consumer . ')');
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		// SimpleSAML_Logger::info('OAuth new_access_token(' . $requestToken . ',' . $consumer . ',' . $token . ')');
		$this->store->set('access', $token->key, $consumer->key, $token, $this->config->getValue('accessTokenDuration', 60*60*24) );
        return $token;
    }

}
