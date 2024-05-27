<?php

declare(strict_types=1);

namespace SimpleSAML\Auth;

use SimpleSAML\{Configuration, Session};

/**
 * Factory class to get instances of \SimpleSAML\Auth\Simple for a given authentication source.
 */
class AuthenticationFactory
{
    /**
     * @param \SimpleSAML\Configuration $config
     * @param \SimpleSAML\Session $session
     */
    public function __construct(
        protected Configuration $config,
        protected Session $session,
    ) {
    }


    /**
     * Create a new instance of \SimpleSAML\Auth\Simple for the given authentication source.
     *
     * @param string $as The identifier of the authentication source, as indexed in the authsources.php configuration
     * file.
     *
     * @return \SimpleSAML\Auth\Simple
     */
    public function create(string $as): Simple
    {
        return new Simple($as, $this->config, $this->session);
    }
}
