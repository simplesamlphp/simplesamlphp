<?php

namespace SimpleSAML\Module\statistics\Statistics\FieldPresentation;

class Base
{
    protected $fields;
    protected $template;
    protected $config;

    public function __construct($fields, $config, $template)
    {
        $this->fields = $fields;
        $this->template = $template;
        $this->config = $config;
    }

    public function getPresentation()
    {
        return ['_' => 'Total'];
    }
}
