<?php

declare(strict_types=1);

namespace SimpleSAML\Test\Utils;

use SimpleSAML\Auth\Source;

class TestAuthSource extends Source
{
    /**
     * @param array &$state
     */
    public function authenticate(array &$state): void
    {
    }
}
