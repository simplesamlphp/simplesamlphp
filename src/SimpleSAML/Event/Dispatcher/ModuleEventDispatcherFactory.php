<?php 

declare(strict_types=1);

namespace SimpleSAML\Event\Dispatcher;

use SimpleSAML\Configuration;
use SimpleSAML\Event\Provider\ModuleListenerProvider;

class ModuleEventDispatcherFactory
{
    private static ?EventDispatcher $instance = null;

    public static function getInstance(): EventDispatcher
    {
        if (self::$instance === null) {
            $config = Configuration::getInstance();
            $enabledModules = $config->getArray('modules.enabled', []);

            $provider = new ModuleListenerProvider($enabledModules);
            self::$instance = new EventDispatcher($provider);
        }

        return self::$instance;
    }
}