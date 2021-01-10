<?php

use SimpleSAML\Assert\Assert;
use SimpleSAML\Module;

/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 */
function cron_hook_frontpage(array &$links): void
{
    Assert::keyExists($links, 'links');

    $links['config'][] = [
        'href' => Module::getModuleURL('cron/info'),
        'text' => '{cron:cron:link_cron}',
    ];
}
