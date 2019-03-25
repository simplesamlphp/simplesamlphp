<?php

namespace SimpleSAML\Auth;

@trigger_error(sprintf('Using the "SimpleSAML\Auth\LDAP" class is deprecated, use "SimpleSAML\Module\ldap\Auth\Ldap" instead.'), E_USER_DEPRECATED);

/**
 * @deprecated To be removed in 2.0
 */
if (class_exists('\SimpleSAML\Module\ldap\Auth\Ldap')) {
    class LDAP extends \SimpleSAML\Module\ldap\Auth\Ldap
    {
        public function __construct()
        {
            parent::__construct();
        }
    }
} else {
    throw new \Exception('Missing ldap-module');
}
