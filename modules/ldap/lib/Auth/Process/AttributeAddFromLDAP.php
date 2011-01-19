<?php
// +---------------------------------------------------+
// | PHP Version: 5.2.x                                |
// +---------------------------------------------------+
// | simpleSAMLphp Auth Proc for adding additional     |
// | identity attributes from a secondary LDAP query   |
// +---------------------------------------------------+
// |                                                   |
// | This Auth Proc needs the following configuration  |
// | directives set in order to function properly.     |
// |                                                   |
// | 'ldap_host' the hostname of the LDAP server       |
// |                                                   |
// | 'ldap_port' the port for the LDAP process         |
// |                                                   |
// | 'ldap_bind_user' the user to bind as (optional)   |
// |                                                   |
// | 'ldap_bind_pwd' the password for the bind user    |
// |                 required if ldap_bind_user is     |
// |                 specified                         |
// |                                                   |
// | 'userid_attribute' the attribute you will use to  |
// |    filter results                                 |
// |                                                   |
// | 'ldap_search_base_dn' the search base             |
// |                                                   |
// | 'ldap_search_filter' the search filter.           |
// | NOTE: Variable substitution will be performed on  |
// |      ldap_search_filter. Any attribute in the     |
// |      identity can be substituted by surrounding   |
// |      it with percent symbols (%). For instance    |
// |      %cn% would be replaced with the cn of the    |
// |      user.                                        |
// |                                                   |
// | 'ldap_search_attribute' the name of the attribute |
// |      in the search results that you want to add   |
// |      to the identity attributes                   |
// |                                                   |
// | 'new_attribute_name' the name you want the newly  |
// |      added attribute to be called when it's added |
// |                                                   |
// | EXAMPLE                                           |
// |                                                   |
// | 'authproc' => array(
// |    50 => array(
// |    'class' => 'ldap:AttributeAddFromLDAP',
// |    'ldap_host' => 'ldap.example.org',
// |    'ldap_port' => '389',
// |    'ldap_bind_user' => 'ldap_bind_user',
// |    'ldap_bind_pwd' => 'ldap_bind_pwd',
// |    'userid_attribute' => 'cn',
// |    'ldap_search_base_dn' => 'cn=security_tags,dc=example,dc=org',
// |    'ldap_search_filter' => '(uniquemember=cn=%cn%,cn=users,cn=example,dc=org)',
// |    'ldap_search_attribute' => 'displayname',
// |    'new_attribute_name' => 'security_tags',
// |    ),
// | ),
// |                                                   |
// | This will cause the Auth Proc to query the LDAP   |
// | looking for all the security tags that the        |
// | current user is assigned. It will take the value  |
// | contained in displayname and put it into a mutli- |
// | value attribute called security_tags              |
// |                                                   |
// +---------------------------------------------------+
// | Author: Steve Moitozo II <steve_moitozo@jaars.org>|
// | Created: 20100513                                 |
// +---------------------------------------------------+
// | 20100920 Steve Moitozo II                         |
// |    - incorporated feedback from Olav Morken to    |
// |    prep code for inclusion in SimpleSAMLphp distro|
// |    - moved call to ldap_set_options() inside test |
// |    for $ds                                        |
// |    - added the output of ldap_error() to the      |
// |    exceptions                                     |
// |    - reduced some of the nested ifs               |
// |    - added support for multiple values            |
// |    - added support for anonymous binds            |
// |    - added escaping of search filter and attribute|
// +---------------------------------------------------+


/**
 * Filter to add attributes to the identity by executing a query against an LDAP directory
 *
 *
 * @author Steve Moitozo, JAARS, Inc.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_ldap_Auth_Process_AttributeAddFromLDAP extends SimpleSAML_Auth_ProcessingFilter {

	/**
	 * The configuration.
	 *
	 * Associative array of strings.
	 */
	private $config = array();


	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		assert('is_array($config)');

		$reqConfigVars = array(
			'ldap_host',
			'ldap_port',
			'ldap_bind_user',
			'ldap_bind_pwd',
			'userid_attribute',
			'ldap_search_base_dn',
			'ldap_search_filter',
			'ldap_search_attribute',
			'new_attribute_name'
		);

		foreach($config as $name => $values){
			if(!is_string($name)){
				throw new Exception('Invalid attribute name: ' . var_export($name, TRUE));
			}

			// make sure the name is in the list of required config variables
			if(in_array($name,$reqConfigVars)){


				if(is_array($values)){
					throw new Exception('Configuration parameters must not contain arrays. The value for parameter "'.$name.'" is an array.');
				}

				$this->config[$name] = $values;

			}else{
				// unknown config variable, skipping
				throw new Exception('Unknown configuration variable "'.$name.'"');
			}


		}

		$configVarsSet = array_keys($this->config);
		foreach($reqConfigVars as $configVar){
			if(!in_array($configVar, $configVarsSet)){
				throw new Exception('Please provide a value for configuration parameter "'.$configVar.'".');
			}
		}
	}


	/**
	 * Add attributes from an LDAP server.
	 *
	 * @param array &$request  The current request
	 */
	public function process(&$request) {
		assert('is_array($request)');
		assert('array_key_exists("Attributes", $request)');

		$attributes =& $request['Attributes'];

		if(!isset($attributes[$this->config['userid_attribute']])){
			throw new Exception('The user\'s identity does not have an attribute called "'.$this->config['userid_attribute'].'"');
		}


		// perform a merge on the ldap_search_filter

		// loop over the attributes and build the search and replace arrays
		foreach($attributes as $attr => $val){
			$arrSearch[] = '%'.$attr.'%';

			if(strlen($val[0]) > 0){
				$arrReplace[] = SimpleSAML_Auth_LDAP::escape_filter_value($val[0]);
			}else{
				$arrReplace[] = '';
			}
		}

		// merge the attributes into the ldap_search_filter
		$merged_ldap_search_filter = str_replace($arrSearch, $arrReplace, $this->config['ldap_search_filter']);


		// connect to the LDAP directory
		$ds = ldap_connect($this->config['ldap_host'], $this->config['ldap_port']);

		if(!$ds){
			throw new Exception('Failed to initialize LDAP connection parameters ('.ldap_error(NULL).')');
		}

		ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

		// if we're supposed to bind as a specified user
		if((isset($this->config['ldap_bind_user']) && $this->config['ldap_bind_user']) && 
		   (isset($this->config['ldap_bind_pwd'])  && $this->config['ldap_bind_pwd'])){

			// bind to the directory as the specified user
			if(!ldap_bind($ds, $this->config['ldap_bind_user'], $this->config['ldap_bind_pwd'])){
				throw new Exception($this->config['ldap_bind_user'].' failed to bind against '.$this->config['ldap_host'].' ('.ldap_error($ds).')');
			}

		}else{	// bind to the directory anonymously

			if(!ldap_bind($ds)){
				throw new Exception('Failed to anonymously bind against '.$this->config['ldap_host'].' ('.ldap_error($ds).')');
			}

		}

		// search for matching entries
		$sr = ldap_search($ds, $this->config['ldap_search_base_dn'], $merged_ldap_search_filter, array($this->config['ldap_search_attribute']));
		$entries = ldap_get_entries($ds, $sr);

		// handle [multiple] values
		if(is_array($entries) && is_array($entries[0])){
			$results = array();
			foreach($entries as $entry){
				$entry = $entry[strtolower($this->config['ldap_search_attribute'])];
				for($i = 0; $i < $entry['count']; $i++){
					$results[] = $entry[$i];
				}
			}
			$attributes[$this->config['new_attribute_name']] = array_values($results);
		}

		ldap_unbind($ds);

	}

}
