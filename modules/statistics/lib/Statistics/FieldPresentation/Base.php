<?php

namespace SimpleSAML\Module\statistics\Statistics\FieldPresentation;

class Base
{
    /** @var array */
    protected $fields;

    /** @var \SimpleSAML\XHTML\Template */
    protected $template;

    /** @var string */
    protected $config;


    /**
     * @param array $fields
     * @param string $config
     * @param \SimpleSAML\XHTML\Template $template
     */
    public function __construct($fields, $config, $template)
    {
        $this->fields = $fields;
        $this->template = $template;
        $this->config = $config;
    }


    /**
     * @return array
     */
    public function getPresentation()
    {
        return ['_' => 'Total'];
    }
}
