<?php
/**
 * Extend IdP session and cookies.
 */
class sspmod_core_Auth_Process_ExtendIdPSession extends SimpleSAML_Auth_ProcessingFilter
{
    /**
     * Apply filter to extend IdP session and cookies.
     *
     * @param array &$request  The current request
     */
    public function process(array &$request) {
        if (empty($request['Expire']) || empty($request['Authority'])) {
            return;
        }

        $now = time();
        $delta = $request['Expire'] - $now;

        $globalConfig = SimpleSAML_Configuration::getInstance();
        $sessionDuration = $globalConfig->getInteger('session.duration', 8*60*60);

        // Extend only if half of session duration already passed
        if ($delta >= ($sessionDuration * 0.5)) {
            return;
        }

        // Update authority expire time
        $session = SimpleSAML_Session::getSessionFromRequest();
        $session->setAuthorityExpire($request['Authority']);

        /* Update session cookies duration */

        /* If remember me is active */
        $rememberMeExpire = $session->getRememberMeExpire();
        if (!empty($request['RememberMe']) && $rememberMeExpire !== null && $globalConfig->getBoolean('session.rememberme.enable', false)) {
            $session->setRememberMeExpire();
            return;
        }

        /* Or if session lifetime is more than zero */
        $sessionHandler = \SimpleSAML\SessionHandler::getSessionHandler();
        $cookieParams = $sessionHandler->getCookieParams();
        if ($cookieParams['lifetime'] > 0) {
            $session->updateSessionCookies();
        }
    }
}
