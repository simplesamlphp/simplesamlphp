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
	
	
	public static function mcmp($a, $b) {
#		echo 'aort'; exit;
	    if ($a['name']['en'] == $b['name']['en']) {
	        return 0;
	    }
	    return ($a['name']['en'] < $b['name']['en']) ? -1 : 1;
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
		$t->show();
	}
}

?>