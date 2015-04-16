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

    /**
     * Require admin access to the current page.
     *
     * This is a helper function for limiting a page to those with administrative access. It will redirect the user to
     * a login page if the current user doesn't have admin access.
     *
     * @return void This function will only return if the user is admin.
     * @throws SimpleSAML_Error_Exception If no "admin" authentication source was configured.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     * @author Jaime Perez, UNINETT AS <jaime.perez@uninett.no>
     */
    public static function requireAdmin()
    {
        if (SimpleSAML_Utils_Auth::isAdmin()) {
            return;
        }

        // not authenticated as admin user, start authentication
        if (SimpleSAML_Auth_Source::getById('admin') !== null) {
            $as = new SimpleSAML_Auth_Simple('admin');
            $as->login();
        } else {
            throw new SimpleSAML_Error_Exception('Cannot find "admin" auth source, and admin privileges are required.');
        }
    }
}