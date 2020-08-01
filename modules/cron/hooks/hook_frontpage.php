<?php

use SimpleSAML\Assert\Assert;
use SimpleSAML\Module;

/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 * @return void
 */
function cron_hook_frontpage(array &$links): void
{
    Assert::keyExists($links, 'links');

    $links['config'][] = [
        'href' => Module::getModuleURL('cron/croninfo.php'),
        'text' => '{cron:cron:link_cron}',
    ];
}
