<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "The module.php entry point is no longer supported.\n";
