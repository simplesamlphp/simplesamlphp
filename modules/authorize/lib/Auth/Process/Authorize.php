<?php

namespace SimpleSAML\Module\authorize\Auth\Process;

/**
 * Filter to authorize only certain users.
 * See docs directory.
 *
 * @author Ernesto Revilla, Yaco Sistemas SL., Ryan Panning
 * @package SimpleSAMLphp
 */

class Authorize extends \SimpleSAML\Auth\ProcessingFilter
{
    /**
     * Flag to deny/unauthorize the user a attribute filter IS found
     *
     * @var bool
     */
    protected $deny = false;

    /**
     * Flag to turn the REGEX pattern matching on or off
     *
     * @var bool
     */
    protected $regex = true;

    /**
     * Array of localised rejection messages
     *
     * @var array
     */
    protected $reject_msg = [];

    /**
     * Array of valid users. Each element is a regular expression. You should
     * user \ to escape special chars, like '.' etc.
     *
     */
    protected $valid_attribute_values = [];

    /**
     * Initialize this filter.
     * Validate configuration parameters.
     *
     * @param array $config  Configuration information about this filter.
     * @param mixed $reserved  For future use.
     */
    public function __construct($config, $reserved)
    {
        parent::__construct($config, $reserved);

        assert(is_array($config));

        // Check for the deny option, get it and remove it
        // Must be bool specifically, if not, it might be for a attrib filter below
        if (isset($config['deny']) && is_bool($config['deny'])) {
            $this->deny = $config['deny'];
            unset($config['deny']);
        }

        // Check for the regex option, get it and remove it
        // Must be bool specifically, if not, it might be for a attrib filter below
        if (isset($config['regex']) && is_bool($config['regex'])) {
            $this->regex = $config['regex'];
            unset($config['regex']);
        }

        // Check for the reject_msg option, get it and remove it
        // Must be array of languages
        if (isset($config['reject_msg']) && is_array($config['reject_msg'])) {
            $this->reject_msg = $config['reject_msg'];
            unset($config['reject_msg']);
        }

        foreach ($config as $attribute => $values) {
            if (is_string($values)) {
                $values = [$values];
            }
            if (!is_array($values)) {
                throw new \Exception(
                    'Filter Authorize: Attribute values is neither string nor array: '.var_export($attribute, true)
                );
            }
            foreach ($values as $value) {
                if (!is_string($value)) {
                    throw new \Exception(
                        'Filter Authorize: Each value should be a string for attribute: '.var_export($attribute, true).
                            ' value: '.var_export($value, true).' Config is: '.var_export($config, true)
                    );
                }
            }
            $this->valid_attribute_values[$attribute] = $values;
        }
    }

    /**
     * Apply filter to validate attributes.
     *
     * @param array &$request  The current request
     */
    public function process(&$request)
    {
        $authorize = $this->deny;
        assert(is_array($request));
        assert(array_key_exists('Attributes', $request));

        $attributes = &$request['Attributes'];
        // Store the rejection message array in the $request
        if(!empty($this->reject_msg)) {
            $request['authprocAuthorize_reject_msg'] = $this->reject_msg;
        }

        foreach ($this->valid_attribute_values as $name => $patterns) {
            if (array_key_exists($name, $attributes)) {
                foreach ($patterns as $pattern) {
                    $values = $attributes[$name];
                    if (!is_array($values)) {
                        $values = [$values];
                    }
                    foreach ($values as $value) {
                        if ($this->regex) {
                            $matched = preg_match($pattern, $value);
                        } else {
                            $matched = ($value == $pattern);
                        }
                        if ($matched) {
                            $authorize = ($this->deny ? false : true);
                            break 3;
                        }
                    }
                }
            }
        }
        if (!$authorize) {
            $this->unauthorized($request);
        }
    }

    /**
     * When the process logic determines that the user is not
     * authorized for this service, then forward the user to
     * an 403 unauthorized page.
     *
     * Separated this code into its own method so that child
     * classes can override it and change the action. Forward
     * thinking in case a "chained" ACL is needed, more complex
     * permission logic.
     *
     * @param array $request
     */
    protected function unauthorized(&$request)
    {
        // Save state and redirect to 403 page
        $id = \SimpleSAML\Auth\State::saveState($request, 'authorize:Authorize');
        $url = \SimpleSAML\Module::getModuleURL('authorize/authorize_403.php');
        \SimpleSAML\Utils\HTTP::redirectTrustedURL($url, ['StateId' => $id]);
    }
}
