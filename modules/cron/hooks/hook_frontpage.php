<?php

use Webmozart\Assert\Assert;

/**
 * Hook to add the modinfo module to the frontpage.
 *
 * @param array &$links  The links on the frontpage, split into sections.
 * @return void
 */
function cron_hook_frontpage(&$links)
{
    Assert::isArray($links);
    Assert::keyExists($links, 'links');

    $links['config'][] = [
        'href' => SimpleSAML\Module::getModuleURL('cron/croninfo.php'),
        'text' => '{cron:cron:link_cron}',
    ];
}
