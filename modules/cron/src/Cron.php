<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron;

use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Event\Dispatcher\ModuleEventDispatcherFactory;
use SimpleSAML\Logger;
use SimpleSAML\Module;
use SimpleSAML\Module\cron\Event\CronEvent;

use function in_array;
use function is_null;

/**
 * Handles interactions with SSP's cron system/hooks.
 */
class Cron
{
    /**
     * The configuration for the Cron module
     * @var \SimpleSAML\Configuration
     */
    private Configuration $cronconfig;

    private readonly EventDispatcherInterface $eventDispatcher;


    /**
     * @param \SimpleSAML\Configuration $cronconfig The cron configuration to use. If not specified defaults
     * to `config/module_cron.php`
     * @throws \Exception
     */
    public function __construct(?Configuration $cronconfig = null)
    {
        if ($cronconfig == null) {
            $cronconfig = Configuration::getConfig('module_cron.php');
        }
        $this->cronconfig = $cronconfig;

        $this->eventDispatcher = ModuleEventDispatcherFactory::getInstance();
    }


    /**
     * Invoke the cron hook for the given tag
     * @param string $tag The tag to use. Must be valid in the cronConfig
     * @return array the tag, and summary information from the run.
     * @throws \Exception If an invalid tag specified
     */
    public function runTag(string $tag): array
    {
        if (!$this->isValidTag($tag)) {
            throw new Exception("Invalid cron tag '$tag''");
        }

        $summary = [];
        $croninfo = [
            'summary' => &$summary,
            'tag' => $tag,
        ];

        // DEPRECATED: call the hook infrastructure
        Module::callHooks('cron', $croninfo);
        // NEW: dispatch the cron event
        /** @var CronEvent $event */
        $event = $this->eventDispatcher->dispatch(new CronEvent($tag));
        // merge results from the event into $croninfo. Can be removed when hook infrastructure is removed.
        $croninfo['summary'] = array_merge($croninfo['summary'], array_map(
            fn ($result) => $result['message'],
            $event->getResults()
        ));
        Assert::isArray($croninfo);

        foreach ($summary as $s) {
            Logger::debug('Cron - Summary: ' . $s);
        }

        /** @psalm-suppress NullableReturnStatement */
        return $croninfo;
    }


    /**
     * @param string $tag
     * @return bool
     * @throws \SimpleSAML\Assert\AssertionFailedException
     */
    public function isValidTag(string $tag): bool
    {
        if (!is_null($this->cronconfig->getValue('allowed_tags'))) {
            return in_array($tag, $this->cronconfig->getArray('allowed_tags'), true);
        }
        return true;
    }
}
