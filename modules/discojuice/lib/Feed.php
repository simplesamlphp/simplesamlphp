<?php

/**
 * ...
 */
class sspmod_discojuice_Feed {
	
	protected $config, $djconfig;
	
	protected $excludes, $override, $insert, $idplist;
	
	protected $metadata;
	protected $feed;
	
	protected $contrytags, $countryTLDs;
	
	function __construct() {
	
		$this->config = SimpleSAML_Configuration::getInstance();
		$this->djconfig = SimpleSAML_Configuration::getOptionalConfig('discojuicefeed.php');

		$metadatah = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();
		$this->metadata = $metadatah->getList('saml20-idp-remote');
		
		$this->idplist = $this->getIdPList();
		
		SimpleSAML_Logger::info('IdP List contained : ' . count($this->idplist)  . ' entries.');
		
		$this->excludes = array_flip($this->djconfig->getValue('exclude'));
		$this->insert = $this->djconfig->getValue('insert');
		$this->overrides = $this->djconfig->getValue('overrides');
		
		$this->countrytags = array(
			'croatia' => 'HR',
			'czech' => 'CZ',
			'denmark' => 'DK',
			'finland' => 'FI',
			'france' => 'FR',
			'germany' => 'DE',
			'greece' => 'GR',
			'ireland' => 'IE',
			'italy' => 'IT',
			'luxembourg' => 'LU',
			'hungary' => 'HU',
			'netherlands' => 'NL',
			'norway' => 'NO',
			'portugal' => 'PT',
			'poland' => 'PL',
			'slovenia' => 'SI',
			'spain' => 'ES',
			'sweden' => 'SE',
			'switzerland' => 'CH',
			'turkey' => 'TR',
			'us' => 'US',
			'uk' => 'GB',
		);
		
		$this->countryTLDs = array(
			'lp.' => 'PL',
			'uh.' => 'HU',
			'es.' => 'SE',
			'ed.' => 'DE',
			'if.' => 'FI',
			'zc.' => 'CZ',
			'rt.' => 'TR',
			'kd.' => 'DK',
			'on.' => 'NO',
			'ude.' => 'US',
			'ku.oc.' => 'GB',
		);
	}
	
	public function store() {
		$datadir = $this->config->getPathValue('datadir', 'data/');
		
		if (!is_dir($datadir))
			throw new Exception('Data directory [' . $datadir. '] does not exist');
		if (!is_writable($datadir))
			throw new Exception('Data directory [' . $datadir. '] is not writable');
		
		$djdatadir = $datadir . 'discojuice/';
		if (!is_dir($djdatadir)) {
			mkdir($djdatadir);
		}
		
		$djdatafile = $djdatadir . 'discojuice.cache';
		
		$data = $this->getJSON();
		
		file_put_contents($djdatafile, json_encode($data));
		
	}
	
	
	public function read() {
		$djdatafile = $this->config->getPathValue('datadir', 'data/')  . 'discojuice/'  . 'discojuice.cache';

		if (!file_exists($djdatafile)) {
			error_log('Did not find cached version, generating content again...');
			return json_encode($this->getJSON());
		}
		
		return file_get_contents($djdatafile);
	}
	
	private function exclude($e) {
		if ($this->excludes === NULL) return FALSE;
		return (array_key_exists($e, $this->excludes));
	}
	
	protected function getIdPList() {
		$api = $this->djconfig->getValue('idplistapi', NULL);
		if (empty($api)) return array();
		
		$result = array();
		
		$apiresult = json_decode(file_get_contents($api), TRUE);
		if ($apiresult['status'] === 'ok') {
			foreach($apiresult['data'] AS $idp) {
				$result[$idp] = 1;
			}
		}
		return $result;
	}
	
	private function process() {
		
		$this->feed = array();
		$this->merge();
		
		foreach($this->metadata AS $m) {
			if ($this->exclude($m['entityid'])) continue;
			
			$this->feed[] = $this->processEntity($m);
		}
		
		if (!empty($this->insert)) {
			foreach($this->insert AS $i) {
				$this->feed[] = $i;
			}
		}

	}
	
	protected function merge() {
		$mergeendpoints = $this->djconfig->getValue('mergeEndpoints', NULL);
		SimpleSAML_Logger::info('Processing merge endpoint: ' . var_export($mergeendpoints, TRUE));
		
		if ($mergeendpoints === NULL) return;
		if (!is_array($mergeendpoints)) return;
		foreach($mergeendpoints AS $me) {
			SimpleSAML_Logger::info('Processing merge endpoint: ' . $me);
			$newlist = json_decode(file_get_contents($me), TRUE);
			$this->feed = array_merge($this->feed, $newlist);
		}
	}
	
	
	private function processEntity($m) {
		
		$data = array('entityID' => $m['entityid']);
		
		$this->getCountry($data, $m);
		$this->getTitle($data, $m);
		$this->getOverrides($data, $m);
		$this->getGeo($data, $m);
		
		if (!empty($this->idplist)) {
			$this->islisted($data, $m);
		}


		return $data;
	}
	
	public function getJSON() {
		$this->process();
		return $this->feed;
	}
	
	
	protected function islisted(&$data, $m) {
		$weight = 0;
		if (array_key_exists('weight', $data)) $weight = $data['weight'];
		
		if (!array_key_exists($m['entityid'], $this->idplist)) {
			#echo 'Match for ' . $m['entityid'];
			$weight += 2;
		}
		$data['weight'] = $weight;
		
#		echo '<pre>';
#		print_r($this->idplist); exit;
	}
	
	
	protected function getGeo(&$data, $m) {
		
		// Do not lookup Geo locations from IP if geo location is already set.
		if (array_key_exists('geo', $data)) return;
	
		// Look for SingleSignOnService endpoint.
		if (!empty($m['SingleSignOnService']) ) {
			
			$m['metadata-set'] = 'saml20-idp-remote';
			$mc = SimpleSAML_Configuration::loadFromArray($m);
			$endpoint = $mc->getDefaultEndpoint('SingleSignOnService');

			try {
				$host = parse_url($endpoint['Location'], PHP_URL_HOST); if (empty($host)) return;
				$ip = gethostbyname($host); 
				
				if (empty($ip)) return;
				if ($ip === $host) return;
				
				$capi = new sspmod_discojuice_Country($ip);
				
				if (empty($data['geo'])) {
					$geo = $capi->getGeo();
					$geos = explode(',', $geo);
					$data['geo'] = array('lat' => $geos[0], 'lon' => $geos[1]);
				}

			} catch(Exception $e) {
				error_log('Error looking up geo coordinates: ' . $e->getMessage());
			}
			
		}

		
	}
	
	
	protected function getCountry(&$data, $m) {
		if (!empty($m['tags'])) {
		
			foreach($m['tags'] AS $tag) {
				if (array_key_exists($tag, $this->countrytags)) {
					$data['country'] = $this->countrytags[$tag];
					return;
				}
			}
		}
		
		
		$c = self::countryFromURL($m['entityid']);
		if (!empty($c)) { $data['country'] = $c; return; }

		if (!empty($m['SingleSignOnService']) ) {
			
			SimpleSAML_Logger::debug('SingleSignOnService found');
			
			$m['metadata-set'] = 'saml20-idp-remote';
			$mc = SimpleSAML_Configuration::loadFromArray($m);
			
			$endpoint = $mc->getDefaultEndpoint('SingleSignOnService');
			
			error_log('Endpoint: ' . var_export($endpoint, TRUE));
			
			$c = $this->countryFromURL($endpoint['Location']);
			if (!empty($c)) { $data['country'] = $c; return; }
				
			try {
				$host = parse_url($endpoint['Location'], PHP_URL_HOST);
				$ip = gethostbyname($host);
				$capi = new sspmod_discojuice_Country($ip);

				$region = $capi->getRegion();
				
				if (preg_match('|^([A-Z][A-Z])/|', $region, $match)) {
					$data['country'] = $match[1];

				}
			} catch(Exception $e) {}			
			
		}
		


		return null;
	}
	
	protected function getTitle(&$data, $m) {
		if(isset($m['name']) && is_string($m['name'])) {
			$data['title'] = $m['name'];
		} else if(isset($m['name']) && array_key_exists('en', $m['name'])) {
			$data['title'] = $m['name']['en'];
		} else if(isset($m['name']) && is_array($m['name'])) {
			$data['title'] = array_pop($m['name']);
		} else if (isset($m['name']) && is_string($m['name'])) {
			$data['title'] = $m['name'];	
		} else if (isset($m['OrganizationName']) && isset($m['OrganizationName']['en'])) {
			$data['title'] = $m['OrganizationName']['en'];
		} else if (isset($m['OrganizationName']) && is_array($m['OrganizationName'])) {
			$data['title'] = array_pop($m['OrganizationName']);
		} else {
			$data['title'] = substr($m['entityid'], 0, 20);
			$data['weight'] = 9;
		}
	}
	
	protected function getOverrides(&$data, $m) {
		if (empty($this->overrides)) return;
		if (empty($this->overrides[$m['entityid']])) return;
		
		$override = $this->overrides[$m['entityid']];
		
		foreach($override AS $k => $v) {
			$data[$k] = $v;
		}
		
	}
	
		
	protected static function prefix($word, $prefix) {
		if ( strlen($word) < strlen($prefix)) {
				$tmp = $prefix;
				$prefix = $word;
				$word = $tmp;
		}
	
		$word = substr($word, 0, strlen($prefix));
	
		if ($prefix == $word) {
				return 1;
		}
	
		return 0;
	}

	
	protected function countryFromURL($entityid) {
		try {
			$pu = parse_url($entityid, PHP_URL_HOST);			
			if (!empty($pu)) {
				$rh = strrev($pu); 
				// error_log('Looking up TLD : ' . $rh);
				 
				foreach($this->countryTLDs AS $domain => $country) {
					if (self::prefix($domain, $rh)) {
						error_log('Looking up TLD : ' . $rh . ' matched ' . $country);
						return $country;
					}
				}
			}	
		} catch(Exception $e) {
		}
		return null;
	}

	

}

