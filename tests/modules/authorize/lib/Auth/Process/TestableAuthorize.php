<?php
/**
 * Subclass authorize filter to make it unit testable.
 */

namespace SimpleSAML\Test\Module\authorize\Auth\Process;

use SimpleSAML\Module\authorize\Auth\Process\Authorize;

class TestableAuthorize extends Authorize
{
    /**
     * Override the redirect behavior since its difficult to test
     * @param array $request the state
     */
    protected function unauthorized(&$request)
    {
        $request['NOT_AUTHORIZED'] = true;
    }
}
