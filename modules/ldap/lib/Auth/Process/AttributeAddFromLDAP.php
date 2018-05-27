<?php
/**
 * Filter to add attributes to the identity by executing a query against an LDAP directory
 *
 * @package SimpleSAMLphp
 */
class sspmod_ldap_Auth_Process_AttributeAddFromLDAP extends sspmod_ldap_Auth_Process_BaseFilter
{
    /**
     * LDAP attributes to add to the request attributes
     *
     * @var array
     */
    protected $search_attributes;

    /**
     * LDAP search filter to use in the LDAP query
     *
     * @var string
     */
    protected $search_filter;

    /**
     * What to do with attributes when the target already exists. Either replace, merge or add.
     *
     * @var string
     */
    protected $attr_policy;

    /**
     * Initialize this filter.
     *
     * @param array $config Configuration information about this filter.
     * @param mixed $reserved For future use.
     */
    public function __construct(array $config, $reserved)
    {
        /*
         * For backwards compatibility, check for old config names
         * @TODO Remove after 2.0
         */
        if (isset($config['ldap_host'])) {
            $config['ldap.hostname'] = $config['ldap_host'];
        }
        if (isset($config['ldap_port'])) {
            $config['ldap.port'] = $config['ldap_port'];
        }
        if (isset($config['ldap_bind_user'])) {
            $config['ldap.username'] = $config['ldap_bind_user'];
        }
        if (isset($config['ldap_bind_pwd'])) {
            $config['ldap.password'] = $config['ldap_bind_pwd'];
        }
        if (isset($config['userid_attribute'])) {
            $config['attribute.username'] = $config['userid_attribute'];
        }
        if (isset($config['ldap_search_base_dn'])) {
            $config['ldap.basedn'] = $config['ldap_search_base_dn'];
        }
        if (isset($config['ldap_search_filter'])) {
            $config['search.filter'] = $config['ldap_search_filter'];
        }
        if (isset($config['ldap_search_attribute'])) {
            $config['search.attribute'] = $config['ldap_search_attribute'];
        }
        if (isset($config['new_attribute_name'])) {
            $config['attribute.new'] = $config['new_attribute_name'];
        }

        /*
         * Remove the old config names
         * @TODO Remove after 2.0
         */
        unset(
            $config['ldap_host'],
            $config['ldap_port'],
            $config['ldap_bind_user'],
            $config['ldap_bind_pwd'],
            $config['userid_attribute'],
            $config['ldap_search_base_dn'],
            $config['ldap_search_filter'],
            $config['ldap_search_attribute'],
            $config['new_attribute_name']
        );

        // Now that we checked for BC, run the parent constructor
        parent::__construct($config, $reserved);

        // Get filter specific config options
        $this->search_attributes = $this->config->getArrayize('attributes', array());
        if (empty($this->search_attributes)) {
            $new_attribute = $this->config->getString('attribute.new', '');
            $this->search_attributes[$new_attribute] = $this->config->getString('search.attribute');
        }
        $this->search_filter = $this->config->getString('search.filter');

        // get the attribute policy
        $this->attr_policy = $this->config->getString('attribute.policy', 'merge');
    }


    /**
     * Add attributes from an LDAP server.
     *
     * @param array &$request The current request
     */
    public function process(array &$request)
    {
        assert(array_key_exists('Attributes', $request));

        $attributes =& $request['Attributes'];

        // perform a merge on the ldap_search_filter
        // loop over the attributes and build the search and replace arrays
        $arrSearch = array();
        $arrReplace = array();
        foreach ($attributes as $attr => $val) {
            $arrSearch[] = '%'.$attr.'%';

            if (strlen($val[0]) > 0) {
                $arrReplace[] = SimpleSAML_Auth_LDAP::escape_filter_value($val[0]);
            } else {
                $arrReplace[] = '';
            }
        }

        // merge the attributes into the ldap_search_filter
        $filter = str_replace($arrSearch, $arrReplace, $this->search_filter);

        if (strpos($filter, '%') !== false) {
            SimpleSAML\Logger::info('AttributeAddFromLDAP: There are non-existing attributes in the search filter. ('.
                                    $this->search_filter.')');
            return;
        }

        if (!in_array($this->attr_policy, array('merge', 'replace', 'add'), true)) {
            SimpleSAML\Logger::warning("AttributeAddFromLDAP: 'attribute.policy' must be one of 'merge',".
                                       "'replace' or 'add'.");
            return;
        }

        // getLdap
        try {
            $ldap = $this->getLdap();
        } catch (Exception $e) {
            // Added this warning in case $this->getLdap() fails
            SimpleSAML\Logger::warning("AttributeAddFromLDAP: exception = " . $e);
            return;
        }
        // search for matching entries
        try {
            $entries = $ldap->searchformultiple(
                $this->base_dn,
                $filter,
                array_values($this->search_attributes),
                true,
                false
            );
        } catch (Exception $e) {
            return; // silent fail, error is still logged by LDAP search
        }

        // handle [multiple] values
        foreach ($entries as $entry) {
            foreach ($this->search_attributes as $target => $name) {
                if (is_numeric($target)) {
                    $target = $name;
                }

                if (isset($attributes[$target]) && $this->attr_policy === 'replace') {
                    unset($attributes[$target]);
                }
                $name = strtolower($name);
                if (isset($entry[$name])) {
                    unset($entry[$name]['count']);
                    if (isset($attributes[$target])) {
                        foreach (array_values($entry[$name]) as $value) {
                            if ($this->attr_policy === 'merge') {
                                if (!in_array($value, $attributes[$target], true)) {
                                    $attributes[$target][] = $value;
                                }
                            } else {
                                $attributes[$target][] = $value;
                            }
                        }
                    } else {
                        $attributes[$target] = array_values($entry[$name]);
                    }
                }
            }
        }
    }
}
