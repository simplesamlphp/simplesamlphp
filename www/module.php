<?php

/**
 * This web page receives requests for web-pages hosted by modules, and directs them to
 * the process() handler in the Module class.
 */

declare(strict_types=1);

namespace SimpleSAML;

require_once('_include.php');

Module::process()->send();
