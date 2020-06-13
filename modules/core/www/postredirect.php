<?php

/**
 * This page provides a way to create a redirect to a POST request.
 *
 * @package SimpleSAMLphp
 */

use Exception;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Configuration;
use SimpleSAML\Error;
use SimpleSAML\Session;
use SimpleSAML\Utils;
use SimpleSAML\XHTML\Template;

if (array_key_exists('RedirId', $_REQUEST)) {
    $postId = $_REQUEST['RedirId'];
    $session = Session::getSessionFromRequest();
} elseif (array_key_exists('RedirInfo', $_REQUEST)) {
    $encData = base64_decode($_REQUEST['RedirInfo']);

    if (empty($encData)) {
        throw new Error\BadRequest('Invalid RedirInfo data.');
    }

    list($sessionId, $postId) = explode(':', Utils\Crypto::aesDecrypt($encData));

    if (empty($sessionId) || empty($postId)) {
        throw new Error\BadRequest('Invalid session info data.');
    }

    $session = Session::getSession($sessionId);
} else {
    throw new Error\BadRequest('Missing redirection info parameter.');
}

if ($session === null) {
    throw new Exception('Unable to load session.');
}

$postData = $session->getData('core_postdatalink', $postId);

if ($postData === null) {
    // The post data is missing, probably because it timed out
    throw new Exception('The POST data we should restore was lost.');
}

$session->deleteData('core_postdatalink', $postId);

Assert::isArray($postData);
Assert::keyExists($postData, 'url');
Assert::keyExists($postData, 'post');

if (!Utils\HTTP::isValidURL($postData['url'])) {
    throw new Error\Exception('Invalid destination URL.');
}

$config = Configuration::getInstance();
$template = new Template($config, 'post.php');
$template->data['destination'] = $postData['url'];
$template->data['post'] = $postData['post'];
$template->send();
exit(0);
