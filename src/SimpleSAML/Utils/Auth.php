<?php

declare(strict_types=1);

namespace SimpleSAML\Utils;

use SimpleSAML\{Auth as Authentication, Error, Module, Session};
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth-related utility methods.
 *
 * @package SimpleSAMLphp
 */
class Auth
{
    /**
     * Retrieve an admin logout URL.
     *
     * @param string|NULL $returnTo The URL the user should arrive on after admin authentication. Defaults to null.
     *
     * @return string A URL which can be used for logging out.
     * @throws \InvalidArgumentException If $returnTo is neither a string nor null.
     */
    public function getAdminLogoutURL(?string $returnTo = null): string
    {
        $as = new Authentication\Simple('admin');
        return $as->getLogoutURL($returnTo);
    }


    /**
     * Check whether the current user is admin.
     *
     * @return boolean True if the current user is an admin user, false otherwise.
     *
     */
    public function isAdmin(): bool
    {
        $session = Session::getSessionFromRequest();
        return $session->isValid('admin') || $session->isValid('login-admin');
    }


    /**
     * Require admin access to the current page.
     *
     * This is a helper function for limiting a page to those with administrative access. It will redirect the user to
     * a login page if the current user doesn't have admin access.
     *
     * @throws \SimpleSAML\Error\Exception If no "admin" authentication source was configured.
     *
     */
    public function requireAdmin(): ?Response
    {
        if ($this->isAdmin()) {
            return null;
        }

        // not authenticated as admin user, start authentication
        if (Authentication\Source::getById('admin') !== null) {
            $as = new Authentication\Simple('admin');
            return $as->login();
        } else {
            throw new Error\Exception(
                'Cannot find "admin" auth source, and admin privileges are required.',
            );
        }
    }
}
