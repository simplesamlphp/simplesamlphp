<?php


/**
 * Attribute filter for running arbitrary PHP code.
 *
 * @package simpleSAMLphp
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
     * @var callable
     */
    private $function = null;


    /**
     * Initialize this filter, parse configuration
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert('is_array($config)');

        if (isset($config['function'])) {
            $this->function = $config['function'];
        } else { // TODO: remove this branch after removing the 'code' option.
            if (!isset($config['code'])) {
                throw new SimpleSAML_Error_Exception("core:PHP: Neither 'function' nor 'code' options defined.");
            }
            SimpleSAML_Logger::warning(
                "Deprecated 'code' configuration option in PHP authentication processing filter."
            );
            $this->code = (string) $config['code'];
        }
    }


    /**
     * Apply the PHP code to the attributes.
     *
     * @param array &$request The current request
     */
    public function process(&$request)
    {
        assert('is_array($request)');
        assert('array_key_exists("Attributes", $request)');

        if ($this->function) {
            $function = $this->function;
            $function($request['Attributes']);
        } else { // TODO: remove this branch after removing the 'code' option.
            $function = create_function('&$attributes', $this->code);
            $function($request['Attributes']);
        }
    }

}
