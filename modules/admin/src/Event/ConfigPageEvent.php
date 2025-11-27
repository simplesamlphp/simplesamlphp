<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Event;

use SimpleSAML\XHTML;

class ConfigPageEvent
{
    public function __construct(
        private readonly XHTML\Template $template,
    )
    {}

    public function getTemplate(): XHTML\Template
    {
        return $this->template;
    }
}