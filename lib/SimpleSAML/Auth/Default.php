<?php

namespace SimpleSAML\Auth;

/**
 * Implements the default behaviour for authentication.
 *
 * This class contains an implementation for default behaviour when authenticating. It will
 * save the session information it got from the authentication client in the users session.
 *
 * @author Olav Morken, UNINETT AS.
 * @package SimpleSAMLphp
 *
 * @deprecated This class will be removed in SSP 2.0.
 */

class DefaultAuth
{
    /**
     * @deprecated This method will be removed in SSP 2.0. Use Source::initLogin() instead.
     */
    public static function initLogin(
        $authId,
        $return,
        $errorURL = null,
        array $params = []
    ) {

        $as = self::getAuthSource($authId);
        $as->initLogin($return, $errorURL, $params);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0. Please use
     * State::getPersistentAuthData() instead.
     */
    public static function extractPersistentAuthState(array &$state)
    {
        return State::getPersistentAuthData($state);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0. Please use Source::loginCompleted() instead.
     */
    public static function loginCompleted($state)
    {
        Source::loginCompleted($state);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0.
     */
    public static function initLogoutReturn($returnURL, $authority)
    {
        assert(is_string($returnURL));
        assert(is_string($authority));

        $session = \SimpleSAML\Session::getSessionFromRequest();

        $state = $session->getAuthData($authority, 'LogoutState');
        $session->doLogout($authority);

        $state['\SimpleSAML\Auth\DefaultAuth.ReturnURL'] = $returnURL;
        $state['LogoutCompletedHandler'] = [get_class(), 'logoutCompleted'];

        $as = Source::getById($authority);
        if ($as === null) {
            // The authority wasn't an authentication source...
            self::logoutCompleted($state);
        }

        $as->logout($state);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0.
     */
    public static function initLogout($returnURL, $authority)
    {
        assert(is_string($returnURL));
        assert(is_string($authority));

        self::initLogoutReturn($returnURL, $authority);

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($returnURL);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0.
     */
    public static function logoutCompleted($state)
    {
        assert(is_array($state));
        assert(array_key_exists('\SimpleSAML\Auth\DefaultAuth.ReturnURL', $state));

        \SimpleSAML\Utils\HTTP::redirectTrustedURL($state['\SimpleSAML\Auth\DefaultAuth.ReturnURL']);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0. Please use Source::logoutCallback() instead.
     */
    public static function logoutCallback($state)
    {
        Source::logoutCallback($state);
    }


    /**
     * @deprecated This method will be removed in SSP 2.0. Please use
     * \SimpleSAML\Module\saml\Auth\Source\SP::handleUnsolicitedAuth() instead.
     */
    public static function handleUnsolicitedAuth($authId, array $state, $redirectTo)
    {
        \SimpleSAML\Module\saml\Auth\Source\SP::handleUnsolicitedAuth($authId, $state, $redirectTo);
    }


    /**
     * Return an authentication source by ID.
     *
     * @param string $id The id of the authentication source.
     * @return Source The authentication source.
     * @throws \Exception If the $id does not correspond with an authentication source.
     */
    private static function getAuthSource($id)
    {
        $as = Source::getById($id);
        if ($as === null) {
            throw new \Exception('Invalid authentication source: '.$id);
        }
        return $as;
    }
}
