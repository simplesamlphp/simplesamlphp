<?php
class sspmod_logpeek_Syslog_parseLine{
	
	
	public static function isOlderThan($time, $logLine){
		return true;
	}
	
	public static function getUnixTime($logLine, $year = NULL){
		// I can read month and day and time from the file.
		// but I will assum year is current year retured by time().
		// Unless month and day in the file is bigger than current month and day,
		// I will then asume prevous year.
		// A better approach would be to get the year from last modification time (mtime) of the
		// file this record is taken from. But that require knowledge about the file.
		if(!$year){
			$now = getdate();
			$year = (int)$now['year'];
		}
		list($month, $day, $hour, $minute, $second) = sscanf($logLine, "%s %d %d:%d:%d ");
		$time = sprintf("%d %s %d %d:%d:%d", $day, $month, $year, $hour, $minute, $second);
		return strtotime($time);
	}
}
?>