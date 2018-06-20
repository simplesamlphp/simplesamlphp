<?php

/**
 *
 *
 * @author Mathias Meisfjordskar, University of Oslo.
 *         <mathias.meisfjordskar@usit.uio.no>
 * @package SimpleSAMLphp
 */

$params = array(
    'secure' => FALSE,
    'httponly' => TRUE,
);
\SimpleSAML\Utils\HTTP::setCookie('NEGOTIATE_AUTOLOGIN_DISABLE_PERMANENT', NULL, $params, FALSE);

$globalConfig = \SimpleSAML\Configuration::getInstance();
$session = \SimpleSAML\Session::getSessionFromRequest();
$session->setData('negotiate:disable', 'session', FALSE, 24*60*60);
$t = new \SimpleSAML\XHTML\Template($globalConfig, 'negotiate:enable.php');
$t->data['url'] = \SimpleSAML\Module::getModuleURL('negotiate/disable.php');
$t->show();
