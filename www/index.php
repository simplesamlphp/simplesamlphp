<?php

require_once('_include.php');

\SimpleSAML\Utils\HTTP::redirectTrustedURL(SimpleSAML\Module::getModuleURL('core/frontpage_welcome.php'));
