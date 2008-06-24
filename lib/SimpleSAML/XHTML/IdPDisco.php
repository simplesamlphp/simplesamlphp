<?php

/**
 * This class implements a generic IdP discovery service, for use in various IdP
 * discovery service pages. This should reduce code duplication.
 *
 * @author Olav Morken, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_XHTML_IdPDisco {

	/**
	 * The various discovery services we can host.
	 */
	private static $discoTypes = array(
		'saml20' => array(
			'metadata' => 'saml20-idp-remote',
			),
		'shib13' => array(
			'metadata' => 'shib13-idp-remote',
			),
		'wsfed' => array(
			'metadata' => 'wsfed-idp-remote',
			),
		);


	/**
	 * An instance of the configuration class.
	 */
	private $config;


	/**
	 * An instance of the metadata handler, which will allow us to fetch metadata about IdPs.
	 */
	private $metadata;


	/**
	 * The users session.
	 */
	private $session;


	/**
	 * Our discovery service type.
	 */
	private $discoType;


	/**
	 * The entity id of the SP which accesses this IdP discovery service.
	 */
	private $spEntityId;


	/**
	 * The name of the query parameter which should contain the users choice of IdP.
	 * This option default to 'entityID' for Shibboleth compatibility.
	 */
	private $returnIdParam;


	/**
	 * The URL the user should be redirected to after choosing an IdP.
	 */
	private $returnURL;


	/**
	 * Initializes this discovery service.
	 *
	 * The constructor does the parsing of the request. If this is an invalid request, it will
	 * throw an exception.
	 *
	 * @param $discoType  String which identifies the type of discovery service.
	 */
	public function __construct($discoType) {

		/* Initialize standard classes. */
		$this->config = SimpleSAML_Configuration::getInstance();
		$this->metadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$this->session = SimpleSAML_Session::getInstance();


		if(!array_key_exists($discoType, self::$discoTypes)) {
			throw new Exception('Unknown discovery service type: ' . $discoType);
		}

		$this->discoType = self::$discoTypes[$discoType];
		$this->discoType['type'] = $discoType;

		$this->log('Accessing discovery service.');


		/* Standard discovery service parameters. */

		if(!array_key_exists('entityID', $_GET)) {
			throw new Exception('Missing parameter: entityID');
		} else {
			$this->spEntityId = $_GET['entityID'];
		}

		if(!array_key_exists('returnIDParam', $_GET)) {
			$this->returnIdParam = 'entityID';
		} else {
			$this->returnIdParam = $_GET['returnIDParam'];
		}

		if(!array_key_exists('return', $_GET)) {
			throw new Exception('Missing parameter: return');
		} else {
			$this->returnURL = $_GET['return'];
		}

	}


	/**
	 * Log a message.
	 *
	 * This is an helper function for logging messages. It will prefix the messages with our
	 * discovery service type.
	 *
	 * @param $message  The message which should be logged.
	 */
	private function log($message) {
		SimpleSAML_Logger::info('idpDisco.' . $this->discoType['type'] . ': ' . $message);
	}


	/**
	 * Retrieve cookie with the given name.
	 *
	 * This function will retrieve a cookie with the given name for the current discovery
	 * service type.
	 *
	 * @param $name  The name of the cookie.
	 * @return  The value of the cookie with the given name, or NULL if no cookie with that name exists.
	 */
	private function getCookie($name) {
		$prefixedName = 'idpdisco_' . $this->discoType['type'] . '_' . $name;
		if(array_key_exists($prefixedName, $_COOKIE)) {
			return $_COOKIE[$prefixedName];
		} else {
			return NULL;
		}
	}


	/**
	 * Save cookie with the given name and value.
	 *
	 * This function will save a cookie with the given name and value for the current discovery
	 * service type.
	 *
	 * @param $name  The name of the cookie.
	 * @param $value  The value of the cookie.
	 */
	private function setCookie($name, $value) {
		$prefixedName = 'idpdisco_' . $this->discoType['type'] . '_' . $name;

		/* We save the cookies for 90 days. */
		$saveUntil = time() + 60*60*24*90;

		/* The base path for cookies. This should be the installation directory for simpleSAMLphp. */
		$cookiePath = '/' . $this->config->getBaseUrl();

		setcookie($prefixedName, $value, $saveUntil, $cookiePath);
	}


	/**
	 * Validates the given IdP entity id.
	 *
	 * Takes a string with the IdP entity id, and returns the entity id if it is valid, or
	 * NULL if not.
	 *
	 * @param $idp  The entity id we want to validate. This can be NULL, in which case we will return NULL.
	 * @return  The entity id if it is valid, NULL if not.
	 */
	private function validateIdP($idp) {
		if($idp === NULL) {
			return NULL;
		}

		try {
			$this->metadata->getMetaData($idp, $this->discoType['metadata']);
			return $idp;
		} catch(Exception $e) {
			$this->log('Unable to validate IdP entity id [' . $idp . '].');
			/* The entity id wasn't valid. */
			return NULL;
		}
	}


	/**
	 * Retrieve the users choice of IdP.
	 *
	 * This function finds out which IdP the user has manually chosen, if any.
	 *
	 * @return  The entity id of the IdP the user has chosen, or NULL if the user has made no choice.
	 */
	private function getSelectedIdP() {

		if(array_key_exists('idpentityid', $_GET)) {
			return $this->validateIdP($_GET['idpentityid']);
		}

		/* Search for the IdP selection from the form used by the links view.
		 * This form uses a name which equals idp_<entityid>, so we search for that.
		 *
		 * Unfortunately, php replaces periods in the name with underscores, and there
		 * is no reliable way to get them back. Therefore we do some quick and dirty
		 * parsing of the query string.
		 */
		$qstr = $_SERVER['QUERY_STRING'];
		$matches = array();
		if(preg_match('/(?:^|&)idp_([^=]+)=/', $qstr, $matches)) {
			return $this->validateIdP(urldecode($matches[1]));
		}

		/* No IdP chosen. */
		return NULL;
	}


	/**
	 * Retrieve the users saved choice of IdP.
	 *
	 * @return  The entity id of the IdP the user has saved, or NULL if the user hasn't saved any choice.
	 */
	private function getSavedIdP() {
		if(!$this->config->getBoolean('idpdisco.enableremember', FALSE)) {
			/* Saving of IdP choices is disabled. */
			return NULL;
		}

		if($this->getCookie('remember') === '1') {
			return $this->getPreviousIdP();
		}
	}


	/**
	 * Retrieve the previous IdP the user used.
	 *
	 * @return  The entity id of the previous IdP the user used, or NULL if this is the first time.
	 */
	private function getPreviousIdP() {
		return $this->validateIdP($this->getCookie('lastidp'));
	}


	/**
	 * Try to determine which IdP the user should most likely use.
	 *
	 * This function will first look at the previous IdP the user has chosen. If the user
	 * hasn't chosen an IdP before, it will look at the IP address.
	 *
	 * @return  The entity id of the IdP the user should most likely use.
	 */
	private function getRecommendedIdP() {

		$idp = $this->getPreviousIdP();
		if($idp !== NULL) {
			$this->log('Preferred IdP from previous use [' . $idp . '].');
			return $idp;
		}

		$idp = $this->metadata->getPreferredEntityIdFromCIDRhint(
			$this->discoType['metadata'], $_SERVER['REMOTE_ADDR']);

		if(!empty($idp)) {
			$this->log('Preferred IdP from CIDR hint [' . $idp . '].');
			return $idp;
		}

		return NULL;
	}


	/**
	 * Determine whether the choice of IdP should be saved.
	 *
	 * @return  TRUE if the choice should be saved, FALSE if not.
	 */
	private function saveIdP() {
		if(!$this->config->getBoolean('idpdisco.enableremember', FALSE)) {
			/* Saving of IdP choices is disabled. */
			return FALSE;
		}

		if(array_key_exists('remember', $_GET)) {
			return TRUE;
		}
	}


	/**
	 * Determine which IdP the user should go to, if any.
	 *
	 * @return  The entity id of the IdP the user should be sent to, or NULL if the user
	 *          should choose.
	 */
	private function getTargetIdP() {

		/* First, check if the user has chosen an IdP. */
		$idp = $this->getSelectedIdP();
		if($idp !== NULL) {
			/* The user selected this IdP. Save the choice in a cookie. */

			$this->log('Choice made [' . $idp . '] Setting cookie.');
			$this->setCookie('lastidp', $idp);

			if($this->saveIdP()) {
				$this->setCookie('remember', 1);
			} else {
				$this->setCookie('remember', 0);
			}

			return $idp;
		}

		/* Check if the user has saved an choice earlier. */
		$idp = $this->getSavedIdP();
		if($idp !== NULL) {
			$this->log('Using saved choice [' . $idp . '].');
			return $idp;
		}

		/* The user has made no choice. */
		return NULL;
	}


	/**
	 * Handles a request to this discovery service.
	 *
	 * The IdP disco parameters should be set before calling this function.
	 */
	public function handleRequest() {

		$idp = $this->getTargetIdp();
		if($idp !== NULL) {
			$this->log('Choice made [' . $idp . '] (Redirecting the user back)');
			SimpleSAML_Utilities::redirect($this->returnURL, array($this->returnIdParam => $idp));
			return;
		}

		/* No choice made. Show discovery service page. */

		$idpList = $this->metadata->getList($this->discoType['metadata']);
		$preferredIdP = $this->getRecommendedIdP();

		/*
		 * Make use of an XHTML template to present the select IdP choice to the user.
		 * Currently the supported options is either a drop down menu or a list view.
		 */
		switch($this->config->getString('idpdisco.layout', 'links')) {
		case 'dropdown':
			$templateFile = 'selectidp-dropdown.php';
			break;
		case 'links':
			$templateFile = 'selectidp-links.php';
			break;
		default:
			throw new Exception('Invalid value for the \'idpdisco.layout\' option.');
		}

		$t = new SimpleSAML_XHTML_Template($this->config, $templateFile, 'disco.php');
		$t->data['idplist'] = $idpList;
		$t->data['preferredidp'] = $preferredIdP;
		$t->data['return'] = $this->returnURL;
		$t->data['returnIDParam'] = $this->returnIdParam;
		$t->data['entityID'] = $this->spEntityId;
		$t->data['urlpattern'] = htmlspecialchars(SimpleSAML_Utilities::selfURLNoQuery());
		$t->data['rememberenabled'] = $this->config->getBoolean('idpdisco.enableremember', FALSE);
		$t->show();
	}
}

?>