#!/usr/bin/env php
<?php

declare(strict_types=1);

// This is the base directory of the SimpleSAMLphp installation
$baseDir = dirname(__FILE__, 2);

// Add library autoloader and configuration
require_once $baseDir . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . '_autoload.php';

// This is the config dir of the SimpleSAMLphp installation
$configDir = (new \SimpleSAML\Utils\Config())->getConfigDir();

require_once $configDir . DIRECTORY_SEPARATOR . 'config.php';

echo "Initializing Metadata Database..." . PHP_EOL;

# Iterate through configured metadata sources and ensure
# that a PDO source exists.
foreach ($config['metadata.sources'] as $source) {
    # If pdo is configured, create the new handler and initialize the DB.
    if ($source['type'] === "pdo") {
        $metadataStorageHandler = new \SimpleSAML\Metadata\MetaDataStorageHandlerPdo($source);
        $result = $metadataStorageHandler->initDatabase();

        if ($result === false) {
            echo "Failed to initialize metadata database." . PHP_EOL;
        } else {
            echo "Successfully initialized metadata database." . PHP_EOL;
        }
    }
}
