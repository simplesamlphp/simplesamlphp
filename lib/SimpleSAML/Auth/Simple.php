<?php

namespace SimpleSAML\Auth;

use \SimpleSAML_Auth_Source as Source;
use \SimpleSAML_Auth_State as State;
use \SimpleSAML_Configuration as Configuration;
use \SimpleSAML_Error_AuthSource as AuthSourceError;
use \SimpleSAML\Module;
use \SimpleSAML_Session as Session;
use \SimpleSAML\Utils\HTTP;

/**
 * Helper class for simple authentication applications.
 *
 * @package SimpleSAMLphp
 */
class Simple
{

    /**
     * The id of the authentication source we are accessing.
     *
     * @var string
     */
    protected $authSource;

    /**
     * @var \SimpleSAML_Configuration|null
     */
    protected $app_config;

    /**
     * Create an instance with the specified authsource.
     *
     * @param string $authSource The id of the authentication source.
     */
    public function __construct($authSource)
    {
        assert('is_string($authSource)');

        $this->authSource = $authSource;
        $this->app_config = Configuration::getInstance()->getConfigItem('application', null);
    }


    /**
     * Retrieve the implementing authentication source.
     *
     * @return \SimpleSAML_Auth_Source The authentication source.
     *
     * @throws \SimpleSAML_Error_AuthSource If the requested auth source is unknown.
     */
    public function getAuthSource()
    {
        $as = Source::getById($this->authSource);
        if ($as === null) {
            throw new AuthSourceError($this->authSource, 'Unknown authentication source.');
        }
        return $as;
    }


    /**
     * Check if the user is authenticated.
     *
     * This function checks if the user is authenticated with the default authentication source selected by the
     * 'default-authsource' option in 'config.php'.
     *
     * @return bool True if the user is authenticated, false if not.
     */
    public function isAuthenticated()
    {
        $session = Session::getSessionFromRequest();

        return $session->isValid($this->authSource);
    }


    /**
     * Require the user to be authenticated.
     *
     * If the user is authenticated, this function returns immediately.
     *
     * If the user isn't authenticated, this function will authenticate the user with the authentication source, and
     * then return the user to the current page.
     *
     * This function accepts an array $params, which controls some parts of the authentication. See the login()
     * method for a description.
     *
     * @param array $params Various options to the authentication request. See the documentation.
     */
    public function requireAuth(array $params = array())
    {

        $session = Session::getSessionFromRequest();

        if ($session->isValid($this->authSource)) {
            // Already authenticated
            return;
        }

        $this->login($params);
    }


    /**
     * Start an authentication process.
     *
     * This function accepts an array $params, which controls some parts of the authentication. The accepted parameters
     * depends on the authentication source being used. Some parameters are generic:
     *  - 'ErrorURL': A URL that should receive errors from the authentication.
     *  - 'KeepPost': If the current request is a POST request, keep the POST data until after the authentication.
     *  - 'ReturnTo': The URL the user should be returned to after authentication.
     *  - 'ReturnCallback': The function we should call after the user has finished authentication.
     *
     * Please note: this function never returns.
     *
     * @param array $params Various options to the authentication request.
     */
    public function login(array $params = array())
    {

        if (array_key_exists('KeepPost', $params)) {
            $keepPost = (bool) $params['KeepPost'];
        } else {
            $keepPost = true;
        }

        if (array_key_exists('ReturnTo', $params)) {
            $returnTo = (string) $params['ReturnTo'];
        } else {
            if (array_key_exists('ReturnCallback', $params)) {
                $returnTo = (array) $params['ReturnCallback'];
            } else {
                $returnTo = HTTP::getSelfURL();
            }
        }

        if (is_string($returnTo) && $keepPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnTo = HTTP::getPOSTRedirectURL($returnTo, $_POST);
        }

        if (array_key_exists('ErrorURL', $params)) {
            $errorURL = (string) $params['ErrorURL'];
        } else {
            $errorURL = null;
        }


        if (!isset($params[State::RESTART]) && is_string($returnTo)) {
            /*
             * A URL to restart the authentication, in case the user bookmarks
             * something, e.g. the discovery service page.
             */
            $restartURL = $this->getLoginURL($returnTo);
            $params[State::RESTART] = $restartURL;
        }

        $as = $this->getAuthSource();
        $as->initLogin($returnTo, $errorURL, $params);
        assert('false');
    }


    /**
     * Log the user out.
     *
     * This function logs the user out. It will never return. By default, it will cause a redirect to the current page
     * after logging the user out, but a different URL can be given with the $params parameter.
     *
     * Generic parameters are:
     *  - 'ReturnTo': The URL the user should be returned to after logout.
     *  - 'ReturnCallback': The function that should be called after logout.
     *  - 'ReturnStateParam': The parameter we should return the state in when redirecting.
     *  - 'ReturnStateStage': The stage the state array should be saved with.
     *
     * @param string|array|null $params Either the URL the user should be redirected to after logging out, or an array
     * with parameters for the logout. If this parameter is null, we will return to the current page.
     */
    public function logout($params = null)
    {
        assert('is_array($params) || is_string($params) || is_null($params)');

        if ($params === null) {
            $params = HTTP::getSelfURL();
        }

        if (is_string($params)) {
            $params = array(
                'ReturnTo' => $params,
            );
        }

        assert('is_array($params)');
        assert('isset($params["ReturnTo"]) || isset($params["ReturnCallback"])');

        if (isset($params['ReturnStateParam']) || isset($params['ReturnStateStage'])) {
            assert('isset($params["ReturnStateParam"]) && isset($params["ReturnStateStage"])');
        }

        $session = Session::getSessionFromRequest();
        if ($session->isValid($this->authSource)) {
            $state = $session->getAuthData($this->authSource, 'LogoutState');
            if ($state !== null) {
                $params = array_merge($state, $params);
            }

            $session->doLogout($this->authSource);

            $params['LogoutCompletedHandler'] = array(get_class(), 'logoutCompleted');

            $as = Source::getById($this->authSource);
            if ($as !== null) {
                $as->logout($params);
            }
        }

        self::logoutCompleted($params);
    }


    /**
     * Called when logout operation completes.
     *
     * This function never returns.
     *
     * @param array $state The state after the logout.
     */
    public static function logoutCompleted($state)
    {
        assert('is_array($state)');
        assert('isset($state["ReturnTo"]) || isset($state["ReturnCallback"])');

        if (isset($state['ReturnCallback'])) {
            call_user_func($state['ReturnCallback'], $state);
            assert('false');
        } else {
            $params = array();
            if (isset($state['ReturnStateParam']) || isset($state['ReturnStateStage'])) {
                assert('isset($state["ReturnStateParam"]) && isset($state["ReturnStateStage"])');
                $stateID = State::saveState($state, $state['ReturnStateStage']);
                $params[$state['ReturnStateParam']] = $stateID;
            }
            \SimpleSAML\Utils\HTTP::redirectTrustedURL($state['ReturnTo'], $params);
        }
    }


    /**
     * Retrieve attributes of the current user.
     *
     * This function will retrieve the attributes of the current user if the user is authenticated. If the user isn't
     * authenticated, it will return an empty array.
     *
     * @return array The users attributes.
     */
    public function getAttributes()
    {

        if (!$this->isAuthenticated()) {
            // Not authenticated
            return array();
        }

        // Authenticated
        $session = Session::getSessionFromRequest();
        return $session->getAuthData($this->authSource, 'Attributes');
    }


    /**
     * Retrieve authentication data.
     *
     * @param string $name The name of the parameter, e.g. 'Attributes', 'Expire' or 'saml:sp:IdP'.
     *
     * @return mixed|null The value of the parameter, or null if it isn't found or we are unauthenticated.
     */
    public function getAuthData($name)
    {
        assert('is_string($name)');

        if (!$this->isAuthenticated()) {
            return null;
        }

        $session = Session::getSessionFromRequest();
        return $session->getAuthData($this->authSource, $name);
    }


    /**
     * Retrieve all authentication data.
     *
     * @return array|null All persistent authentication data, or null if we aren't authenticated.
     */
    public function getAuthDataArray()
    {

        if (!$this->isAuthenticated()) {
            return null;
        }

        $session = Session::getSessionFromRequest();
        return $session->getAuthState($this->authSource);
    }


    /**
     * Retrieve a URL that can be used to log the user in.
     *
     * @param string|null $returnTo The page the user should be returned to afterwards. If this parameter is null, the
     * user will be returned to the current page.
     *
     * @return string A URL which is suitable for use in link-elements.
     */
    public function getLoginURL($returnTo = null)
    {
        assert('is_null($returnTo) || is_string($returnTo)');

        if ($returnTo === null) {
            $returnTo = HTTP::getSelfURL();
        }

        $login = Module::getModuleURL('core/as_login.php', array(
            'AuthId'   => $this->authSource,
            'ReturnTo' => $returnTo,
        ));

        return $login;
    }


    /**
     * Retrieve a URL that can be used to log the user out.
     *
     * @param string|null $returnTo The page the user should be returned to afterwards. If this parameter is null, the
     * user will be returned to the current page.
     *
     * @return string A URL which is suitable for use in link-elements.
     */
    public function getLogoutURL($returnTo = null)
    {
        assert('is_null($returnTo) || is_string($returnTo)');

        if ($returnTo === null) {
            $returnTo = HTTP::getSelfURL();
        }

        $logout = Module::getModuleURL('core/as_logout.php', array(
            'AuthId'   => $this->authSource,
            'ReturnTo' => $returnTo,
        ));

        return $logout;
    }


    /**
     * Process a URL and modify it according to the application/baseURL configuration option, if present.
     *
     * @param string|null $url The URL to process, or null if we want to use the current URL. Both partial and full
     * URLs can be used as a parameter. The maximum precedence is given to the application/baseURL configuration option,
     * then the URL specified (if it specifies scheme, host and port) and finally the environment observed in the
     * server.
     *
     * @return string The URL modified according to the precedence rules.
     */
    protected function getProcessedURL($url = null)
    {
        if ($url === null) {
            $url = HTTP::getSelfURL();
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST) ?: HTTP::getSelfHost();
        $port = parse_url($url, PHP_URL_PORT) ?: (
            $scheme ? '' : trim(HTTP::getServerPort(), ':')
        );
        $scheme = $scheme ?: (HTTP::getServerHTTPS() ? 'https' : 'http');
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY) ?: '';
        $fragment = parse_url($url, PHP_URL_FRAGMENT) ?: '';

        $port = !empty($port) ? ':'.$port : '';
        if (($scheme === 'http' && $port === ':80') || ($scheme === 'https' && $port === ':443')) {
            $port = '';
        }

        if (is_null($this->app_config)) {
            // nothing more we can do here
            return $scheme.'://'.$host.$port.$path.($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');
        }

        $base =  trim($this->app_config->getString(
            'baseURL',
            $scheme.'://'.$host.$port
        ), '/');
        return $base.$path.($query ? '?'.$query : '').($fragment ? '#'.$fragment : '');
    }
}
