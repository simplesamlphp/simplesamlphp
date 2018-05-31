<?php

namespace SimpleSAML\Module\consent;

/**
 * Class defining the logout completed handler for the consent page.
 *
 * @package SimpleSAMLphp
 */

class Logout
{
    public static function postLogout(\SimpleSAML\IdP $idp, array $state)
    {
        $url = \SimpleSAML\Module::getModuleURL('consent/logout_completed.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url);
    }
}
