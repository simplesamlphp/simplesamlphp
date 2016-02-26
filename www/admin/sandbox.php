<?php

require_once('../_include.php');

// Load SimpleSAMLphp, configuration
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

// Check if valid local session exists..
//SimpleSAML\Utils\Auth::requireAdmin();

$template = new SimpleSAML_XHTML_Template($config, 'sandbox.php');

$template->data['pagetitle'] = 'Sandbox';
$template->data['sometext'] = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Donec a diam lectus. Sed sit amet ipsum mauris. Maecenas congue ligula ac quam viverra nec consectetur ante hendrerit. Donec et mollis dolor. Praesent et diam eget libero egestas mattis sit amet vitae augue. Nam tincidunt congue enim, ut porta lorem lacinia consectetur.';
$template->data['remaining']  = $session->getAuthData('admin', 'Expire') - time();
$template->data['logout'] = null;

$template->show();
