<?php
/**
 * Hook to add the sanitycheck link to the config page.
 *
 * @param \SimpleSAML\XHTML\Template The template that we should alter in this hook.
 */
function sanitycheck_hook_configpage(\SimpleSAML\XHTML\Template &$template)
{
    $template->data['links_config']['sanitycheck'] = [
        'href' => SimpleSAML\Module::getModuleURL('sanitycheck/index.php'),
        'text' => '{sanitycheck:strings:link_sanitycheck}',
    ];

    $config = \SimpleSAML\Configuration::getInstance();
    if ($config->getBoolean('usenewui', false)) {
        $template->getLocalization()->addModuleDomain('sanitycheck');
    }
}
