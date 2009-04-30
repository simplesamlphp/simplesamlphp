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
	
	public function getRequestToken($url) {
		$req_req = OAuthRequest::from_consumer_and_token($this->consumer, NULL, "GET", $url, NULL);
		$req_req->sign_request($this->signer, $this->consumer, NULL);

		echo "Requesting a request token\n";
		// echo 'go to url: ' . $req_req->to_url() . "\n"; exit;
		$response_req = file_get_contents($req_req->to_url());

		parse_str($response_req, $responseParsed);
		
		if(array_key_exists('error', $responseParsed))
			throw new Exception('Error getting request token: ') . $responseParsed['error'];
		
		$requestToken = $responseParsed['oauth_token'];
		$requestTokenSecret = $responseParsed['oauth_token_secret'];
		
		return new OAuthToken($requestToken, $requestTokenSecret);
	}
	
	public function getAuthorizeRequest($url, $requestToken) {
		$authorizeURL = $url . '?oauth_token=' . $requestToken->key;

		echo "Please go to this URL to authorize access: " . $authorizeURL . "\n";
		system("open " . $authorizeURL);

		echo "Waiting 15 seconds for you to authenticate. Usually you should let the user enter return or click a continue button.\n";

		sleep(15);
	}
	
	public function getAccessToken($url, $requestToken) {

		$acc_req = OAuthRequest::from_consumer_and_token($this->consumer, $requestToken, "GET", $url, NULL);
		$acc_req->sign_request($this->signer, $this->consumer, $requestToken);

		$response_acc = file_get_contents($acc_req->to_url());
		
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

