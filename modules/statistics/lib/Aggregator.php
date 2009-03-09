<?php
/*
 * @author Andreas Åkre Solberg <andreas.solberg@uninett.no>
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_statistics_Aggregator {

	private $statconfig;
	private $statdir;
	private $inputfile;
	private $statrules;
	private $offset;

	private $fromcmdline;

	/**
	 * Constructor
	 */
	public function __construct($fromcmdline = FALSE) {
	
		$this->fromcmdline = $fromcmdline;
		$this->statconfig = SimpleSAML_Configuration::getConfig('module_statistics.php');
		
		$this->statdir = $this->statconfig->getValue('statdir');
		$this->inputfile = $this->statconfig->getValue('inputfile');
		$this->statrules = $this->statconfig->getValue('statrules');
		$this->offset = $this->statconfig->getValue('offset', 0);
	}
	
	public function dumpConfig() {
		
		echo 'Statistics directory   : ' . $this->statdir . "\n";
		echo 'Input file             : ' . $this->inputfile . "\n";
		echo 'Offset                 : ' . $this->offset . "\n";
		
	}
	


	public function aggregate($debug = FALSE) {
		
		if (!is_dir($this->statdir)) 
			throw new Exception('Statistics module: output dir do not exists [' . $this->statdir . ']');
		
		if (!file_exists($this->inputfile)) 
			throw new Exception('Statistics module: input file do not exists [' . $this->inputfile . ']');
		
		
		$file = fopen($this->inputfile, 'r');
		#$logfile = file($this->inputfile, FILE_IGNORE_NEW_LINES );
		
		
		$logparser = new sspmod_statistics_LogParser(
			$this->statconfig->getValue('datestart', 0), $this->statconfig->getValue('datelength', 15), $this->statconfig->getValue('offsetspan', 44)
		);
		$datehandler = new sspmod_statistics_DateHandler($this->offset);
		
		$results = array();
		
		$i = 0;
		// Parse through log file, line by line
		while (!feof($file)) {
			
			$logline = fgets($file, 4096);
			
			// Continue if STAT is not found on line.
			if (!preg_match('/STAT/', $logline)) continue;
			$i++;
			
			// Parse log, and extract epoch time and rest of content.
			$epoch = $logparser->parseEpoch($logline);
			$content = $logparser->parseContent($logline);
			$action = trim($content[5]);

			if ($this->fromcmdline && ($i % 10000) == 0) {
				echo("Read line " . $i . "\n");
			}

			
			if ($debug) {
			
			
				echo("----------------------------------------\n");
				echo('Log line: ' . $logline . "\n");
				echo('Date parse [' . substr($logline, 0, $this->statconfig->getValue('datelength', 15)) . '] to [' . date(DATE_RFC822, $epoch) . ']' . "\n");
				print_r($content);
				if ($i >= 13) exit;
			}
			
			
			// Iterate all the statrules from config.
			foreach ($this->statrules AS $rulename => $rule) {
			
				// echo 'Comparing action: [' . $rule['action'] . '] with [' . $action . ']' . "\n";
			
				$timeslot = $datehandler->toSlot($epoch, $rule['slot']);
				$fileslot = $datehandler->toSlot($epoch, $rule['fileslot']); //print_r($content);
				
				if (isset($rule['action']) && ($action !== $rule['action'])) continue;
				
		
				$difcol = trim($content[$rule['col']]); // echo '[...' . $difcol . '...]';
		
				if (!isset($results[$rulename][$fileslot][$timeslot]['_'])) $results[$rulename][$fileslot][$timeslot]['_'] = 0;
				if (!isset($results[$rulename][$fileslot][$timeslot][$difcol])) $results[$rulename][$fileslot][$timeslot][$difcol] = 0;
		
				$results[$rulename][$fileslot][$timeslot]['_']++;
				$results[$rulename][$fileslot][$timeslot][$difcol]++;
				
			}
		}
		return $results;		
	}
	
	
	public function store($results) {
	
		$datehandler = new sspmod_statistics_DateHandler($this->offset);
	
		// Iterate the first level of results, which is per rule, as defined in the config.
		foreach ($results AS $rulename => $ruleresults) {
		
			
			$filenos = array_keys($ruleresults);
			$lastfile = $filenos[count($filenos)-1];
			
			// Iterate the second level of results, which is the fileslot.
			foreach ($ruleresults AS $fileno => $fileres) {
			
				$slotlist = array_keys($fileres);
				
				$maxslot = $slotlist[count($slotlist)-1];
				#print_r($slotlist); 
		
				// Get start and end slot number within the file, based on the fileslot.
				$start = (int)$datehandler->toSlot($datehandler->fromSlot($fileno, $this->statrules[$rulename]['fileslot']), $this->statrules[$rulename]['slot']);
				$end = (int)$datehandler->toSlot($datehandler->fromSlot($fileno+1, $this->statrules[$rulename]['fileslot']), $this->statrules[$rulename]['slot']);
		
				// Fill in missing entries and sort file results
				$filledresult = array();
				for ($slot = $start; $slot < $end; $slot++) {
					#print_r(gettype($slot));
					if (array_key_exists($slot,  $fileres)) {
						$filledresult[$slot] = $fileres[$slot];
					} else {
						#echo('SLot [' . $slot . '] of [' . $maxslot . ']' . "\n");
						if ($lastfile == $fileno && $slot > $maxslot) {
						#if ($slot > $maxslot) {
							$filledresult[$slot] = array('_' => NULL);
						} else {
							$filledresult[$slot] = array('_' => 0);
						}				
					}
					#print_r($filledresult[$slot]);
#					 = (isset($fileres[$slot])) ? $fileres[$slot] : array('_' => NULL);
				}
				
				#print_r($filledresult); exit;
				
				// store file
				file_put_contents($this->statdir . '/' . $rulename . '-' . $fileno . '.stat', serialize($filledresult), LOCK_EX );
			}
		}
	
	}


}

?>