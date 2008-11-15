<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_DateHandler {

	private $offset;

	/**
	 * Constructor
	 *
	 * @param array $offset 	Date offset
	 */
	public function __construct($offset) {
		$this->offset = $offset;
	}

	public function toSlot($epoch, $slotsize) {
		return floor( ($epoch + $this->offset) / $slotsize);
	}

	public function fromSlot($slot, $slotsize) {
		return $slot*$slotsize - $this->offset;
	}

	public function prettyDateEpoch($epoch, $dateformat) {
		return date($dateformat, $epoch);
	}

	public function prettyDateSlot($slot, $slotsize, $dateformat) {
		return $this->prettyDateEpoch($this->fromSlot($slot, $slotsize), $dateformat);

	}

}

// 	$datestr = substr($logline,0,$datenumbers);
// 	#$datestr = substr($logline,0,23);
// 	$timestamp = parse15($datestr) + $offset;
// 	$restofline = substr($logline,$datenumbers+1);
// 	$restcols = split(' ', $restofline);
// 	$action = $restcols[5];
	
// 	print_r($timestamp);
// 	print_r($restcols); if ($i++ > 5) exit;

?>