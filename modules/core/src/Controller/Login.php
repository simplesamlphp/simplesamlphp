<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception as BuiltinException;
use SimpleSAML\{Auth, Configuration, Error, Module, Utils};
use SimpleSAML\Assert\Assert;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module\core\Auth\{UserPassBase, UserPassOrgBase};
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function substr;
use function time;
use function trim;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\core
 */
class Login
{
    /**
     * @var \SimpleSAML\Auth\Source|string
     * @psalm-var \SimpleSAML\Auth\Source|class-string
     */
    protected $authSource = Auth\Source::class;

    /**
     * @var \SimpleSAML\Auth\State|string
     * @psalm-var \SimpleSAML\Auth\State|class-string
     */
    protected $authState = Auth\State::class;


    /**
     * Controller constructor.
     *
     * It initializes the global configuration for the controllers implemented here.
     *
     * @param \SimpleSAML\Configuration              $config The configuration to use by the controllers.
     *
     * @throws \Exception
     */
    public function __construct(
        protected Configuration $config
    ) {
    }


    /**
     * Inject the \SimpleSAML\Auth\Source dependency.
     *
     * @param \SimpleSAML\Auth\Source $authSource
     */
    public function setAuthSource(Auth\Source $authSource): void
    {
        $this->authSource = $authSource;
    }


    /**
     * Inject the \SimpleSAML\Auth\State dependency.
     *
     * @param \SimpleSAML\Auth\State $authState
     */
    public function setAuthState(Auth\State $authState): void
    {
        $this->authState = $authState;
    }


    /**
     * @return \SimpleSAML\XHTML\Template
     */
    public function welcome(): Template
    {
        return new Template($this->config, 'core:welcome.twig');
    }


    /**
     * This page shows a username/password login form, and passes information from it
     * to the \SimpleSAML\Module\core\Auth\UserPassBase class, which is a generic class for
     * username/password authentication.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function loginuserpass(Request $request): Response
    {
        // Retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }
        $authStateId = $request->query->get('AuthState');

        $state = $this->authState::loadState($authStateId, UserPassBase::STAGEID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassBase|null $source */
        $source = $this->authSource::getById($state[UserPassBase::AUTHID]);
        if ($source === null) {
            throw new BuiltinException(
                'Could not find authentication source with id ' . $state[UserPassBase::AUTHID]
            );
        }

        return $this->handleLogin($request, $source, $state);
    }


    /**
     * This method handles the generic part for both loginuserpass and loginuserpassorg
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\Module\core\Auth\UserPassBase|\SimpleSAML\Module\core\Auth\UserPassOrgBase $source
     * @param array $state
     */
    private function handleLogin(Request $request, UserPassBase|UserPassOrgBase $source, array $state): Response
    {
        $authStateId = $request->query->get('AuthState');

        $organizations = $organization = null;
        if ($source instanceof UserPassOrgBase) {
            $organizations = UserPassOrgBase::listOrganizations($authStateId);
            $organization = $this->getOrganizationFromRequest($request, $source, $state);
        }

        $username = $this->getUsernameFromRequest($request, $source, $state);
        $password = $this->getPasswordFromRequest($request);

        $errorCode = null;
        $errorParams = null;

        if (isset($state['error'])) {
            $errorCode = $state['error']['code'];
            $errorParams = $state['error']['params'];
        }

        $cookies = [];
        if ($organizations === null || $organization !== '') {
            if (!empty($username) || !empty($password)) {
                $httpUtils = new Utils\HTTP();
                $sameSiteNone = $httpUtils->canSetSamesiteNone() ? Cookie::SAMESITE_NONE : null;

                // Either username or password set - attempt to log in
                if (array_key_exists('forcedUsername', $state) && ($state['forcedUsername'] !== false)) {
                    $username = $state['forcedUsername'];
                }

                if ($source->getRememberUsernameEnabled()) {
                    if (
                        $request->request->has('remember_username')
                        && ($request->request->get('remember_username') === 'Yes')
                    ) {
                        $expire = time() + 3153600;
                    } else {
                        $expire = time() - 300;
                    }

                    $cookies[] = $this->renderCookie(
                        $source->getAuthId() . '-username',
                        $username,
                        $expire,
                        '/',   // path
                        null,  // domain
                        null,  // secure
                        true,  // httponly
                        false, // raw
                        $sameSiteNone,
                    );
                }

                if (($source instanceof UserPassBase) && $source->isRememberMeEnabled()) {
                    if ($request->request->has('remember_me') && ($request->request->get('remember_me') === 'Yes')) {
                        $state['RememberMe'] = true;
                        $authStateId = Auth\State::saveState($state, UserPassBase::STAGEID);
                    }
                }

                if (($source instanceof UserPassOrgBase) && $source->getRememberOrganizationEnabled()) {
                    if (
                        $request->request->has('remember_organization')
                        && ($request->request->get('remember_organization') === 'Yes')
                    ) {
                        $expire = time() + 3153600;
                    } else {
                        $expire = time() - 300;
                    }

                    $cookies[] = $this->renderCookie(
                        $source->getAuthId() . '-organization',
                        $organization,
                        $expire,
                        '/',   // path
                        null,  // domain
                        null,  // secure
                        true,  // httponly
                        false, // raw
                        $sameSiteNone,
                    );
                }

                try {
                    if ($source instanceof UserPassOrgBase) {
                        UserPassOrgBase::handleLogin($authStateId, $username, $password, $organization);
                    } else {
                        UserPassBase::handleLogin($authStateId, $username, $password);
                    }
                } catch (Error\Error $e) {
                    // Login failed. Extract error code and parameters, to display the error
                    $errorCode = $e->getErrorCode();
                    $errorParams = $e->getParameters();
                    $state['error'] = [
                        'code' => $errorCode,
                        'params' => $errorParams
                    ];
                    $authStateId = Auth\State::saveState($state, $source::STAGEID);
                }

                if (isset($state['error'])) {
                    unset($state['error']);
                }
            }
        }

        $t = new Template($this->config, 'core:loginuserpass.twig');
        $t->data['AuthState'] = $authStateId;

        if ($source instanceof UserPassOrgBase) {
            $t->data['username'] = $username;
            $t->data['forceUsername'] = false;
            $t->data['rememberUsernameEnabled'] = $source->getRememberUsernameEnabled();
            $t->data['rememberUsernameChecked'] = $source->getRememberUsernameChecked();
            $t->data['rememberMeEnabled'] = false;
            $t->data['rememberMeChecked'] = false;
        } elseif (array_key_exists('forcedUsername', $state)) {
            $t->data['username'] = $state['forcedUsername'];
            $t->data['forceUsername'] = true;
            $t->data['rememberUsernameEnabled'] = false;
            $t->data['rememberUsernameChecked'] = false;
            $t->data['rememberMeEnabled'] = $source->isRememberMeEnabled();
            $t->data['rememberMeChecked'] = $source->isRememberMeChecked();
        } else {
            $t->data['username'] = $username;
            $t->data['forceUsername'] = false;
            $t->data['rememberUsernameEnabled'] = $source->getRememberUsernameEnabled();
            $t->data['rememberUsernameChecked'] = $source->getRememberUsernameChecked();
            $t->data['rememberMeEnabled'] = $source->isRememberMeEnabled();
            $t->data['rememberMeChecked'] = $source->isRememberMeChecked();

            if ($request->cookies->has($source->getAuthId() . '-username')) {
                $t->data['rememberUsernameChecked'] = true;
            }
        }

        if ($source instanceof UserPassOrgBase) {
            if ($request->request->has($source->getAuthId() . '-username')) {
                $t->data['rememberUsernameChecked'] = true;
            }

            $t->data['rememberOrganizationEnabled'] = $source->getRememberOrganizationEnabled();
            $t->data['rememberOrganizationChecked'] = $source->getRememberOrganizationChecked();

            if ($request->request->has($source->getAuthId() . '-organization')) {
                $t->data['rememberOrganizationChecked'] = true;
            }

            if ($organizations !== null) {
                $t->data['selectedOrg'] = $organization;
                $t->data['organizations'] = $organizations;
            }
        } else {
            $t->data['loginpage_links'] = $source->getLoginLinks();
        }

        $t->data['errorcode'] = $errorCode;
        $t->data['errorcodes'] = Error\ErrorCodes::getAllErrorCodeMessages();
        $t->data['errorparams'] = $errorParams;

        if (isset($state['SPMetadata'])) {
            $t->data['SPMetadata'] = $state['SPMetadata'];
        } else {
            $t->data['SPMetadata'] = null;
        }

        foreach ($cookies as $cookie) {
            $t->headers->setCookie($cookie);
        }

        return $t;
    }


    /**
     * This page shows a username/password/organization login form, and passes information from
     * into the \SimpleSAML\Module\core\Auth\UserPassBase class, which is a generic class for
     * username/password/organization authentication.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function loginuserpassorg(Request $request): Response
    {
        // Retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }
        $authStateId = $request->query->get('AuthState');

        $state = $this->authState::loadState($authStateId, UserPassOrgBase::STAGEID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassOrgBase $source */
        $source = $this->authSource::getById($state[UserPassOrgBase::AUTHID]);
        if ($source === null) {
            throw new BuiltinException(
                'Could not find authentication source with id ' . $state[UserPassOrgBase::AUTHID]
            );
        }

        return $this->handleLogin($request, $source, $state);
    }


    /**
     * @param string $name     The name for the cookie
     * @param string $value    The value for the cookie
     * @param int $expire      The expiration in seconds
     * @param string $path     The path for the cookie
     * @param string $domain   The domain for the cookie
     * @param bool $secure     Whether this cookie must have the secure-flag
     * @param bool $httponly   Whether this cookie must have the httponly-flag
     * @param bool $raw        Whether this cookie must be sent without urlencoding
     * @param string $sameSite The value for the sameSite-flag
     * @return \Symfony\Component\HttpFoundation\Cookie
     */
    private function renderCookie(
        string $name,
        ?string $value,
        int $expire = 0,
        string $path = '/',
        ?string $domain = null,
        ?bool $secure = null,
        bool $httponly = true,
        bool $raw = false,
        ?string $sameSite = 'none'
    ): Cookie {
        return new Cookie($name, $value, $expire, $path, $domain, $secure, $httponly, $raw, $sameSite);
    }


    /**
     * Retrieve the username from the request, a cookie or the state
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\Auth\Source $source
     * @param array $state
     * @return string
     */
    private function getUsernameFromRequest(Request $request, Auth\Source $source, array $state): string
    {
        $username = '';

        if ($request->request->has('username')) {
            $username = trim($request->request->get('username'));
        } elseif (
            $source->getRememberUsernameEnabled()
            && $request->cookies->has($source->getAuthId() . '-username')
        ) {
            $username = $request->cookies->get($source->getAuthId() . '-username');
        } elseif (isset($state['core:username'])) {
            $username = strval($state['core:username']);
        }

        return $username;
    }


    /**
     * Retrieve the password from the request
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return string
     */
    private function getPasswordFromRequest(Request $request): string
    {
        $password = '';

        if ($request->request->has('password')) {
            $password = $request->request->get('password');
        }

        return $password;
    }


    /**
     * Retrieve the organization from the request, a cookie or the state
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param \SimpleSAML\Auth\Source $source
     * @param array $state
     * @return string
     */
    private function getOrganizationFromRequest(Request $request, Auth\Source $source, array $state): string
    {
        $organization = '';

        if ($request->request->has('organization')) {
            $organization = $request->request->get('organization');
        } elseif (
            $source->getRememberOrganizationEnabled()
            && $request->cookies->has($source->getAuthId() . '-organization')
        ) {
            $organization = $request->cookies->get($source->getAuthId() . '-organization');
        } elseif (isset($state['core:organization'])) {
            $organization = strval($state['core:organization']);
        }

        return $organization;
    }


    /**
     * Searches for a valid and allowed ReturnTo URL parameter,
     * otherwise give the base installation page as a return point.
     */
    private function getReturnPath(Request $request): string
    {
        $httpUtils = new Utils\HTTP();

        $returnTo = $request->query->get('ReturnTo', false);
        if ($returnTo !== false) {
            $returnTo = $httpUtils->checkURLAllowed($returnTo);
        }
        if (empty($returnTo)) {
            return $this->config->getBasePath();
        }
        return $returnTo;
    }


    /**
     * This clears the user's IdP discovery choices.
     *
     * @param Request $request The request that lead to this login operation.
     */
    public function cleardiscochoices(Request $request): void
    {
        $httpUtils = new Utils\HTTP();

        // The base path for cookies. This should be the installation directory for SimpleSAMLphp.
        $cookiePath = $this->config->getBasePath();

        // We delete all cookies which starts with 'idpdisco_'
        foreach ($request->cookies->all() as $cookieName => $value) {
            if (substr($cookieName, 0, 9) !== 'idpdisco_') {
                // Not a idpdisco cookie.
                continue;
            }

            $httpUtils->setCookie($cookieName, null, ['path' => $cookiePath, 'httponly' => false], false);
        }

        $returnTo = $this->getReturnPath($request);

        // Redirect to destination.
        $httpUtils->redirectTrustedURL($returnTo);
    }
}
