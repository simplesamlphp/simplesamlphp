<?php

/**
 * The _include script registers a autoloader for the SimpleSAMLphp libraries. It also
 * initializes the SimpleSAMLphp config class with the correct path.
 */

require_once('_include.php');

// Load SimpleSAMLphp configuration and metadata
$config = \SimpleSAML\Configuration::getInstance();
$session = \SimpleSAML\Session::getSessionFromRequest();

\SimpleSAML\Utils\Auth::requireAdmin();

$cronconfig = \SimpleSAML\Configuration::getConfig('module_cron.php');

$key = $cronconfig->getValue('key', '');
$tags = $cronconfig->getValue('allowed_tags');

$def = [
    'weekly' => "22 0 * * 0",
    'daily' => "02 0 * * *",
    'hourly' => "01 * * * *",
    'default' => "XXXXXXXXXX",
];

$urls = [];
foreach ($tags as $tag) {
    $urls[] = [
        'href' => \SimpleSAML\Module::getModuleURL('cron/cron.php', ['key' => $key, 'tag' => $tag]),
        'tag' => $tag,
        'int' => (array_key_exists($tag, $def) ? $def[$tag] : $def['default']),
    ];
}

$t = new \SimpleSAML\XHTML\Template($config, 'cron:croninfo.tpl.php', 'cron:cron');
$t->data['urls'] = $urls;
$t->show();
