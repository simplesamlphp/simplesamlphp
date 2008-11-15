<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_LogParser {

	private $datestart;
	private $datelength;
	private $offset;

	/**
	 * Constructor
	 *
	 * @param $datestart   At which char is the date starting
	 * @param $datelength  How many characters is the date (on the b
	 * @param $offset      At which char is the rest of the entries starting
	 */
	public function __construct($datestart, $datelength, $offset) {
		$this->datestart = $datestart;
		$this->datelength = $datelength;
		$this->offset = $offset;
	}

	public function parseEpoch($line) {
		$epoch = strtotime(substr($line, 0, $this->datelength));
		echo 'debug   ' . $line . "\n";
		echo 'debug   [' . substr($line, 0, $this->datelength)  . '] => [' . $epoch . ']' . "\n";
		return $epoch;
	}

	public function parseContent($line) {
		$contentstr = substr($line, $this->offset);
		$content = split(' ', $contentstr);
		return $content;
	}
	
	
	# Aug 27 12:54:25 ssp 5 STAT [5416262207] saml20-sp-SSO urn:mace:feide.no:services:no.uninett.wiki-feide sam.feide.no NA
	# 
	#Oct 30 11:07:14 www1 simplesamlphp-foodle[12677]: 5 STAT [200b4679af] saml20-sp-SLO spinit urn:mace:feide.no:services:no.feide.foodle sam.feide.no
	
	function parse15($str) {
		$di = date_parse($str);
		$datestamp = mktime($di['hour'], $di['minute'], $di['second'], $di['month'], $di['day']);	
		return $datestamp;
	}
	
	function parse23($str) {
		$timestamp = strtotime($str);
		return $timestamp;
	}


}

?>