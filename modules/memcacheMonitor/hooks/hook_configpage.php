<?php
/**
 * Hook to add the memcacheMonitor module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template &$template The template that we should alter in this hook.
 */

function memcacheMonitor_hook_configpage(\SimpleSAML\XHTML\Template &$template)
{
    $template->data['links_config']['memcacheMonitor'] = [
        'href' => SimpleSAML\Module::getModuleURL('memcacheMonitor/memcachestat.php'),
        'text' => '{memcacheMonitor:memcachestat:link_memcacheMonitor}',
    ];

    $config = \SimpleSAML\Configuration::getInstance();
    if ($config->getBoolean('usenewui', false)) {
        $template->getLocalization()->addModuleDomain('memcacheMonitor');
    }
}
