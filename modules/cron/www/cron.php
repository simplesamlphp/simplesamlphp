<?php

$config = \SimpleSAML\Configuration::getInstance();
$cronconfig = \SimpleSAML\Configuration::getConfig('module_cron.php');

if (!is_null($cronconfig->getValue('key'))) {
    if ($_REQUEST['key'] !== $cronconfig->getValue('key')) {
        \SimpleSAML\Logger::error('Cron - Wrong key provided. Cron will not run.');
        exit;
    }
}

$cron = new \SimpleSAML\Module\cron\Cron();
if (!$cron->isValidTag($_REQUEST['tag'])) {
    SimpleSAML\Logger::error('Cron - Illegal tag ['.$_REQUEST['tag'].'].');
    exit;
}

$url = \SimpleSAML\Utils\HTTP::getSelfURL();
$time = date(DATE_RFC822);

$croninfo = $cron->runTag($_REQUEST['tag']);
$summary = $croninfo['summary'];

if ($cronconfig->getValue('sendemail', true) && count($summary) > 0) {
    $mail = new \SimpleSAML\Utils\EMail('SimpleSAMLphp cron report');
    $mail->setData(['url' => $url, 'tag' => $croninfo['tag'], 'summary' => $croninfo['summary']]);
    $mail->send();
}

if (isset($_REQUEST['output']) && $_REQUEST['output'] == "xhtml") {
    $t = new \SimpleSAML\XHTML\Template($config, 'cron:croninfo-result.php', 'cron:cron');
    $t->data['tag'] = $croninfo['tag'];
    $t->data['time'] = $time;
    $t->data['url'] = $url;
    $t->data['summary'] = $summary;
    $t->show();
}
