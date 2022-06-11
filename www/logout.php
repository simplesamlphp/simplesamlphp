<?php

declare(strict_types=1);

namespace SimpleSAML;

use SimpleSAML\XHTML\Template;

use function array_key_exists;

require_once('_include.php');

$config = Configuration::getInstance();
$httpUtils = new Utils\HTTP();

if (array_key_exists('link_href', $_REQUEST)) {
    $link = $httpUtils->checkURLAllowed($_REQUEST['link_href']);
} else {
    $link = 'index.php';
}

if (array_key_exists('link_text', $_REQUEST)) {
    $text = $_REQUEST['link_text'];
} else {
    $text = '{logout:default_link_text}';
}

$t = new Template($config, 'logout.twig');
$t->data['link'] = $link;
$t->data['text'] = $text;
$t->send();
