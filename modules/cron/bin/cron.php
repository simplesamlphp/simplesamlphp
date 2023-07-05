#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script can be used to invoke cron jobs from cli.
 * You most likely want to execute as the user running your webserver.
 * Example:  su -s "/bin/sh" -c "php /var/simplesamlphp/modules/cron/bin/cron.php -t hourly" apache
 */

// This is the base directory of the SimpleSAMLphp installation
$baseDir = dirname(__FILE__, 4);

// Add library autoloader.
require_once($baseDir . '/src/_autoload.php');

if (!SimpleSAML\Module::isModuleEnabled('cron')) {
    echo "You need to enable the cron module before this script can be used.\n";
    echo "You can enable it by editing your config.php file. Example:\n";
    echo '  echo \'$config["module.enable"]["cron"] = true;\' >> "' . $baseDir . '/config/config.php' . "\"\n";
    exit(1);
}

$options = getopt("t:");
if (function_exists('posix_getuid') && posix_getuid() === 0) {
    echo "Running as root is discouraged. Some cron jobs will generate files that would have the wrong ownership.\n";
    echo 'Suggested invocation: su -s "/bin/sh" -c "php /var/simplesamlphp/modules/cron/bin/cron.php -t hourly" apache';
    exit(3);
}

if (!array_key_exists('t', $options)) {
    echo "You must provide a tag (-t) option";
    exit(2);
}

/** @psalm-var string $tag */
$tag = $options['t'];
$cron = new SimpleSAML\Module\cron\Cron();
if (!$cron->isValidTag($tag)) {
    echo "Invalid tag option '$tag' . \n";
    exit(2);
}

$cronInfo = $cron->runTag($tag);

print_r($cronInfo);
exit(0);
