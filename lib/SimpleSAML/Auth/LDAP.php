<?php

namespace SimpleSAML\Auth;

@trigger_error(sprintf('Using the "SimpleSAML\Auth\LDAP" class is deprecated, use "SimpleSAML\Module\ldap\Auth\Ldap" instead.'), E_USER_DEPRECATED);

/**
 * @deprecated To be removed in 2.0
 */
if (class_exists('\SimpleSAML\Module\ldap\Auth\Ldap')) {
    class LDAP extends \SimpleSAML\Module\ldap\Auth\Ldap
    {
        /**
         * Private constructor restricts instantiation to getInstance().
         *
         * @param string $hostname
         * @param bool $enable_tls
         * @param bool $debug
         * @param int $timeout
         * @param int $port
         * @param bool $referrals
         * @psalm-suppress NullArgument
         */
        public function __construct(
            $hostname,
            $enable_tls = true,
            $debug = false,
            $timeout = 0,
            $port = 389,
            $referrals = true
        ) {
            parent::__construct($hostname, $enable_tls, $debug, $timeout, $port, $referrals);
        }
    }
} else {
    throw new \Exception('Missing ldap-module');
}
