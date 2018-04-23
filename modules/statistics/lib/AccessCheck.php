<?php

/**
 * Class implementing the access checker function for the statistics module.
 *
 * @package SimpleSAMLphp
 */
class sspmod_statistics_AccessCheck
{
    /**
     * Check that the user has access to the statistics.
     *
     * If the user doesn't have access, send the user to the login page.
     */
    public static function checkAccess(SimpleSAML_Configuration $statconfig)
    {
        $protected = $statconfig->getBoolean('protected', false);
        $authsource = $statconfig->getString('auth', null);
        $allowedusers = $statconfig->getValue('allowedUsers', null);
        $useridattr = $statconfig->getString('useridattr', 'eduPersonPrincipalName');

        $acl = $statconfig->getValue('acl', null);
        if ($acl !== null && !is_string($acl) && !is_array($acl)) {
            throw new SimpleSAML_Error_Exception('Invalid value for \'acl\'-option. Should be an array or a string.');
        }

        if (!$protected) {
            return;
        }

        if (SimpleSAML\Utils\Auth::isAdmin()) {
            // User logged in as admin. OK.
            SimpleSAML\Logger::debug('Statistics auth - logged in as admin, access granted');
            return;
        }

        if (!isset($authsource)) {
            // If authsource is not defined, init admin login.
            SimpleSAML\Utils\Auth::requireAdmin();
        }

        // We are using an authsource for login.

        $as = new \SimpleSAML\Auth\Simple($authsource);
        $as->requireAuth();

        // User logged in with auth source.
        SimpleSAML\Logger::debug('Statistics auth - valid login with auth source [' . $authsource . ']');

        // Retrieving attributes
        $attributes = $as->getAttributes();

        if (!empty($allowedusers)) {
            // Check if userid exists
            if (!isset($attributes[$useridattr][0])) {
                throw new Exception('User ID is missing');
            }

            // Check if userid is allowed access..
            if (in_array($attributes[$useridattr][0], $allowedusers, true)) {
                SimpleSAML\Logger::debug('Statistics auth - User granted access by user ID [' . $attributes[$useridattr][0] . ']');
                return;
            }
            SimpleSAML\Logger::debug('Statistics auth - User denied access by user ID [' . $attributes[$useridattr][0] . ']');
        } else {
            SimpleSAML\Logger::debug('Statistics auth - no allowedUsers list.');
        }

        if (!is_null($acl)) {
            $acl = new sspmod_core_ACL($acl);
            if ($acl->allows($attributes)) {
                SimpleSAML\Logger::debug('Statistics auth - allowed access by ACL.');
                return;
            }
            SimpleSAML\Logger::debug('Statistics auth - denied access by ACL.');
        } else {
            SimpleSAML\Logger::debug('Statistics auth - no ACL configured.');
        }
        throw new SimpleSAML_Error_Exception('Access denied to the current user.');
    }
}
