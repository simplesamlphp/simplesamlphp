<?php

function tdate($input) {
	return date(DATE_RFC822, $input); 
}

function hours($input) {
	if ($input < 60) return number_format($input, 2) . ' sec';
	if ($input < 60*60) return number_format(($input/60),2) . ' min';
	if ($input < 24*60*60) return number_format(($input/(60*60)),2) . ' hours';
	return number_format($input/(24*60*60),2) . ' days';
	
}


function humanreadable($input) {
	 
	$output = "";
	$input = abs($input);
	
	if ($input >= (1024*1024*1024*1024*1024*1024*1024*100)) {
		$output = sprintf("%5ldEi", $input / (1024*1024*1024*1024*1024*1024) );		
	} else if ($input >= (1024*1024*1024*1024*1024*1024*10)) {
		$output = sprintf("%5.1fEi", $input / (1024.0*1024.0*1024.0*1024.0*1024.0*1024.0) );		
	} else if ($input >= (1024*1024*1024*1024*1024*1024)) {
		$output = sprintf("%5.2fEi", $input / (1024.0*1024.0*1024.0*1024.0*1024.0*1024.0) );	


	} else if ($input >= (1024*1024*1024*1024*1024*100)) {
		$output = sprintf("%5ldPi", $input / (1024*1024*1024*1024*1024) );		
	} else if ($input >= (1024*1024*1024*1024*1024*10)) {
		$output = sprintf("%5.1fPi", $input / (1024.0*1024.0*1024.0*1024.0*1024.0) );		
	} else if ($input >= (1024*1024*1024*1024*1024)) {
		$output = sprintf("%5.2fPi", $input / (1024.0*1024.0*1024.0*1024.0*1024.0) );	
		
	} else if ($input >= (1024*1024*1024*1024*100)) {
		$output = sprintf("%5ldTi", $input / (1024*1024*1024*1024) );
	} else if ($input >= (1024*1024*1024*1024*10)) {
		$output = sprintf("%5.1fTi", $input / (1024.0*1024.0*1024.0*1024.0) );	
	} else if ($input >= (1024*1024*1024*1024)) {
		$output = sprintf("%5.2fTi", $input / (1024.0*1024.0*1024.0*1024.0) );


	} else if ($input >= (1024*1024*1024*100)) {
		$output = sprintf("%5ldGi", $input / (1024*1024*1024) );		
	} else if ($input >= (1024*1024*1024*10)) {
		$output = sprintf("%5.1fGi", $input / (1024.0*1024.0*1024.0) );		
	} else if ($input >= (1024*1024*1024)) {
		$output = sprintf("%5.2fGi", $input / (1024.0*1024.0*1024.0) );	
		
	} else if ($input >= (1024*1024*100)) {
		$output = sprintf("%5ldMi", $input / (1024*1024) );
	} else if ($input >= (1024*1024*10)) {
		$output = sprintf("%5.1fM", $input / (1024.0*1024.0) );	
	} else if ($input >= (1024*1024)) {
		$output = sprintf("%5.2fMi", $input / (1024.0*1024.0) );		
		
	} else if ($input >= (1024 * 100)) {
		$output = sprintf("%5ldKi", $input / (1024) );
	} else if ($input >= (1024 * 10)) {
		$output = sprintf("%5.1fKi", $input / 1024.0 );
	} else if ($input >= (1024)) {
		$output = sprintf("%5.2fKi", $input / 1024.0 );
		
	} else {
		$output = sprintf("%5ld", $input );
	}

	return $output;
}




$config = SimpleSAML_Configuration::getInstance();

// Make sure that the user has admin access rights
SimpleSAML\Utils\Auth::requireAdmin();


$formats = array(
	'bytes' => 'humanreadable',
	'bytes_read' => 'humanreadable',
	'bytes_written' => 'humanreadable',
	'limit_maxbytes' => 'humanreadable',
	'time' => 'tdate',
	'uptime' => 'hours',
);

$statsraw = SimpleSAML_Memcache::getStats();

$stats = $statsraw;

foreach($stats AS $key => &$entry) {
	if (array_key_exists($key, $formats)) {
		$func = $formats[$key];
		foreach($entry AS $k => $val) {
			$entry[$k] = $func($val);
		}
	}

}

$t = new SimpleSAML_XHTML_Template($config, 'memcacheMonitor:memcachestat.tpl.php');
$rowTitles = array(
    'accepting_conns' => $t->noop('{memcacheMonitor:memcachestat:accepting_conns}'),
    'auth_cmds' => $t->noop('{memcacheMonitor:memcachestat:auth_cmds}'),
    'auth_errors' => $t->noop('{memcacheMonitor:memcachestat:auth_errors}'),
    'bytes' => $t->noop('{memcacheMonitor:memcachestat:bytes}'),
    'bytes_read' => $t->noop('{memcacheMonitor:memcachestat:bytes_read}'),
    'bytes_written' => $t->noop('{memcacheMonitor:memcachestat:bytes_written}'),
    'cas_badval' => $t->noop('{memcacheMonitor:memcachestat:cas_badval}'),
    'cas_hits' => $t->noop('{memcacheMonitor:memcachestat:cas_hits}'),
    'cas_misses' => $t->noop('{memcacheMonitor:memcachestat:cas_misses}'),
    'cmd_get' => $t->noop('{memcacheMonitor:memcachestat:cmd_get}'),
    'cmd_set' => $t->noop('{memcacheMonitor:memcachestat:cmd_set}'),
    'connection_structures' => $t->noop('{memcacheMonitor:memcachestat:connection_structures}'),
    'conn_yields' => $t->noop('{memcacheMonitor:memcachestat:conn_yields}'),
    'curr_connections' => $t->noop('{memcacheMonitor:memcachestat:curr_connections}'),
    'curr_items' => $t->noop('{memcacheMonitor:memcachestat:curr_items}'),
    'decr_hits' => $t->noop('{memcacheMonitor:memcachestat:decr_hits}'),
    'decr_misses' => $t->noop('{memcacheMonitor:memcachestat:decr_misses}'),
    'delete_hits' => $t->noop('{memcacheMonitor:memcachestat:delete_hits}'),
    'delete_misses' => $t->noop('{memcacheMonitor:memcachestat:delete_misses}'),
    'evictions' => $t->noop('{memcacheMonitor:memcachestat:evictions}'),
    'get_hits' => $t->noop('{memcacheMonitor:memcachestat:get_hits}'),
    'get_misses' => $t->noop('{memcacheMonitor:memcachestat:get_misses}'),
    'incr_hits' => $t->noop('{memcacheMonitor:memcachestat:incr_hits}'),
    'incr_misses' => $t->noop('{memcacheMonitor:memcachestat:incr_misses}'),
    'limit_maxbytes' => $t->noop('{memcacheMonitor:memcachestat:limit_maxbytes}'),
    'listen_disabled_num' => $t->noop('{memcacheMonitor:memcachestat:listen_disabled_num}'),
    'pid' => $t->noop('{memcacheMonitor:memcachestat:pid}'),
    'pointer_size' => $t->noop('{memcacheMonitor:memcachestat:pointer_size}'),
    'rusage_system' => $t->noop('{memcacheMonitor:memcachestat:rusage_system}'),
    'rusage_user' => $t->noop('{memcacheMonitor:memcachestat:rusage_user}'),
    'threads' => $t->noop('{memcacheMonitor:memcachestat:threads}'),
    'time' => $t->noop('{memcacheMonitor:memcachestat:time}'),
    'total_connections' => $t->noop('{memcacheMonitor:memcachestat:total_connections}'),
    'total_items' => $t->noop('{memcacheMonitor:memcachestat:total_items}'),
    'uptime' => $t->noop('{memcacheMonitor:memcachestat:uptime}'),
    'version' => $t->noop('{memcacheMonitor:memcachestat:version}'),
);
$t->data['title'] = 'Memcache stats';
$t->data['rowtitles'] = $rowTitles;
$t->data['table'] = $stats;
$t->data['statsraw'] = $statsraw;
$t->show();
