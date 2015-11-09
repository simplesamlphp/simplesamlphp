<?php

/**
 * This base LDAP filter class can be extended to enable real
 * filter classes direct access to the authsource ldap config
 * and connects to the ldap server.
 *
 * @author Ryan Panning <panman@traileyes.com>
 * @package SimpleSAMLphp
 */
abstract class sspmod_ldap_Auth_Process_BaseFilter extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * List of attribute "alias's" linked to the real attribute
	 * name. Used for abstraction / configuration of the LDAP
	 * attribute names, which may change between dir service.
	 *
	 * @var array
	 */
	protected $attribute_map;


	/**
	 * The base DN of the LDAP connection. Used when searching
	 * the LDAP server.
	 *
	 * @var string|array
	 */
	protected $base_dn;


	/**
	 * The construct method will change the filter config into
	 * a SimpleSAML_Configuration object and store it here for
	 * later use, if needed.
	 *
	 * @var SimpleSAML_Configuration
	 */
	protected $config;


	/**
	 * Instance, object of the ldap connection. Stored here to
	 * be access later during processing.
	 *
	 * @var sspmod_ldap_LdapConnection
	 */
	private $ldap;


	/**
	 * Many times a LDAP product specific query can be used to
	 * speed up or reduce the filter process. This helps the
	 * child classes determine the product used to optimize
	 * those queries.
	 *
	 * @var string
	 */
	protected $product;


	/**
	 * The class "title" used in logging and exception messages.
	 * This should be prepended to the beginning of the message.
	 *
	 * @var string
	 */
	protected $title = 'ldap:BaseFilter : ';


	/**
	 * List of LDAP object types, used to determine the type of
	 * object that a DN references.
	 *
	 * @var array
	 */
	protected $type_map;


	/**
	 * Checks the authsource, if defined, for configuration values
	 * to the LDAP server. Then sets up the LDAP connection for the
	 * instance/object and stores everything in class members.
	 *
	 * @throws SimpleSAML_Error_Exception
	 * @param array $config
	 * @param $reserved
	 */
	public function __construct(&$config, $reserved) {
		parent::__construct($config, $reserved);

		// Change the class $title to match it's true name
		// This way if the class is extended the proper name is used
		$classname = get_class($this);
		$classname = explode('_', $classname);
		$this->title = 'ldap:' . end($classname) . ' : ';

		// Log the construction
		SimpleSAML_Logger::debug(
			$this->title . 'Creating and configuring the filter.'
		);

		// If an authsource was defined (an not empty string)...
		if (isset($config['authsource']) && $config['authsource']) {

			// Log the authsource request
			SimpleSAML_Logger::debug(
				$this->title . 'Attempting to get configuration values from authsource [' .
				$config['authsource'] . ']'
			);

			// Get the authsources file, which should contain the config
			$authsource = SimpleSAML_Configuration::getConfig('authsources.php');

			// Verify that the authsource config exists
			if (!$authsource->hasValue($config['authsource'])) {
				throw new SimpleSAML_Error_Exception(
					$this->title . 'Authsource [' . $config['authsource'] .
					'] defined in filter parameters not found in authsources.php'
				);
			}

			// Get just the specified authsource config values
			$authsource = $authsource->getConfigItem($config['authsource']);
			$authsource = $authsource->toArray();

			// Make sure it is an ldap source
			// TODO: Support ldap:LDAPMulti, if possible
			if (@$authsource[0] != 'ldap:LDAP') {
				throw new SimpleSAML_Error_Exception(
					$this->title . 'Authsource [' . $config['authsource'] .
					'] specified in filter parameters is not an ldap:LDAP type'
				);
			}

			// Build the authsource config
			$authconfig = array();
			$authconfig['ldap.hostname']   = @$authsource['hostname'];
			$authconfig['ldap.enable_tls'] = @$authsource['enable_tls'];
			$authconfig['ldap.timeout']    = @$authsource['timeout'];
			$authconfig['ldap.debug']      = @$authsource['debug'];
			$authconfig['ldap.basedn']     = (@$authsource['search.enable'] ? @$authsource['search.base'] : NULL);
			$authconfig['ldap.username']   = (@$authsource['search.enable'] ? @$authsource['search.username'] : NULL);
			$authconfig['ldap.password']   = (@$authsource['search.enable'] ? @$authsource['search.password'] : NULL);
			$authconfig['ldap.username']   = (@$authsource['priv.read'] ? @$authsource['priv.username'] : $authconfig['ldap.username']);
			$authconfig['ldap.password']   = (@$authsource['priv.read'] ? @$authsource['priv.password'] : $authconfig['ldap.password']);

			// Only set the username attribute if the authsource specifies one attribute
			if (@$authsource['search.enable'] && is_array(@$authsource['search.attributes'])
			     && count($authsource['search.attributes']) == 1) {
				$authconfig['attribute.username'] = reset($authsource['search.attributes']);
			}

			// Merge the authsource config with the filter config,
			// but have the filter config override the authsource config
			$config = array_merge($authconfig, $config);

			// Authsource complete
			SimpleSAML_Logger::debug(
				$this->title . 'Retrieved authsource [' . $config['authsource'] .
				'] configuration values: ' . $this->var_export($authconfig)
			);
		}

		// Convert the config array to a config class,
		// that way we can verify type and define defaults.
		// Store in the instance in-case needed later, by a child class.
		$this->config = SimpleSAML_Configuration::loadFromArray($config, 'ldap:AuthProcess');

		// Set all the filter values, setting defaults if needed
		$this->base_dn = $this->config->getArrayizeString('ldap.basedn', '');
		$this->product = $this->config->getString('ldap.product', '');

		// Cleanup the directory service, so that it is easier for
		// child classes to determine service name consistently
		$this->product = trim($this->product);
		$this->product = strtoupper($this->product);

		// Log the member values retrieved above
		SimpleSAML_Logger::debug(
			$this->title . 'Configuration values retrieved;' .
			' BaseDN: ' . $this->var_export($this->base_dn) .
			' Product: ' . $this->var_export($this->product)
		);

		// Setup the attribute map which will be used to search LDAP
		$this->attribute_map = array(
			'dn'       => $this->config->getString('attribute.dn', 'distinguishedName'),
			'groups'   => $this->config->getString('attribute.groups', 'groups'),
			'member'   => $this->config->getString('attribute.member', 'member'),
			'memberof' => $this->config->getString('attribute.memberof', 'memberOf'),
			'name'     => $this->config->getString('attribute.groupname', 'name'),
			'type'     => $this->config->getString('attribute.type', 'objectClass'),
			'username' => $this->config->getString('attribute.username', 'sAMAccountName')
		);

		// Log the attribute map
		SimpleSAML_Logger::debug(
			$this->title . 'Attribute map created: ' . $this->var_export($this->attribute_map)
		);

		// Setup the object type map which is used to determine a DNs' type
		$this->type_map = array(
			'group' => $this->config->getString('type.group', 'group'),
			'user'  => $this->config->getString('type.user', 'user')
		);

		// Log the type map
		SimpleSAML_Logger::debug(
			$this->title . 'Type map created: ' . $this->var_export($this->type_map)
		);
	}


	/**
	 * Getter for the LDAP connection object. Created this getter
	 * rather than setting in the constructor to avoid unnecessarily
	 * connecting to LDAP when it might not be needed.
	 *
	 * @return sspmod_ldap_LdapConnection
	 */
	protected function getLdap() {

		// Check if already connected
		if ($this->ldap) {
			return $this->ldap;
		}

		// Get the connection specific options
		$hostname   = $this->config->getString('ldap.hostname');
		$port       = $this->config->getInteger('ldap.port', 389);
		$enable_tls = $this->config->getBoolean('ldap.enable_tls', FALSE);
		$debug      = $this->config->getBoolean('ldap.debug', FALSE);
		$timeout    = $this->config->getInteger('ldap.timeout', 0);
		$username   = $this->config->getString('ldap.username', NULL);
		$password   = $this->config->getString('ldap.password', NULL);

		// Log the LDAP connection
		SimpleSAML_Logger::debug(
			$this->title . 'Connecting to LDAP server;' .
			' Hostname: ' . $hostname .
			' Port: ' . $port .
			' Enable TLS: ' . ($enable_tls ? 'Yes' : 'No') .
			' Debug: ' . ($debug ? 'Yes' : 'No') .
			' Timeout: ' . $timeout .
			' Username: ' . $username .
			' Password: ' . str_repeat('*', strlen($password))
		);

		// Connect to the LDAP server to be queried during processing
		$this->ldap = new SimpleSAML_Auth_LDAP($hostname, $enable_tls, $debug, $timeout, $port);
		$this->ldap->bind($username, $password);

		// All done
		return $this->ldap;
	}


	/**
	 * Local utility function to get details about a variable,
	 * basically converting it to a string to be used in a log
	 * message. The var_export() function returns several lines
	 * so this will remove the new lines and trim each line.
	 *
	 * @param mixed $value
	 * @return string
	 */
	protected function var_export($value) {
		$export = var_export($value, TRUE);
		$lines = explode("\n", $export);
		foreach ($lines as &$line) {
			$line = trim($line);
		}
		return implode(' ', $lines);
	}
}
