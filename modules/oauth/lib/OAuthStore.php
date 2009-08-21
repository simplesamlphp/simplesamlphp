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

	private $path;

    function __construct($path = '/tmp/oauth/') {
			
		if (!file_exists($path)) {
			mkdir($path);
		}
		if (!is_dir($path)) 
			throw new Exception('OAuth Storage Path [' . $path . '] is not a valid directory');
			
		$this->path = $path;
    }

	private function filename($key) {
		return $this->path . sha1($key) . '.oauthstore';
	}
	
	private function exists($key) {
		return file_exists($this->filename($key));
	}
	
	private function get($key) {
		error_log( 'Getting :' . $key . ' : ' . ($this->exists($key) ? 'FOUND' : 'NOTFOUND'));
		if (!$this->exists($key)) return NULL;
		return unserialize(file_get_contents($this->filename($key)));
	}
	
	private function set($key, $value) {
		error_log('Setting :' . $key . ' : ' . ($this->exists($key) ? 'FOUND' : 'NOTFOUND'));
		file_put_contents($this->filename($key), serialize($value));
	}

	private function remove($key) {
		unlink($this->filename($key));
	}
	
	public function authorize($requestToken, $data) {
		$this->set('validated.request.' . $requestToken, $data);
	}
	
	public function isAuthorized($requestToken) {
		return $this->exists('validated.request.' . $requestToken);
	}
	
	public function getAuthorizedData($token) {
		return $this->get('validated.request.' . $token);
	}
	
	public function moveAuthorizedData($requestToken, $accessToken) {
		$this->authorize($accessToken, $this->getAuthorizedData($requestToken));
		$this->remove('validated.request.' . $requestToken);
	}
	
	private function tokenTag($tokenType = 'default', $token) {
		return 'token.' . $token . '.' . $tokenType;
	}

    function lookup_consumer($consumer_key) {
        if ($consumer_key == 'key') return new OAuthConsumer("key", "secret", NULL);
        return NULL;
    }

    function lookup_token($consumer, $tokenType = 'default', $token) {
		return $this->get($this->tokenTag($tokenType, $token));
    }

    function lookup_nonce($consumer, $token, $nonce, $timestamp) {
		$nonceTag = 'nonce.' . $consumer->key . '.' . $nonce;
		if ($this->exists($nonceTag))
			return TRUE;
		
		$this->set($nonceTag, $timestamp);
		return FALSE;
    }

    function new_request_token($consumer) {
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		$this->set($this->tokenTag('request', $token->key), $token);
        return $token;
    }

    function new_access_token($token, $consumer) {
		$token = new OAuthToken(SimpleSAML_Utilities::generateID(), SimpleSAML_Utilities::generateID());
		$this->set($this->tokenTag('access', $token->key), $token);
        return $token;
    }

}
