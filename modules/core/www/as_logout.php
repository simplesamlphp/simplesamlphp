<?php

/**
 * Endpoint for logging out in with an authentication source.
 *
 * @package SimpleSAMLphp
 */

if (!isset($_REQUEST['ReturnTo']) || !is_string($_REQUEST['ReturnTo'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing ReturnTo parameter.');
}

if (!isset($_REQUEST['AuthId']) || !is_string($_REQUEST['AuthId'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing AuthId parameter.');
}


if( $possible_vetos = $config->getArray('logout_possible_veto_php_file',null)) {
    foreach( $possible_vetos as $filename  ) {
        include $filename;
    }
}



$as = new \SimpleSAML\Auth\Simple($_REQUEST['AuthId']);
$as->logout(\SimpleSAML\Utils\HTTP::checkURLAllowed($_REQUEST['ReturnTo']));
