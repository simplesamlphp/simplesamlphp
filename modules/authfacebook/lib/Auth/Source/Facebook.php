<?php

namespace SimpleSAML\Module\authfacebook\Auth\Source;

use SimpleSAML\Module;

/**
 * Authenticate using Facebook Platform.
 *
 * @author Andreas Åkre Solberg, UNINETT AS.
 * @package SimpleSAMLphp
 */

class Facebook extends \SimpleSAML\Auth\Source
{
    /**
     * The string used to identify our states.
     */
    const STAGE_INIT = 'facebook:init';


    /**
     * The key of the AuthId field in the state.
     */
    const AUTHID = 'facebook:AuthId';


    /**
     * Facebook App ID or API Key
     */
    private $api_key;


    /**
     * Facebook App Secret
     */
    private $secret;


    /**
     * Which additional data permissions to request from user
     */
    private $req_perms;


    /**
     * A comma-separated list of user profile fields to request.
     *
     * Note that some user fields require appropriate permissions. For
     * example, to retrieve the user's primary email address, "email" must
     * be specified in both the req_perms and the user_fields parameter.
     *
     * When empty, only the app-specific user id and name will be returned.
     *
     * See the Graph API specification for all available user fields:
     * https://developers.facebook.com/docs/graph-api/reference/v2.6/user
     */
    private $user_fields;


    /**
     * Constructor for this authentication source.
     *
     * @param array $info  Information about this authentication source.
     * @param array $config  Configuration.
     */
    public function __construct($info, $config)
    {
        assert(is_array($info));
        assert(is_array($config));

        // Call the parent constructor first, as required by the interface
        parent::__construct($info, $config);

        $cfgParse = \SimpleSAML\Configuration::loadFromArray(
            $config,
            'authsources['.var_export($this->authId, true).']'
        );

        $this->api_key = $cfgParse->getString('api_key');
        $this->secret = $cfgParse->getString('secret');
        $this->req_perms = $cfgParse->getString('req_perms', null);
        $this->user_fields = $cfgParse->getString('user_fields', null);
    }


    /**
     * Log-in using Facebook platform
     *
     * @param array &$state  Information about the current authentication.
     */
    public function authenticate(&$state)
    {
        assert(is_array($state));

        // We are going to need the authId in order to retrieve this authentication source later
        $state[self::AUTHID] = $this->authId;
        \SimpleSAML\Auth\State::saveState($state, self::STAGE_INIT);

        $facebook = new Module\authfacebook\Facebook(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $facebook->destroySession();

        $linkback = Module::getModuleURL('authfacebook/linkback.php');
        $url = $facebook->getLoginUrl(['redirect_uri' => $linkback, 'scope' => $this->req_perms]);
        \SimpleSAML\Auth\State::saveState($state, self::STAGE_INIT);

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
    }


    public function finalStep(&$state)
    {
        assert(is_array($state));

        $facebook = new Module\authfacebook\Facebook(
            ['appId' => $this->api_key, 'secret' => $this->secret],
            $state
        );
        $uid = $facebook->getUser();

        if (isset($uid) && $uid) {
            try {
                $info = $facebook->api("/".$uid.($this->user_fields ? "?fields=".$this->user_fields : ""));
            } catch (\FacebookApiException $e) {
                throw new \SimpleSAML\Error\AuthSource($this->authId, 'Error getting user profile.', $e);
            }
        }

        if (!isset($info)) {
            throw new \SimpleSAML\Error\AuthSource($this->authId, 'Error getting user profile.');
        }

        $attributes = [];
        foreach ($info as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $attributes['facebook.'.$key] = [(string) $value];
            }
        }

        if (array_key_exists('third_party_id', $info)) {
            $attributes['facebook_user'] = [$info['third_party_id'].'@facebook.com'];
        } else {
            $attributes['facebook_user'] = [$uid.'@facebook.com'];
        }

        $attributes['facebook_targetedID'] = ['http://facebook.com!'.$uid];
        $attributes['facebook_cn'] = [$info['name']];

        \SimpleSAML\Logger::debug('Facebook Returned Attributes: '.implode(", ", array_keys($attributes)));

        $state['Attributes'] = $attributes;

        $facebook->destroySession();
    }
}
