<?php

use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;

/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function cron_hook_cron(array &$croninfo): void
{
    Assert::keyExists($croninfo, 'summary');
    Assert::keyExists($croninfo, 'tag');

    $cronconfig = Configuration::getConfig('module_cron.php');

    if ($cronconfig->getOptionalBoolean('debug_message', true)) {
        $croninfo['summary'][] = 'Cron did run tag [' . $croninfo['tag'] . '] at ' . date(DATE_RFC822);
    }
}
