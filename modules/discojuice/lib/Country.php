<?php

/**
 * ...
 */
class sspmod_discojuice_Country {

	const CACHETIME = 86400; 

	
	/* Instance of sspmod_core_Storage_SQLPermanentStorage
	 * 
	 * key1		calendar URL
	 * key2		NULL
	 * type		'calendar'
	 *
	 */
	public $store;
	public $ip;
	
	public function __construct($ip = NULL) {
		if (is_null($ip)) $ip = $_SERVER['REMOTE_ADDR'];
		
		if (empty($ip))
			throw new Exception('Trying to use the TimeZone class without specifying an IP address');
		$this->ip = $ip;
		
		$this->store = new sspmod_core_Storage_SQLPermanentStorage('iptimezone');

	}

	public function lookupRegion($region) {
		
		if ($this->store->exists('region', $region, NULL)) {
			SimpleSAML_Logger::debug('IP Geo location: Found region [' . $region . '] in cache.');
			return $this->store->getValue('region', $region, NULL);
		}
		
		SimpleSAML_Logger::debug('Lookup region');
		$rawdata = file_get_contents('http://freegeoip.net/tz/json/' . $region);
		
		if (empty($rawdata)) throw new Exception('Error looking up IP geo location for [' . $ip . ']');
		$data = json_decode($rawdata, TRUE);
		if (empty($data)) throw new Exception('Error decoding response from looking up IP geo location for [' . $ip . ']');
		
		if (empty($data['timezone'])) throw new Exception('Could not get TimeZone from IP lookup');
		
		$timezone = $data['timezone'];
		
		SimpleSAML_Logger::debug('IP Geo location: Store region [' . $region . '] in cache: ' . $timezone);
		$this->store->set('region', $region, NULL, $timezone);
		
		return $timezone;	
	}
	
	public function getRegion() {
		return $this->lookupIP($this->ip);		
	}
	
	public function getGeo() {
		return $this->lookupGeo($this->ip);		
	}
	
	public function lookupGeo($ip) {

		if ($this->store->exists('geo', $ip, NULL)) {
			SimpleSAML_Logger::debug('IP Geo location (geo): Found ip [' . $ip . '] in cache.');
			$stored =  $this->store->getValue('geo', $ip, NULL);
			if ($stored === NULL) throw new Exception('Got negative cache for this IP');
			return $stored;
		}
		
		SimpleSAML_Logger::debug('Lookup IP');
		$rawdata = file_get_contents('http://freegeoip.net/json/' . $ip);
		
		if (empty($rawdata)) throw new Exception('Error looking up IP geo location for [' . $ip . ']');
		$data = json_decode($rawdata, TRUE);
		if (empty($data)) throw new Exception('Error decoding response from looking up IP geo location for [' . $ip . ']');
		
		if (empty($data['longitude'])) {
			$this->store->set('geo', $ip, NULL, NULL);
		}
		
		
		if (empty($data['longitude'])) throw new Exception('Could not get longitude from IP lookup');
		if (empty($data['latitude'])) throw new Exception( 'Could not get latitude from IP lookup');
		
		$geo = $data['latitude'] . ',' . $data['longitude'];
		
		SimpleSAML_Logger::debug('IP Geo location: Store ip [' . $ip . '] in cache: ' . $geo);
		$this->store->set('geo', $ip, NULL, $geo);
		
		return $geo;
	}
	
	public function lookupIP($ip) {

		if ($this->store->exists('ip', $ip, NULL)) {
			SimpleSAML_Logger::debug('IP Geo location: Found ip [' . $ip . '] in cache.');
			return $this->store->getValue('ip', $ip, NULL);
		}
		
		SimpleSAML_Logger::debug('Lookup IP [' . $ip. ']');
		$rawdata = file_get_contents('http://freegeoip.net/json/' . $ip);
		
		if (empty($rawdata)) throw new Exception('Error looking up IP geo location for [' . $ip . ']');
		$data = json_decode($rawdata, TRUE);
		if (empty($data)) throw new Exception('Error decoding response from looking up IP geo location for [' . $ip . ']');
		
		SimpleSAML_Logger::info('Country code: ' . $data['country_code']);
		
		if (empty($data['country_code'])) throw new Exception('Could not get Coutry Code from IP lookup : ' . var_export($data, TRUE));
		if (empty($data['region_code'])) $region = 'NA';
		
		$region = $data['country_code'] . '/' . $data['region_code'];
		
		SimpleSAML_Logger::debug('IP Geo location: Store ip [' . $ip . '] in cache: ' . $region);
		$this->store->set('ip', $ip, NULL, $region);
		
		return $region;
	}
	
	public function getTimeZone() {
		$tz = 'Europe/Amsterdam';
		
		try {
			$tz = $this->lookupRegion($this->lookupIP($this->ip));
		} catch(Exception $e) {
			$tz = 'Europe/Amsterdam';
		}
		
		return $tz;
	}
	

	
	public function getSelectedTimeZone() {
	
	
		if (isset($_REQUEST['timezone'])) {		
			return $_REQUEST['timezone'];
		}
		return $this->getTimeZone();
	}
	
	public function getHTMLList($default = NULL, $autosubmit = FALSE) {

		$tzlist = DateTimeZone::listIdentifiers();
		$tzlist = array_reverse($tzlist);
		$thiszone = $this->getTimeZone();
		
		if (is_null($default)) $default = $thiszone;
		
		$a = '';
		if ($autosubmit) $a = "onchange='this.form.submit()' ";
		
		$html = '<select ' .  $a . 'name="timezone">' . "\n";
		foreach($tzlist AS $tz) {
			if ($tz == $default) {
				$html .= ' <option selected="selected" value="' . htmlspecialchars($tz) . '">' . htmlspecialchars($tz) . '</option>' . "\n";				
			} else {
				$html .= ' <option value="' . htmlspecialchars($tz) . '">' . htmlspecialchars($tz) . '</option>' . "\n";				
			}

		}
		$html .= '</select>' . "\n";
		return $html;
	}
	

}
