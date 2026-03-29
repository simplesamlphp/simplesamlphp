<?php

declare(strict_types=1);

namespace SimpleSAML;

require_once('../../_include.php');

http_response_code(410);
header('Content-Type: text/plain; charset=UTF-8');
echo "Legacy public/saml2/idp scripts are no longer supported. Use the routed /module/saml endpoints instead.\n";
