<?php

/**
 * This class implements a generic IdP discovery service, for use in various IdP
 * discovery service pages. This should reduce code duplication.
 *
 * This module extends the basic IdP disco handler, and add features like filtering 
 * and tabs.
 *
 * @author Andreas Åkre Solberg <andreas@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_discopower_PowerIdPDisco extends SimpleSAML_XHTML_IdPDisco {

	private $discoconfig;


	/**
	 * The domain to use when saving common domain cookies.
	 * This is NULL if support for common domain cookies is disabled.
	 *
	 * @var string|NULL
	 */
	private $cdcDomain;


	/**
	 * The lifetime of the CDC cookie, in seconds.
	 * If set to NULL, it will only be valid until the browser is closed.
	 *
	 * @var int|NULL
	 */
	private $cdcLifetime;


	/**
	 * Initializes this discovery service.
	 *
	 * The constructor does the parsing of the request. If this is an invalid request, it will
	 * throw an exception.
	 *
	 * @param array $metadataSets  Array with metadata sets we find remote entities in.
	 * @param string $instance  The name of this instance of the discovery service.
	 */
	public function __construct(array $metadataSets, $instance) {

		parent::__construct($metadataSets, $instance);

		$this->discoconfig = SimpleSAML_Configuration::getConfig('module_discopower.php');

		$this->cdcDomain = $this->discoconfig->getString('cdc.domain', NULL);
		if ($this->cdcDomain !== NULL && $this->cdcDomain[0] !== '.') {
			/* Ensure that the CDC domain starts with a dot ('.') as required by the spec. */
			$this->cdcDomain = '.' . $this->cdcDomain;
		}

		$this->cdcLifetime = $this->discoconfig->getInteger('cdc.lifetime', NULL);
	}


	/**
	 * Log a message.
	 *
	 * This is an helper function for logging messages. It will prefix the messages with our
	 * discovery service type.
	 *
	 * @param $message  The message which should be logged.
	 */
	protected function log($message) {
		SimpleSAML_Logger::info('PowerIdPDisco.' . $this->instance . ': ' . $message);
	}


	/**
	 * Compare two entities.
	 *
	 * This function is used to sort the entity list. It sorts based on english name,
	 * and will always put IdP's with names configured before those with only an
	 * entityID.
	 *
	 * @param array $a  The metadata of the first entity.
	 * @param array $b  The metadata of the second entity.
	 * @return int  How $a compares to $b.
	 */
	public static function mcmp(array $a, array $b) {
		if (isset($a['name']['en']) && isset($b['name']['en'])) {
			return strcasecmp($a['name']['en'], $b['name']['en']);
		} elseif (isset($a['name']['en'])) {
			return -1; /* Place name before entity ID. */
		} elseif (isset($b['name']['en'])) {
			return 1; /* Place entity ID after name. */
		} else {
			return strcasecmp($a['entityid'], $b['entityid']);
		}
	}


	/*
	 * This function will structure the idp list in a hierarchy based upon the tags.
	 */
	protected function idplistStructured($list) {
		# echo '<pre>'; print_r($list); exit;
		$slist = array();
		
		$order = $this->discoconfig->getValue('taborder');
		if (is_array($order)) {
			foreach($order AS $oe) {
				$slist[$oe] = array();
			}
		}
		
		$enableTabs = $this->discoconfig->getValue('tabs', NULL);
		
		foreach($list AS $key => $val) {
			$tags = array('misc');
			if (array_key_exists('tags', $val)) {
				$tags = $val['tags'];
			}
			foreach ($tags AS $tag) {
				if (!empty($enableTabs) && !in_array($tag, $enableTabs)) continue;
				$slist[$tag][$key] = $val;
			}
		}
		
		foreach($slist AS $tab => $tbslist) {
			uasort($slist[$tab], array('sspmod_discopower_PowerIdPDisco', 'mcmp'));
		}
		
		return $slist;
	}
	
	private function processFilter($filter, $entry, $default = TRUE) {
		if (in_array($entry['entityid'], $filter['entities.include'] )) return TRUE;
		if (in_array($entry['entityid'], $filter['entities.exclude'] )) return FALSE;
		
		if (array_key_exists('tags', $entry)) {
			foreach ($filter['tags.include'] AS $fe) {
				if (in_array($fe, $entry['tags'])) return TRUE;
			}
			foreach ($filter['tags.exclude'] AS $fe) {
				if (in_array($fe, $entry['tags'])) return FALSE;
			}
		}
		return $default;
	}
	
	protected function filterList($list) {
		
		try {
			$spmd = $this->metadata->getMetaData($this->spEntityId, 'saml20-sp-remote');
		} catch(Exception $e) {
			return $list;
		}
		
		if (!isset($spmd)) return $list;
		if (!array_key_exists('discopower.filter', $spmd)) return $list;
		$filter = $spmd['discopower.filter'];
		
		if (!array_key_exists('entities.include', $filter)) $filter['entities.include'] = array();
		if (!array_key_exists('entities.exclude', $filter)) $filter['entities.exclude'] = array();
		if (!array_key_exists('tags.include', $filter)) $filter['tags.include'] = array();
		if (!array_key_exists('tags.exclude', $filter)) $filter['tags.exclude'] = array();
		
		$defaultrule = TRUE;
		if ( array_key_exists('entities.include', $spmd['discopower.filter'] ) ||
			array_key_exists('tags.include', $spmd['discopower.filter'])) {
				
				$defaultrule = FALSE;
		}
		
		$returnlist = array();
		foreach ($list AS $key => $entry) {
			if ($this->processFilter($filter, $entry, $defaultrule)) {
				$returnlist[$key] = $entry;
			}
		}
		return $returnlist;
		
	}
	

	/**
	 * Handles a request to this discovery service.
	 *
	 * The IdP disco parameters should be set before calling this function.
	 */
	public function handleRequest() {

		$idp = $this->getTargetIdp();
		if($idp !== NULL) {
		
			if ($this->config->getBoolean('idpdisco.extDiscoveryStorage', NULL) != NULL) {
				$extDiscoveryStorage = $this->config->getBoolean('idpdisco.extDiscoveryStorage');
				$this->log('Choice made [' . $idp . '] (Forwarding to external discovery storage)');
				SimpleSAML_Utilities::redirect($extDiscoveryStorage, array(
					'entityID' => $this->spEntityId,
					'IdPentityID' => $idp,
					'returnIDParam' => $this->returnIdParam,
					'isPassive' => 'true',
					'return' => $this->returnURL
				));
				
			} else {
				$this->log('Choice made [' . $idp . '] (Redirecting the user back. returnIDParam=' . $this->returnIdParam . ')');
				SimpleSAML_Utilities::redirect($this->returnURL, array($this->returnIdParam => $idp));
			}
			
			return;
		}
		
		if ($this->isPassive) {
			$this->log('Choice not made. (Redirecting the user back without answer)');
			SimpleSAML_Utilities::redirect($this->returnURL);
			return;
		}

		/* No choice made. Show discovery service page. */
		$idpList = $this->getIdPList();
		$idpList = $this->idplistStructured($this->filterList($idpList));
		$preferredIdP = $this->getRecommendedIdP();

		$t = new SimpleSAML_XHTML_Template($this->config, 'discopower:disco-tpl.php', 'disco');
		$t->data['idplist'] = $idpList;
		$t->data['preferredidp'] = $preferredIdP;
		$t->data['return'] = $this->returnURL;
		$t->data['returnIDParam'] = $this->returnIdParam;
		$t->data['entityID'] = $this->spEntityId;
		$t->data['urlpattern'] = htmlspecialchars(SimpleSAML_Utilities::selfURLNoQuery());
		$t->data['rememberenabled'] = $this->config->getBoolean('idpdisco.enableremember', FALSE);
		$t->data['rememberchecked'] = $this->config->getBoolean('idpdisco.rememberchecked', FALSE);
		$t->data['defaulttab'] = $this->discoconfig->getValue('defaulttab', 0);
		$t->data['score'] = $this->discoconfig->getValue('score', 'quicksilver');
		$t->show();
	}


	/**
	 * Get the IdP entities saved in the common domain cookie.
	 *
	 * @return array  List of IdP entities.
	 */
	private function getCDC() {

		if (!isset($_COOKIE['_saml_idp'])) {
			return array();
		}

		$ret = (string)$_COOKIE['_saml_idp'];
		$ret = explode(' ', $ret);
		foreach ($ret as &$idp) {
			$idp = base64_decode($idp);
			if ($idp === FALSE) {
				/* Not properly base64 encoded. */
				return array();
			}
		}

		return $ret;
	}


	/**
	 * Save the current IdP choice to a cookie.
	 *
	 * This function overrides the corresponding function in the parent class,
	 * to add support for common domain cookie.
	 *
	 * @param string $idp  The entityID of the IdP.
	 */
	protected function setPreviousIdP($idp) {
		assert('is_string($idp)');

		if ($this->cdcDomain === NULL) {
			parent::setPreviousIdP($idp);
			return;
		}

		$list = $this->getCDC();

		$prevIndex = array_search($idp, $list, TRUE);
		if ($prevIndex !== FALSE) {
			unset($list[$prevIndex]);
		}
		$list[] = $idp;

		foreach ($list as &$value) {
			$value = base64_encode($value);
		}
		$newCookie = implode(' ', $list);

		while (strlen($newCookie) > 4000) {
			/* The cookie is too long. Remove the oldest elements until it is short enough. */
			$tmp = explode(' ', $newCookie, 2);
			if (count($tmp) === 1) {
				/*
				 * We are left with a single entityID whose base64
				 * representation is too long to fit in a cookie.
				 */
				break;
			}
			$newCookie = $tmp[1];
		}

		if ($this->cdcLifetime === NULL) {
			$expire = 0;
		} else {
			$expire = time() + $this->cdcLifetime;
		}

		setcookie('_saml_idp', $newCookie, $expire, '/', $this->cdcDomain, TRUE);
	}


	/**
	 * Retrieve the previous IdP the user used.
	 *
	 * This function overrides the corresponding function in the parent class,
	 * to add support for common domain cookie.
	 *
	 * @return string|NULL  The entity id of the previous IdP the user used, or NULL if this is the first time.
	 */
	protected function getPreviousIdP() {

		if ($this->cdcDomain === NULL) {
			return parent::getPreviousIdP();
		}

		$prevIdPs = $this->getCDC();
		while (count($prevIdPs) > 0) {
			$idp = array_pop($prevIdPs);
			$idp = $this->validateIdP($idp);
			if ($idp !== NULL) {
				return $idp;
			}
		}

		return NULL;
	}

}

?>