<?php
/**
 * Attribute filter for running arbitrary PHP code.
 *
 * @package SimpleSAMLphp
 */
class sspmod_core_Auth_Process_PHP extends SimpleSAML_Auth_ProcessingFilter
{
    /**
     * The PHP code that should be run.
     *
     * @var string
     */
    private $code;

    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     *
     * @throws SimpleSAML_Error_Exception if the 'code' option is not defined.
     */
    public function __construct(array $config, $reserved)
    {
        parent::__construct($config, $reserved);

        if (!isset($config['code'])) {
            throw new SimpleSAML_Error_Exception("core:PHP: missing mandatory configuration option 'code'.");
        }
        $this->code = (string) $config['code'];
    }

    /**
     * Apply the PHP code to the attributes.
     *
     * @param array &$request The current request
     */
    public function process(array &$request)
    {
        assert(array_key_exists('Attributes', $request));

        $function = function(&$attributes) { eval($this->code); };
        $function($request['Attributes']);
    }
}
