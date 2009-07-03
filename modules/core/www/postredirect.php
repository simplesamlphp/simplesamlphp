<?php

/**
 * This page provides a way to create a redirect to a POST request.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

if (!array_key_exists('RedirId', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing RedirId parameter.');
}

$id = $_REQUEST['RedirId'];

$session = SimpleSAML_Session::getInstance();
$postData = $session->getData('core_postdatalink', $id);

if ($postData === NULL) {
	/* The post data is missing, probably because it timed out. */
	throw new Exception('The POST data we should restore was lost.');
}
assert('is_array($postData)');
assert('array_key_exists("url", $postData)');
assert('array_key_exists("post", $postData)');

$url = $postData['url'];
$post = $postData['post'];

SimpleSAML_Utilities::postRedirect($url, $post);

?>