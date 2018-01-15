<?php

namespace SimpleSAML\Auth;

use SimpleSAML_Auth_Source;

interface SourceFactory
{
    /**
     * @param array $info
     * @param array $config
     * @return SimpleSAML_Auth_Source
     */
    public function create(array $info, array $config);
}
