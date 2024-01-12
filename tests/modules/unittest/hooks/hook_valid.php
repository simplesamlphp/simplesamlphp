<?php

declare(strict_types=1);

/**
 * Hook to for use with unit tests
 *
 * @param array &$data  Some data
 */
function unittest_hook_valid(array &$data): void
{
    $data['summary'][] = 'success';
}
