<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Auth\Source;
use Symfony\Component\HttpFoundation\{Request, Response};

class TestAuthSource extends Source
{
    /**
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array &$state
     */
    public function authenticate(Request $request, array &$state): ?Response
    {
        return null;
    }
}
