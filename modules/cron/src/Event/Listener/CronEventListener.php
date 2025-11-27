<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron\Event\Listener;

use SimpleSAML\Configuration;
use SimpleSAML\Module\cron\Event\CronEvent;

class CronEventListener
{
    public function __invoke(CronEvent $event): void
    {
        $cronconfig = Configuration::getConfig('module_cron.php');

        if ($cronconfig->getOptionalBoolean('debug_message', true)) {
            $event->addResult('cron info', true, 'Cron did run tag [' . $event->getTag() . '] at ' . date(DATE_RFC822));
        }
    }
}