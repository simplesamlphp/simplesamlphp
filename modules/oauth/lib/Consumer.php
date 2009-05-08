<?php

require_once(dirname(dirname(__FILE__)) . '/libextinc/OAuth.php');

/**
 * OAuth Consumer
 *
 * @author Andreas Åkre Solberg, <andreas.solberg@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_oauth_Consumer {
	
	private $consumer;
	private $signer;
	
	public function __construct($key, $secret) {
		$this->consumer = new OAuthConsumer($key, $secret, NULL);
		$this->signer = new OAuthSignatureMethod_HMAC_SHA1();
	}
	
	// Used only to load the libextinc library early.
	public static function dummy() {}
	
	public function getRequestToken($url) {
		$req_req = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "GET", $url, NULL);
		$req_req->sign_request($this->signer, $this->consumer, NULL);

		$response_req = file_get_contents($req_req->to_url());
		if ($response_req === FALSE) {
			throw new Exception('Error contacting request_token endpoint on the OAuth Provider');
		}

		parse_str($response_req, $responseParsed);
		
		if(array_key_exists('error', $responseParsed))
			throw new Exception('Error getting request token: ') . $responseParsed['error'];
		
		$requestToken = $responseParsed['oauth_token'];
		$requestTokenSecret = $responseParsed['oauth_token_secret'];
		
		return new OAuthToken($requestToken, $requestTokenSecret);
	}
	
	public function getAuthorizeRequest($url, $requestToken, $redirect = TRUE, $callback = NULL) {
		$authorizeURL = $url . '?oauth_token=' . $requestToken->key;
		if ($callback) {
			$authorizeURL .= '&oauth_callback=' . urlencode($callback);
		}
		if ($redirect) {
			SimpleSAML_Utilities::redirect($authorizeURL);
			exit;
		}	
		return $authorizeURL;
	}
	
	public function getAccessToken($url, $requestToken) {

		$acc_req = OAuthRequest::from_consumer_and_token($this->consumer, $requestToken, "GET", $url, NULL);
		$acc_req->sign_request($this->signer, $this->consumer, $requestToken);

		$response_acc = file_get_contents($acc_req->to_url());
		if ($response_acc === FALSE) {
			throw new Exception('Error contacting request_token endpoint on the OAuth Provider');
		}

		
		parse_str($response_acc, $accessResponseParsed);
		
		if(array_key_exists('error', $accessResponseParsed))
			throw new Exception('Error getting request token: ') . $accessResponseParsed['error'];
		
		$accessToken = $accessResponseParsed['oauth_token'];
		$accessTokenSecret = $accessResponseParsed['oauth_token_secret'];

		return new OAuthToken($accessToken, $accessTokenSecret);
	}
	
	public function getUserInfo($url, $accessToken) {
		
		$data_req = OAuthRequest::from_consumer_and_token($this->consumer, $accessToken, "GET", $url, NULL);
		$data_req->sign_request($this->signer, $this->consumer, $accessToken);

		$data = file_get_contents($data_req->to_url());
		// print_r($data);

		$dataDecoded = json_decode($data, TRUE);
		return $dataDecoded;
	}
	
}

