<?php


/**
 * Auth-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class SimpleSAML_Utils_Auth
{

    /**
     * Check whether the current user is admin.
     *
     * @return boolean True if the current user is an admin user, false otherwise.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function isAdmin()
    {
        $session = SimpleSAML_Session::getSessionFromRequest();
        return $session->isValid('admin') || $session->isValid('login-admin');
    }
}