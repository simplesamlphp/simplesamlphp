<?php

use Webmozart\Assert\Assert;

/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 * @return void
 */
function cron_hook_cron(&$croninfo)
{
    Assert::isArray($croninfo);
    Assert::keyExists($croninfo, 'summary');
    Assert::keyExists($croninfo, 'tag');

    $cronconfig = \SimpleSAML\Configuration::getConfig('module_cron.php');

    if ($cronconfig->getValue('debug_message', true)) {
        $croninfo['summary'][] = 'Cron did run tag [' . $croninfo['tag'] . '] at ' . date(DATE_RFC822);
    }
}
