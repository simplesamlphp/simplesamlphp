<?php
/**
 * Hook to add the statistics module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template &$template The template that we should alter in this hook.
 */
function statistics_hook_configpage(\SimpleSAML\XHTML\Template &$template)
{
    $template->data['links_config']['statistics'] = [
        'href' => SimpleSAML\Module::getModuleURL('statistics/showstats.php'),
        'text' => '{statistics:statistics:link_statistics}',
    ];
    $template->data['links_config']['statisticsmeta'] = [
        'href' => SimpleSAML\Module::getModuleURL('statistics/statmeta.php'),
        'text' => '{statistics:statistics:link_statistics_metadata}',
        'shorttext' => ['en' => 'Statistics metadata', 'no' => 'Statistikk metadata'],
    ];

    $config = \SimpleSAML\Configuration::getInstance();
    if ($config->getBoolean('usenewui', false)) {
        $template->getLocalization()->addModuleDomain('statistics');
    }
}
