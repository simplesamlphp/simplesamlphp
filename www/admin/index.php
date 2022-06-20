<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('../_include.php');

$httpUtils = new Utils\HTTP();

$redirect = Module::getModuleURL('admin/');
$httpUtils->redirectTrustedURL($redirect);
