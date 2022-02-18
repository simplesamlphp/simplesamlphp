<?php

declare(strict_types=1);

namespace SimpleSAML\Module\core\Controller;

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Module;
use SimpleSAML\Module\core\Auth\UserPassBase;
use SimpleSAML\Module\core\Auth\UserPassOrgBase;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function array_key_exists;
use function substr;
use function time;

/**
 * Controller class for the core module.
 *
 * This class serves the different views available in the module.
 *
 * @package SimpleSAML\Module\core
 */
class Login
{
    /** @var \SimpleSAML\Configuration */
    protected Configuration $config;


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
        Configuration $config
    ) {
        $this->config = $config;
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
     * @return \SimpleSAML\XHTML\Template
     */
    public function loginuserpass(Request $request): Template
    {
        // Retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }
        $authStateId = $request->query->get('AuthState');

        /** @var array $state */
        $state = Auth\State::loadState($authStateId, UserPassBase::STAGEID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassBase|null $source */
        $source = Auth\Source::getById($state[UserPassBase::AUTHID]);
        if ($source === null) {
            throw new Exception(
                'Could not find authentication source with id ' . $state[UserPassBase::AUTHID]
            );
        }

        if ($request->request->has('username')) {
            $username = $request->request->get('username');
        } elseif (
            $source->getRememberUsernameEnabled()
            && $request->cookies->has($source->getAuthId() . '-username')
        ) {
            $username = $request->cookies->get($source->getAuthId() . '-username');
        } elseif (isset($state['core:username'])) {
            $username = (string) $state['core:username'];
        } else {
            $username = '';
        }

        if ($request->request->has('password')) {
            $password = $request->request->get('password');
        } else {
            $password = '';
        }

        $errorCode = null;
        $errorParams = null;
        $queryParams = [];

        if (isset($state['error'])) {
            $errorCode = $state['error']['code'];
            $errorParams = $state['error']['params'];
            $queryParams = ['AuthState' => $authStateId];
        }

        $cookie = null;
        if (!empty($request->request->get('username')) || !empty($password)) {
            // Either username or password set - attempt to log in

            if (array_key_exists('forcedUsername', $state)) {
                $username = $state['forcedUsername'];
            }

            if ($source->getRememberUsernameEnabled()) {
                if ($request->request->has('remember_username') && ($request->request->get('remember_username') === 'Yes')) {
                    $expire = time() + 31536000;
                } else {
                    $expire = time() - 300;
                }

                $cookie = new Cookie(
                    $source->getAuthId() . '-username',
                    $username, // value
                    $expire, // expire
                    '/', // path
                    '', // domain
                    true, // secure
                    true, // httponly
                );
            }

            if ($source->isRememberMeEnabled()) {
                if ($request->request->has('remember_me') && ($request->request->get('remember_me') === 'Yes')) {
                    $state['RememberMe'] = true;
                    $authStateId = Auth\State::saveState(
                        $state,
                        UserPassBase::STAGEID
                    );
                }
            }

            try {
                UserPassBase::handleLogin($authStateId, $username, $password);
            } catch (Error\Error $e) {
                // Login failed. Extract error code and parameters, to display the error
                $errorCode = $e->getErrorCode();
                $errorParams = $e->getParameters();
                $state['error'] = [
                    'code' => $errorCode,
                    'params' => $errorParams
                ];
                $authStateId = Auth\State::saveState($state, UserPassBase::STAGEID);
                $queryParams = ['AuthState' => $authStateId];
            }

            if (isset($state['error'])) {
                unset($state['error']);
            }
        }

        $t = new Template($this->config, 'core:loginuserpass.twig');
        $t->data['stateparams'] = ['AuthState' => $authStateId];

        if (array_key_exists('forcedUsername', $state)) {
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

        $t->data['links'] = $source->getLoginLinks();
        $t->data['errorcode'] = $errorCode;
        $t->data['errorcodes'] = Error\ErrorCodes::getAllErrorCodeMessages();
        $t->data['errorparams'] = $errorParams;
        if (!empty($queryParams)) {
            $t->data['queryParams'] = $queryParams;
        }

        if (isset($state['SPMetadata'])) {
            $t->data['SPMetadata'] = $state['SPMetadata'];
        } else {
            $t->data['SPMetadata'] = null;
        }

        if ($cookie !== null) {
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
     * @return \SimpleSAML\XHTML\Template
     */
    public function loginuserpassorg(Request $request): Template
    {
        // Retrieve the authentication state
        if (!$request->query->has('AuthState')) {
            throw new Error\BadRequest('Missing AuthState parameter.');
        }
        $authStateId = $request->query->get('AuthState');

        /** @var array $state */
        $state = Auth\State::loadState($authStateId, UserPassOrgBase::STAGEID);

        /** @var \SimpleSAML\Module\core\Auth\UserPassOrgBase $source */
        $source = Auth\Source::getById($state[UserPassOrgBase::AUTHID]);
        if ($source === null) {
            throw new Exception(
                'Could not find authentication source with id ' . $state[UserPassOrgBase::AUTHID]
            );
        }

        $organizations = UserPassOrgBase::listOrganizations($authStateId);

        if ($request->request->has('username')) {
            $username = $request->request->get('username');
        } elseif (
            $source->getRememberUsernameEnabled()
            && $request->cookies->has($source->getAuthId() . '-username')
        ) {
            $username = $request->cookies->get($source->getAuthId() . '-username');
        } elseif (isset($state['core:username'])) {
            $username = (string) $state['core:username'];
        } else {
            $username = '';
        }

        if ($request->request->has('password')) {
            $password = $request->request->get('password');
        } else {
            $password = '';
        }

        if ($request->request->has('organization')) {
            $organization = $request->request->get('organization');
        } elseif (
            $source->getRememberOrganizationEnabled()
            && $request->cookies->has($source->getAuthId() . '-organization')
        ) {
            $organization = $request->cookies->get($source->getAuthId() . '-organization');
        } elseif (isset($state['core:organization'])) {
            $organization = (string) $state['core:organization'];
        } else {
            $organization = '';
        }

        $errorCode = null;
        $errorParams = null;
        $queryParams = [];

        if (isset($state['error'])) {
            $errorCode = $state['error']['code'];
            $errorParams = $state['error']['params'];
            $queryParams = ['AuthState' => $authStateId];
        }

        $username_cookie = $org_cookie = null;
        if ($organizations === null || $organization !== '') {
            if (!empty($username) || !empty($password)) {
                if ($source->getRememberUsernameEnabled()) {
                    if (
                        $request->request->has('remember_username')
                        && ($request->request->get('remember_username') === 'Yes')
                    ) {
                        $expire = time() + 3153600;
                    } else {
                        $expire = time() - 300;
                    }

                    $username_cookie = new Cookie(
                        $source->getAuthId() . '-username',
                        $username, // value
                        $expire, // expire
                        '/', // path
                        '', // domain
                        true, // secure
                        true, // httponly
                    );
                }

                if ($source->getRememberOrganizationEnabled()) {
                    if (
                        $request->request->has('remember_organization')
                        && ($request->request->get('remember_organization') === 'Yes')
                    ) {
                        $expire = time() + 3153600;
                    } else {
                        $expire = time() - 300;
                    }

                    $org_cookie = new Cookie(
                        $source->getAuthId() . '-organization',
                        $organization, // value
                        $expire, // expire
                        '/', // path
                        '', // domain
                        true, // secure
                        true, // httponly
                    );
                }

                try {
                    UserPassOrgBase::handleLogin(
                        $authStateId,
                        $username,
                        $password,
                        $organization
                    );
                } catch (Error\Error $e) {
                    // Login failed. Extract error code and parameters, to display the error
                    $errorCode = $e->getErrorCode();
                    $errorParams = $e->getParameters();
                    $state['error'] = [
                        'code' => $errorCode,
                        'params' => $errorParams
                    ];
                    $authStateId = Auth\State::saveState(
                        $state,
                        UserPassOrgBase::STAGEID
                    );
                    $queryParams = ['AuthState' => $authStateId];
                }

                if (isset($state['error'])) {
                    unset($state['error']);
                }
            }
        }

        $t = new Template($this->config, 'core:loginuserpass.twig');
        $t->data['stateparams'] = ['AuthState' => $authStateId];
        $t->data['username'] = $username;
        $t->data['forceUsername'] = false;
        $t->data['rememberUsernameEnabled'] = $source->getRememberUsernameEnabled();
        $t->data['rememberUsernameChecked'] = $source->getRememberUsernameChecked();
        $t->data['rememberMeEnabled'] = false;
        $t->data['rememberMeChecked'] = false;

        if ($request->request->has($source->getAuthId() . '-username')) {
            $t->data['rememberUsernameChecked'] = true;
        }

        $t->data['rememberOrganizationEnabled'] = $source->getRememberOrganizationEnabled();
        $t->data['rememberOrganizationChecked'] = $source->getRememberOrganizationChecked();

        if ($request->request->has($source->getAuthId() . '-organization')) {
            $t->data['rememberOrganizationChecked'] = true;
        }

        $t->data['errorcode'] = $errorCode;
        $t->data['errorcodes'] = Error\ErrorCodes::getAllErrorCodeMessages();
        $t->data['errorparams'] = $errorParams;

        if (!empty($queryParams)) {
            $t->data['queryParams'] = $queryParams;
        }

        if ($organizations !== null) {
            $t->data['selectedOrg'] = $organization;
            $t->data['organizations'] = $organizations;
        }

        if (isset($state['SPMetadata'])) {
            $t->data['SPMetadata'] = $state['SPMetadata'];
        } else {
            $t->data['SPMetadata'] = null;
        }

        if ($username_cookie !== null) {
            $t->headers->setCookie($username_cookie);
        }

        if ($org_cookie !== null) {
            $t->headers->setCookie($org_cookie);
        }

        return $t;
    }


    /**
     * Log the user out of a given authentication source.
     *
     * @param Request $request The request that lead to this logout operation.
     * @param string $as The name of the auth source.
     *
     * @return \SimpleSAML\HTTP\RunnableResponse A runnable response which will actually perform logout.
     *
     * @throws \SimpleSAML\Error\CriticalConfigurationError
     */
    public function logout(Request $request, string $as): RunnableResponse
    {
        $auth = new Auth\Simple($as);
        $returnTo = $this->getReturnPath($request);
        return new RunnableResponse(
            [$auth, 'logout'],
            [$returnTo]
        );
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
