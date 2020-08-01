<?php

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module;
use SimpleSAML\XHTML\Template;

/**
 * Hook to add the cron module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template &$template The template that we should alter in this hook.
 * @return void
 */
function cron_hook_configpage(Template &$template): void
{
    $template->data['links']['cron'] = [
        'href' => Module::getModuleURL('cron/croninfo.php'),
        'text' => Translate::noop('Cron module information page'),
    ];
    $template->getLocalization()->addModuleDomain('cron');
}
