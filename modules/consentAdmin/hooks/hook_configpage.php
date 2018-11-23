<?php
/**
 * Hook to add the consentAdmin module to the config page.
 *
 * @param \SimpleSAML\XHTML\Template The template that we should alter in this hook.
 */

function consentAdmin_hook_configpage(\SimpleSAML\XHTML\Template &$template)
{
    $template->data['links_config']['consentAdmin'] = [
        'href' => SimpleSAML\Module::getModuleURL('consentAdmin/consentAdmin.php'),
        'text' => '{consentAdmin:consentadmin:link_consentAdmin}',
    ];

    $config = \SimpleSAML\Configuration::getInstance();
    if ($config->getBoolean('usenewui', false)) {
        $template->getLocalization()->addModuleDomain('consentAdmin');
    }
}
