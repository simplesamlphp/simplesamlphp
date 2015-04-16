<?php

require_once('../_include.php');

/* Make sure that the user has admin access rights. */
SimpleSAML_Utils_Auth::requireAdmin();

phpinfo();
