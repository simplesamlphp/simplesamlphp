<?php

require_once('../_include.php');

$httpUtils = new \SimpleSAML\Utils\HTTP();
$httpUtils->redirectTrustedURL(\SimpleSAML\Module::getModuleURL('admin/'));
