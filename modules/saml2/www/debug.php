<?php

/**
 * Endpoint for debugging sent SAML-messages.
 *
 * This endpoint will display the message to the user before passing it
 * to its destination.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

$globalConfig = SimpleSAML_Configuration::getInstance();

if (array_key_exists('SAMLRequest', $_REQUEST)) {
	$type = 'SAMLRequest';
} elseif (array_key_exists('SAMLResponse', $_REQUEST)) {
	$type = 'SAMLResponse';
} else {
	throw new SimpleSAML_Error_BadRequest('Unknown SAML2 message type.');
}

$message = $_REQUEST[$type];

$message = @base64_decode($message);
if ($message === FALSE) {
	throw new SimpleSAML_Error_BadRequest('Unable to base64-decode message.');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$message = @gzinflate($message);
	if ($message === FALSE) {
		throw new SimpleSAML_Error_BadRequest('Unable to gzinflate message.');
	}
}

$document = new DOMDocument();
if (!$document->loadXML($message)) {
	throw new SimpleSAML_Error_BadRequest('Unable to parse XML.');
}
$root = $document->firstChild;

if (!$root->hasAttribute('Destination')) {
	throw new SimpleSAML_Error_BadRequest('Missing Destination-attribute on root element.');
}
$realDestination = $root->getAttribute('Destination');

SimpleSAML_Utilities::formatDOMElement($root);
$message = $document->saveXML($root);


switch($_SERVER['REQUEST_METHOD']) {
case 'GET':
	$queryString = $_SERVER['QUERY_STRING'];

	if (strpos($realDestination, '?') === FALSE) {
		$url = $realDestination . '?' . $queryString;
	} else {
		$url = $realDestination . '&' . $queryString;
	}

	$t = new SimpleSAML_XHTML_Template($globalConfig, 'httpredirect-debug.php');
	$t->data['url'] = $url;
	$t->data['message'] = htmlspecialchars($message);
	$t->show();
	exit();

case 'POST':
	$post = $_POST;

	$t = new SimpleSAML_XHTML_Template($globalConfig, 'post-debug.php');

	$t->data['post'] = $post;
	$t->data['destination'] = $realDestination;
	$t->data['responseHTML'] = htmlspecialchars($message);
	$t->show();
	exit();

default:
	throw new SimpleSAML_Error_BadRequest('Unexpected request method: ' . var_export($_SERVER['REQUEST_METHOD'], TRUE));
}

?>