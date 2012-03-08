<?php

/**
 *
 *
 * @author Mathias Meisfjordskar, University of Oslo.
 *         <mathias.meisfjordskar@usit.uio.no>
 * @package simpleSAMLphp
 * @version $Id$
 */

$globalConfig = SimpleSAML_Configuration::getInstance();
setcookie('NEGOTIATE_AUTOLOGIN_DISABLE_PERMANENT', 'True', mktime(0,0,0,1,1,2038), '/', SimpleSAML_Utilities::getSelfHost(), FALSE, TRUE);
$session = SimpleSAML_Session::getInstance();
$session->setData('negotiate:disable', 'session', FALSE, 24*60*60);
$t = new SimpleSAML_XHTML_Template($globalConfig, 'negotiate:disable.php');
$t->show();
