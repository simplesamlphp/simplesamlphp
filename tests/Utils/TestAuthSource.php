<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Auth\Source;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestAuthSource extends Source
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request  The current request
     * @param array &$state
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function authenticate(Request $request, array &$state): ?Response
    {
        return null;
    }
}
