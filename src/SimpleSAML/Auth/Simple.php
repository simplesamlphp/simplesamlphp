<?php

declare(strict_types=1);

namespace SimpleSAML\Auth;

use SimpleSAML\{Configuration, Error, Module, Session, Utils};
use SimpleSAML\Assert\Assert;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function call_user_func;
use function get_class;
use function is_string;
use function parse_url;
use function trim;
use function urlencode;

/**
 * Helper class for simple authentication applications.
 *
 * @package SimpleSAMLphp
 */

class Simple
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $app_config;

    /** @var \SimpleSAML\Session */
    protected Session $session;


    /**
     * Create an instance with the specified authsource.
     *
     * @param string $authSource The id of the authentication source.
     * @param \SimpleSAML\Configuration|null $config Optional configuration to use.
     * @param \SimpleSAML\Session|null $session Optional session to use.
     */
    public function __construct(
        protected string $authSource,
        ?Configuration $config = null,
        ?Session $session = null,
    ) {
        if ($config === null) {
            $config = Configuration::getInstance();
        }
        $this->app_config = $config->getOptionalConfigItem('application', []);

        if ($session === null) {
            $session = Session::getSessionFromRequest();
        }
        $this->session = $session;
    }


    /**
     * Retrieve the implementing authentication source.
     *
     * @return \SimpleSAML\Auth\Source The authentication source.
     *
     * @throws \SimpleSAML\Error\AuthSource If the requested auth source is unknown.
     */
    public function getAuthSource(): Source
    {
        $as = Source::getById($this->authSource);
        if ($as === null) {
            throw new Error\AuthSource($this->authSource, 'Unknown authentication source.');
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
    public function isAuthenticated(): bool
    {
        return $this->session->isValid($this->authSource);
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
    public function requireAuth(array $params = []): ?Response
    {
        if ($this->session->isValid($this->authSource)) {
            // Already authenticated
            return null;
        }

        return $this->login($params);
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
     * @param array $params Various options to the authentication request.
     */
    public function login(array $params = []): Response
    {
        if (array_key_exists('KeepPost', $params)) {
            $keepPost = (bool) $params['KeepPost'];
        } else {
            $keepPost = true;
        }

        $httpUtils = new Utils\HTTP();
        if (array_key_exists('ReturnTo', $params)) {
            $returnTo = (string) $params['ReturnTo'];
        } else {
            if (array_key_exists('ReturnCallback', $params)) {
                $returnTo = (array) $params['ReturnCallback'];
            } else {
                $returnTo = $httpUtils->getSelfURL();
            }
        }

        if (is_string($returnTo) && $keepPost && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $returnTo = $httpUtils->getPOSTRedirectURL($returnTo, $_POST);
        }

        if (array_key_exists('ErrorURL', $params)) {
            $errorURL = (string) $params['ErrorURL'];
        } else {
            $errorURL = null;
        }

        $as = $this->getAuthSource();
        if (
            $as instanceof Module\saml\Auth\Source\SP
            && !isset($params[State::RESTART])
            && is_string($returnTo)
        ) {
            /*
             * A URL to restart the authentication, in case the user bookmarks
             * something, e.g. the discovery service page.
             */
            $restartURL = $this->getLoginURL($returnTo);
            $params[State::RESTART] = $restartURL;
        }

        $as = $this->getAuthSource();
        return $as->initLogin($returnTo, $errorURL, $params);
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
    public function logout(string|array|null $params = null): Response
    {
        if ($params === null) {
            $httpUtils = new Utils\HTTP();
            $params = $httpUtils->getSelfURL();
        }

        if (is_string($params)) {
            $params = [
                'ReturnTo' => $params,
            ];
        }

        Assert::isArray($params);
        Assert::true(isset($params['ReturnTo']) || isset($params['ReturnCallback']));

        if (isset($params['ReturnStateParam']) || isset($params['ReturnStateStage'])) {
            Assert::true(isset($params['ReturnStateParam'], $params['ReturnStateStage']));
        }

        if ($this->session->isValid($this->authSource)) {
            $state = $this->session->getAuthData($this->authSource, 'LogoutState');
            if ($state !== null) {
                $params = array_merge($state, $params);
            }

            $this->session->doLogout($this->authSource);

            $params['LogoutCompletedHandler'] = [get_class($this), 'logoutCompleted'];

            $as = Source::getById($this->authSource);
            if ($as !== null) {
                $response = $as->logout($params);
                if ($response instanceof Response) {
                    return $response;
                }
            }
        }

        return self::logoutCompleted($params);
    }


    /**
     * Called when logout operation completes.
     *
     * This function never returns.
     *
     * @param array $state The state after the logout.
     */
    public static function logoutCompleted(array $state): Response
    {
        Assert::true(isset($state['ReturnTo']) || isset($state['ReturnCallback']));

        if (isset($state['ReturnCallback'])) {
            $response = call_user_func($state['ReturnCallback'], $state);
            Assert::isInstanceOf($response, Response::class);
        } else {
            $params = [];
            if (isset($state['ReturnStateParam']) || isset($state['ReturnStateStage'])) {
                Assert::true(isset($state['ReturnStateParam'], $state['ReturnStateStage']));
                $stateID = State::saveState($state, $state['ReturnStateStage']);
                $params[$state['ReturnStateParam']] = $stateID;
            }
            $httpUtils = new Utils\HTTP();
            $response = $httpUtils->redirectTrustedURL($state['ReturnTo'], $params);
        }

        return $response;
    }


    /**
     * Retrieve attributes of the current user.
     *
     * This function will retrieve the attributes of the current user if the user is authenticated. If the user isn't
     * authenticated, it will return an empty array.
     *
     * @return array The users attributes.
     */
    public function getAttributes(): array
    {
        if (!$this->isAuthenticated()) {
            // Not authenticated
            return [];
        }

        // Authenticated
        return $this->session->getAuthData($this->authSource, 'Attributes');
    }


    /**
     * Retrieve authentication data.
     *
     * @param string $name The name of the parameter, e.g. 'Attributes', 'Expire' or 'saml:sp:IdP'.
     *
     * @return mixed|null The value of the parameter, or null if it isn't found or we are unauthenticated.
     */
    public function getAuthData(string $name): mixed
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->session->getAuthData($this->authSource, $name);
    }


    /**
     * Retrieve all authentication data.
     *
     * @return array|null All persistent authentication data, or null if we aren't authenticated.
     */
    public function getAuthDataArray(): ?array
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->session->getAuthState($this->authSource);
    }


    /**
     * Retrieve a URL that can be used to log the user in.
     *
     * @param string|null $returnTo The page the user should be returned to afterwards. If this parameter is null, the
     * user will be returned to the current page.
     *
     * @return string A URL which is suitable for use in link-elements.
     */
    public function getLoginURL(?string $returnTo = null): string
    {
        if ($returnTo === null) {
            $httpUtils = new Utils\HTTP();
            $returnTo = $httpUtils->getSelfURL();
        }

        $login = Module::getModuleURL('saml/sp/login/' . urlencode($this->authSource), [
            'ReturnTo' => $returnTo,
        ]);

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
    public function getLogoutURL(?string $returnTo = null): string
    {
        if ($returnTo === null) {
            $httpUtils = new Utils\HTTP();
            $returnTo = $httpUtils->getSelfURL();
        }

        $logout = Module::getModuleURL('core/logout/' . urlencode($this->authSource), [
            'ReturnTo' => $returnTo,
        ]);

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
    protected function getProcessedURL(?string $url = null): string
    {
        $httpUtils = new Utils\HTTP();
        if ($url === null) {
            $url = $httpUtils->getSelfURL();
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        $host = parse_url($url, PHP_URL_HOST) ?: $httpUtils->getSelfHost();
        $port = parse_url($url, PHP_URL_PORT) ?:
            ($scheme ? '' : ltrim($httpUtils->getServerPort(), ':'));
        $scheme = $scheme ?: ($httpUtils->getServerHTTPS() ? 'https' : 'http');
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $query = parse_url($url, PHP_URL_QUERY) ?: '';
        $fragment = parse_url($url, PHP_URL_FRAGMENT) ?: '';

        $port = !empty($port) ? ':' . $port : '';
        if (($scheme === 'http' && $port === ':80') || ($scheme === 'https' && $port === ':443')) {
            $port = '';
        }

        $base = trim($this->app_config->getOptionalString(
            'baseURL',
            $scheme . '://' . $host . $port,
        ), '/');
        return $base . $path . ($query ? '?' . $query : '') . ($fragment ? '#' . $fragment : '');
    }
}
