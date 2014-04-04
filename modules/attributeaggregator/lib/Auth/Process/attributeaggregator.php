<?php
/**
 * Attribute Aggregator Authentication Processing filter
 *
 * Filter for requesting the vo to give attributes to the SP.
 *
 * @author Gyula Szabó <gyufi@niif.hu>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_attributeaggregator_Auth_Process_attributeaggregator extends SimpleSAML_Auth_ProcessingFilter
{

	/**
	 *
	 * AA IdP entityId
	 * @var string
	 */
	private $entityId = null;

	/**
	 *
	 * attributeId, the key of the user in the AA. default is eduPersonPrincipalName
	 * @var unknown_type
	 */
	private $attributeId = "urn:oid:1.3.6.1.4.1.5923.1.1.1.6";

	/**
	 *
	 * If set to TRUE, the module will throw an exception if attributeId is not found.
	 * @var boolean
	 */
	private $required = FALSE;

	/**
	 * 
	 * nameIdFormat, the format of the attributeId. Default is "urn:oasis:names:tc:SAML:2.0:nameid-format:persistent";
	 * @var unknown_type
	 */
	private $nameIdFormat = SAML2_Const::NAMEID_PERSISTENT;


	/**
	 * Array of the requested attributes
	 * @var array
	 */
	private $attributes = array();

	/**
	 * nameFormat of attributes. Default is "urn:oasis:names:tc:SAML:2.0:attrname-format:uri"
	 * @var string
	 */
	private $attributeNameFormat = "urn:oasis:names:tc:SAML:2.0:attrname-format:uri";

	/**
	 * Initialize attributeaggregator filter
	 *
	 * Validates and parses the configuration
	 *
	 * @param array $config   Configuration information
	 * @param mixed $reserved For future use
	 */
	public function __construct($config, $reserved)
	{
		assert('is_array($config)');
		parent::__construct($config, $reserved);

		$metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

		if ($config['entityId']) {
			$aameta = $metadata->getMetaData($config['entityId'], 'attributeauthority-remote');
			if (!$aameta) {
				throw new SimpleSAML_Error_Exception(
                    'attributeaggregator: AA entityId (' . $config['entityId'] .
					') does not exist in the attributeauthority-remote metadata set.'
				);
			}
			$this->entityId = $config['entityId'];
		}
		else {
			throw new SimpleSAML_Error_Exception(
                    'attributeaggregator: AA entityId is not specified in the configuration.'
				);
		}

		if (! empty($config["attributeId"])){
			$this->attributeId = $config["attributeId"];
		}
		
		if (! empty($config["required"])){
			$this->required = $config["required"];
		}

		if (!empty($config["nameIdFormat"])){
			foreach (array(
							SAML2_Const::NAMEID_UNSPECIFIED,
							SAML2_Const::NAMEID_PERSISTENT,
							SAML2_Const::NAMEID_TRANSIENT,
							SAML2_Const::NAMEID_ENCRYPTED) as $format) {
				$invalid = TRUE;
				if ($config["nameIdFormat"] == $format) {
					$this->nameIdFormat = $config["nameIdFormat"];
					$invalid = FALSE;
					break;
				}
			}
			if ($invalid)
				throw new SimpleSAML_Error_Exception("attributeaggregator: Invalid nameIdFormat: ".$config["nameIdFormat"]);
		}

		if (!empty($config["attributes"])){
			if (! is_array($config["attributes"])) {
				throw new SimpleSAML_Error_Exception("attributeaggregator: Invalid format of attributes array in the configuration");
			}
			foreach ($config["attributes"] as $attribute) {
				if (! is_array($attribute)) {
					throw new SimpleSAML_Error_Exception("attributeaggregator: Invalid format of attributes array in the configuration");
				}
				if (array_key_exists("values", $attribute)) {
					if (! is_array($attribute["values"])) {
						throw new SimpleSAML_Error_Exception("attributeaggregator: Invalid format of attributes array in the configuration");
					}	
				}
				if (array_key_exists('multiSource', $attribute)){
					if(! preg_match('/^(merge|keep|override)$/', $attribute['multiSource']))
						throw new SimpleSAML_Error_Exception(
                    		'attributeaggregator: Invalid multiSource value '.$attribute['multiSource'].' for '.key($attribute).'. It not mached keep, merge or override.'
					);
				}
			}
			$this->attributes = $config["attributes"];
		}

		if (!empty($config["attributeNameFormat"])){
			foreach (array(
							SAML2_Const::NAMEFORMAT_UNSPECIFIED,
							SAML2_Const::NAMEFORMAT_URI,
							SAML2_Const::NAMEFORMAT_BASIC) as $format) {
				$invalid = TRUE;
				if ($config["attributeNameFormat"] == $format) {
					$this->attributeNameFormat = $config["attributeNameFormat"];
					$invalid = FALSE;
					break;
				}
			}
			if ($invalid)
				throw new SimpleSAML_Error_Exception("attributeaggregator: Invalid attributeNameFormat: ".$config["attributeNameFormat"], 1);
		}
	}

	/**
	 * Process a authentication response
	 *
	 * This function saves the state, and redirects the user to the Attribute Authority for
	 * entitlements.
	 *
	 * @param array &$state The state of the response.
	 *
	 * @return void
	 */
	public function process(&$state)
	{
		assert('is_array($state)');
		$state['attributeaggregator:authsourceId'] = $state["saml:sp:State"]["saml:sp:AuthId"];
		$state['attributeaggregator:entityId'] = $this->entityId;

		$state['attributeaggregator:attributeId'] = $state['Attributes'][$this->attributeId];
		$state['attributeaggregator:nameIdFormat'] = $this->nameIdFormat;

		$state['attributeaggregator:attributes'] = $this->attributes;
		$state['attributeaggregator:attributeNameFormat'] = $this->attributeNameFormat;

		if (! $state['attributeaggregator:attributeId']){
			if (! $this->required) {
				SimpleSAML_Logger::info('[attributeaggregator] This user session does not have '.$this->attributeId.', which is required for querying the AA! Continue processing.');
				SimpleSAML_Logger::debug('[attributeaggregator] Attributes are: '.var_export($state['Attributes'],true));
				SimpleSAML_Auth_ProcessingChain::resumeProcessing($state);
			}	
			throw new SimpleSAML_Error_Exception("This user session does not have ".$this->attributeId.", which is required for querying the AA! Attributes are: ".var_export($state['Attributes'],1));
		}
		
		// Save state and redirect
		$id  = SimpleSAML_Auth_State::saveState($state, 'attributeaggregator:request');
		$url = SimpleSAML_Module::getModuleURL('attributeaggregator/attributequery.php');
		SimpleSAML_Utilities::redirect($url, array('StateId' => $id)); // FIXME: redirect is deprecated
	}
}
