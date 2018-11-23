<?php
/**
 * Hook to add the cron module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template &$template The template that we should alter in this hook.
 */

function cron_hook_configpage(\SimpleSAML\XHTML\Template &$template)
{
    $template->data['links_config']['cron'] = [
        'href' => SimpleSAML\Module::getModuleURL('cron/croninfo.php'),
        'text' => '{cron:cron:link_cron}',
    ];

    $config = \SimpleSAML\Configuration::getInstance();
    if ($config->getBoolean('usenewui', false)) {
        $template->getLocalization()->addModuleDomain('cron');
    }
}
