<?php
namespace SimpleSAML\Utils;

/**
 * Auth-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class Auth
{

    /**
     * Retrieve a admin login URL.
     *
     * @param string|NULL $returnTo The URL the user should arrive on after admin authentication. Defaults to null.
     *
     * @return string A URL which can be used for admin authentication.
     */
    public static function getAdminLoginURL($returnTo = null)
    {
        assert('is_string($returnTo) || is_null($returnTo)');

        if ($returnTo === null) {
            $returnTo = \SimpleSAML_Utilities::selfURL();
        }

        return \SimpleSAML_Module::getModuleURL('core/login-admin.php', array('ReturnTo' => $returnTo));
    }

    /**
     * Check whether the current user is admin.
     *
     * @return boolean True if the current user is an admin user, false otherwise.
     *
     * @author Olav Morken, UNINETT AS <olav.morken@uninett.no>
     */
    public static function isAdmin()
    {
        $session = \SimpleSAML_Session::getSessionFromRequest();
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
        if (self::isAdmin()) {
            return;
        }

        // not authenticated as admin user, start authentication
        if (\SimpleSAML_Auth_Source::getById('admin') !== null) {
            $as = new \SimpleSAML_Auth_Simple('admin');
            $as->login();
        } else {
            throw new \SimpleSAML_Error_Exception('Cannot find "admin" auth source, and admin privileges are required.');
        }
    }
}