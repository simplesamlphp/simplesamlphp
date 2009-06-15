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
	
	protected function getDST($timestamp) {
		if (idate('I', $timestamp)) return 3600;
		return 0;
	}

	public function toSlot($epoch, $slotsize) {
		$dst = $this->getDST($epoch);
		return floor( ($epoch + $this->offset + $dst) / $slotsize);
	}

	public function fromSlot($slot, $slotsize) {
		// echo("slot $slot slotsize $slotsize offset  " . $this->offset);
		// throw new Exception();
		$temp = $slot*$slotsize - $this->offset;
		$dst = $this->getDST($temp);
		return $slot*$slotsize - $this->offset - $dst;
	}

	public function prettyDateEpoch($epoch, $dateformat) {
		return date($dateformat, $epoch);
	}

	public function prettyDateSlot($slot, $slotsize, $dateformat) {
		return $this->prettyDateEpoch($this->fromSlot($slot, $slotsize), $dateformat);

	}
	
	public function prettyHeader($from, $to, $slotsize, $dateformat) {
		$text = $this->prettyDateSlot($from, $slotsize, $dateformat);
		$text .= ' to ';
		$text .= $this->prettyDateSlot($to, $slotsize, $dateformat);
		return $text;
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

