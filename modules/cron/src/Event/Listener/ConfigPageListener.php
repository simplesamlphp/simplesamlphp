<?php

declare(strict_types=1);

namespace SimpleSAML\Module\cron\Event\Listener;

use SimpleSAML\Module\admin\Event\ConfigPageEvent;

class ConfigPageListener
{
    public function __invoke(ConfigPageEvent $event): void
    {
        $template = $event->getTemplate();

        $template->data['links'][] = [
            'href' => \SimpleSAML\Module::getModuleURL('cron/info'),
            'text' => \SimpleSAML\Locale\Translate::noop('Cron module information page'),
        ];

        $template->getLocalization()->addModuleDomain('cron');
    }
}