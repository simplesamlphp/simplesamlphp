<?php



$path_extra = dirname(dirname(__FILE__)) . '/lib';

$path = ini_get('include_path');
$path = $path_extra . PATH_SEPARATOR . $path;
ini_set('include_path', $path);

require_once('SimpleSAML/Configuration.php');

SimpleSAML_Configuration::init(dirname(dirname(__FILE__)) . '/config');




?>