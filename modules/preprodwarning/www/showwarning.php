<?php

/**
 * This script displays a page to the user, which requests that the user
 * authorizes the release of attributes.
 *
 * @package SimpleSAMLphp
 */

\SimpleSAML\Logger::info('PreProdWarning - Showing warning to user');

if (!array_key_exists('StateId', $_REQUEST)) {
    throw new \SimpleSAML\Error\BadRequest('Missing required StateId query parameter.');
}
$id = $_REQUEST['StateId'];
$state = \SimpleSAML\Auth\State::loadState($id, 'warning:request');

if (array_key_exists('yes', $_REQUEST)) {
    // The user has pressed the yes-button
    \SimpleSAML\Auth\ProcessingChain::resumeProcessing($state);
}

$globalConfig = \SimpleSAML\Configuration::getInstance();

$t = new \SimpleSAML\XHTML\Template($globalConfig, 'preprodwarning:warning.php');
$t->data['yesTarget'] = \SimpleSAML\Module::getModuleURL('preprodwarning/showwarning.php');
$t->data['yesData'] = ['StateId' => $id];
$t->show();
