<?php 

declare(strict_types=1);

namespace SimpleSAML\Event\Dispatcher;

use SimpleSAML\Event\Provider\ModuleListenerProvider;

class ModuleEventDispatcherFactory
{
    private static ?EventDispatcher $instance = null;

    public static function getInstance(): EventDispatcher
    {
        if (self::$instance === null) {
            $provider = new ModuleListenerProvider();
            self::$instance = new EventDispatcher($provider);
        }

        return self::$instance;
    }

    public static function testingRemakeInstance(): EventDispatcher
    {
        $provider = new ModuleListenerProvider();
        self::$instance = new EventDispatcher($provider);
        return self::$instance;
    }
    
}
