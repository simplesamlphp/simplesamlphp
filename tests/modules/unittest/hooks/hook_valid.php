<?php

use SimpleSAML\Assert\Assert;
use SimpleSAML\Auth;

/**
 * Hook to for use with unit tests
 *
 * @param array &$data  Some data
 */
function unittest_hook_valid(array &$data)
{
    $data['summary'][] = 'success';
}
