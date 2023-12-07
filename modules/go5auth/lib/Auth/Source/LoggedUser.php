<?php

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException as HttpClientException;

class sspmod_go5auth_Auth_Source_LoggedUser extends SimpleSAML_Auth_Source
{
    /**
     * @var HttpClient
     */
    private $httpClient;

    public function __construct(array $info, array $config)
    {
            parent::__construct($info, $config);

            $this->httpClient = new HttpClient;
    }

    public function authenticate(&$state)
    {
        if (!array_key_exists('access_token', $_REQUEST)
            && !array_key_exists('HTTP_AUTHORIZATION', $_SERVER)
        ) {
            throw new SimpleSAML_Error_Exception('go5auth | error: access_token is required');
        }

        $accessToken = isset($_REQUEST['access_token'])
            ? $_REQUEST['access_token']
            : trim(substr($_SERVER['HTTP_AUTHORIZATION'], 7));

        SimpleSAML_Logger::debug('go5auth | access_token: ' . $accessToken);
        $userInfo = $this->getUserInfo($accessToken);

        if ($userInfo->data->attributes->status != 'active') {
            throw new SimpleSAML_Error_Exception('go5auth | error: inactive user ' . $userInfo->data->id);
        }

        $userAttributes = [
            'email' => $userInfo->data->attributes->email,
            'employee-id' => $userInfo->data->attributes->{'employee-id'},
            'document' => $userInfo->data->attributes->{'document'},
            'document-type' => $userInfo->data->attributes->{'document-type'},
            'name' => $userInfo->data->attributes->{'name'},
            'last-name' => $userInfo->data->attributes->{'last-name'},
        ];
        $state['Attributes'] = SimpleSAML_Utilities::parseAttributes($userAttributes);

        SimpleSAML_Auth_Source::completeAuth($state);
    }

    private function getUserInfo($token)
    {
        try {
            $tokenResponse = $this->httpClient->get(URL_PREFIX . '/oauth/token?access_token=' . $token);
            $tokenInfo  = json_decode($tokenResponse->getBody()->getContents());
            $userResponse = $this->httpClient->get(
                BASE_URI_USER_SDK . '/users/' . $tokenInfo->user_id, [
                'headers' => [
                    'x-go5-platform-id' => $tokenInfo->platform_id,
                    'x-app-sdk' => 1,
                ]]);

            return json_decode($userResponse->getBody()->getContents());

        } catch (HttpClientException $e) {
            throw new SimpleSAML_Error_Exception('go5auth | error: ' . $e->getMessage());
        }
    }
}
