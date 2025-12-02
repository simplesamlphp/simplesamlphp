<?php

declare(strict_types=1);

namespace SimpleSAML\Event\Provider;

use Psr\EventDispatcher\ListenerProviderInterface;
use SimpleSAML\Configuration;
use SimpleSAML\Module;

class ModuleListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, list<callable>> */
    private array $listeners = [];

    public function __construct()
    {
        $configuration = Configuration::getInstance();
        $enabledModules = $configuration->getOptionalArray('module.enable', Module::$core_modules);
        $this->discoverListeners($enabledModules);
    }


    private function discoverListeners(array $enabledModules): void
    {
        foreach ($enabledModules as $moduleName => $enabled) {
            if (!$enabled) {
                continue;
            }

            $listenerDir = dirname(__DIR__, 4) . "/modules/$moduleName/src/Event/Listener";

            if (!is_dir($listenerDir)) {
                continue;
            }

            foreach(glob("{$listenerDir}/*.php") as $file) {
                $className = $this->getClassNameFromFile($file, $moduleName);

                if (!$className || !class_exists($className)) {
                    continue;
                }

                $this->registerListenerClass($className);
            }
        }
    }

    private function registerListenerClass(string $className): void
    {
        $reflection = new \ReflectionClass($className);

        // support named methods with event type hints
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }

            $params = $method->getParameters();
            if (count($params) > 0) {
                $eventType = $params[0]->getType();
                if ($eventType && !$eventType->isBuiltin()) {
                    $eventClass = $eventType->getName();
                    $instance = new $className();
                    $this->addListener($eventClass, [$instance, $method->getName()]);
                }
            }
        }
    }

    public function addListener(string $eventClass, callable $listener, int $priority = 0): void
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = [
            'callable' => $listener,
            'priority' => $priority,
        ];

        // Sort listeners by priority (higher priority first)
        usort($this->listeners[$eventClass], function ($a, $b) {
            return $b['priority'] <=> $a['priority'];
        });
    }

    /**
     * Get the listeners for a specific event type.
     *
     * @param object $event The event object.
     *
     * @return iterable<callable> The listeners for the event type.
     */
    public function getListenersForEvent(object $event): iterable
    {
        $eventClass = get_class($event);

        // check for exact class match
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listenerData) {
                yield $listenerData['callable'];
            }
        }

        // check for parent classes
        foreach (class_parents($event) as $parentClass) {
            if (isset($this->listeners[$parentClass])) {
                foreach ($this->listeners[$parentClass] as $listenerData) {
                    yield $listenerData['callable'];
                }
            }
        }

        // check for implemented interfaces
        foreach (class_implements($event) as $interface) {
            if (isset($this->listeners[$interface])) {
                foreach ($this->listeners[$interface] as $listenerData) {
                    yield $listenerData['callable'];
                }
            }
        }
    }

    private function getClassNameFromFile(string $file, string $moduleName): ?string
    {
        $basename = basename($file, '.php');
        $className = "SimpleSAML\\Module\\{$moduleName}\\Event\\Listener\\{$basename}";
        return $className;
    }
}
