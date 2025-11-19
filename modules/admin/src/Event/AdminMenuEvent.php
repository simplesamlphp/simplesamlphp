<?php

declare(strict_types=1);

namespace SimpleSAML\Module\admin\Event;

use SimpleSAML\XHTML;

class AdminMenuEvent
{
    private XHTML\Template $template;

    public function __construct(XHTML\Template $template)
    {
        $this->template = $template;
    }

    public function getTemplate(): XHTML\Template
    {
        return $this->template;
    }
}