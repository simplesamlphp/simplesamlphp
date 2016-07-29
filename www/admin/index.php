<?php

require_once('../_include.php');

// Load SimpleSAMLphp, configuration
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

// Check if valid local session exists..
//SimpleSAML\Utils\Auth::requireAdmin();

$adminpages = array(
    'hostnames.php' => 'Diagnostics on hostname, port and protocol',
    'phpinfo.php' => 'PHP info',
    '../module.php/sanitycheck/index.php' => 'Sanity check of your SimpleSAMLphp setup',
    'sandbox.php' => 'Sandbox for testing changes to layout and css',
);

$template = new SimpleSAML_XHTML_Template($config, 'index.php');

$template->data['pagetitle'] = 'Admin';
$template->data['adminpages'] = $adminpages;
$template->data['remaining']  = $session->getAuthData('admin', 'Expire') - time();
$template->data['valid'] = 'na';
$template->data['logout'] = null;

$template->show();
