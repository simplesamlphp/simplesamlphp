<?php

namespace  SimpleSAML\Module\cron;

/**
 * Handles interactions with SSP's cron system/hooks.
 */
class Cron
{
    /**
     * The configuration for the Cron module
     * @var \SimpleSAML_Configuration
     */
    private $cronconfig;

    /*
     * @param \SimpleSAML_Configuration $cronconfig The cron configuration to use. If not specified defaults
     * to `config/module_cron.php`
     */
    public function __construct(\SimpleSAML_Configuration $cronconfig = null)
    {
        if ($cronconfig == null) {
            $cronconfig = \SimpleSAML_Configuration::getConfig('module_cron.php');
        }
        $this->cronconfig = $cronconfig;
    }

    /**
     * Invoke the cron hook for the given tag
     * @param $tag string The tag to use. Must be valid in the cronConfig
     * @return array the tag, and summary information from the run.
     * @throws Exception If an invalid tag specified
     */
    public function runTag($tag)
    {

        if (!$this->isValidTag($tag)) {
            throw new \Exception("Invalid cron tag '$tag''");
        }

        $summary = array();
        $croninfo = array(
            'summary' => &$summary,
            'tag' => $tag,
        );

        \SimpleSAML\Module::callHooks('cron', $croninfo);

        foreach ($summary as $s) {
            \SimpleSAML\Logger::debug('Cron - Summary: ' . $s);
        }

        return $croninfo;
    }

    public function isValidTag($tag)
    {
        if (!is_null($this->cronconfig->getValue('allowed_tags'))) {
            return in_array($tag, $this->cronconfig->getArray('allowed_tags'), true);
        }
        return true;
    }
}
