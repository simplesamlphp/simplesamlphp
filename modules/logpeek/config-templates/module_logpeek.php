<?php
/* 
 * Configuration for the module logpeek.
 * 
 * $Id $
 */

$config = array (
	'logfile'	=> '/var/log/simplesamlphp.log',
	'lines'		=> 1500,
	// Read block size. 8192 is max, limited by fread.
	'blocksz'	=> 8192,
);

?>
