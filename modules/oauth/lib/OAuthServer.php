<?php

namespace SimpleSAML\Module\oauth;

require_once(dirname(dirname(__FILE__)).'/libextinc/OAuth.php');

/**
 * OAuth Provider implementation..
 *
 * @author Andreas Åkre Solberg, <andreas.solberg@uninett.no>, UNINETT AS.
 * @package SimpleSAMLphp
 */

class OAuthServer extends \OAuthServer
{
    public function __construct($store)
    {
        parent::__construct($store);
    }

    public function get_signature_methods()
    {
        return $this->signature_methods;
    }
}
