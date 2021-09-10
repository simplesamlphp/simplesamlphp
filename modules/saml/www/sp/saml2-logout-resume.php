<?php

/**
 * Resume logging out, including out of all known associations (IdPs that have logged in SPs).
 *
 * This endpoint concerns itself only with an already received LogoutRequest.
 */

if (!isset($_REQUEST['id'])) {
    throw new \SimpleSAML\Error\BadRequest('Missing id parameter.');
}
$id = (string)$_REQUEST['id'];

$sid = \SimpleSAML\Auth\State::parseStateID($id);
if (!is_null($sid['url'])) {
    \SimpleSAML\Utils\HTTP::checkURLAllowed($sid['url']);
}

$state = \SimpleSAML\Auth\State::loadState($id, 'asConsumerLogout:finishLogout');

$sourceId = $state['saml:LogoutAssociations:authSourceId'];
$message = $state['saml:LogoutAssociations:logoutRequest'];
$idpEntityId = $state['saml:LogoutAssociations:idpEntityId'];

/** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
$source = \SimpleSAML\Auth\Source::getById($sourceId);
$spMetadata = $source->getMetadata();
$idpMetadata = $source->getIdPMetadata($idpEntityId);

$sessionIndexes = $message->getSessionIndexes();
$numLoggedOut = \SimpleSAML\Module\saml\SP\LogoutStore::logoutSessions($source->getAuthId(), $message->getNameId(), $sessionIndexes);
if ($numLoggedOut === false) {
    $source->handleLogout($message->getIssuer());
    $numLoggedOut = count($sessionIndexes);
}

$lr = \SimpleSAML\Module\saml\Message::buildLogoutResponse($spMetadata, $idpMetadata);
$lr->setRelayState($message->getRelayState());
$lr->setInResponseTo($message->getId());

if ($numLoggedOut < count($sessionIndexes)) {
    \SimpleSAML\Logger::warning('Logged out of ' . $numLoggedOut . ' of ' . count($sessionIndexes) . ' sessions.');
}

$dst = $idpMetadata->getEndpointPrioritizedByBinding('SingleLogoutService', [
    \SAML2\Constants::BINDING_HTTP_REDIRECT,
    \SAML2\Constants::BINDING_HTTP_POST
]);

$binding = \SAML2\Binding::getBinding($dst['Binding']);
if (isset($dst['ResponseLocation'])) {
    $dst = $dst['ResponseLocation'];
} else {
    $dst = $dst['Location'];
}
$lr->setDestination($dst);
$binding->setDestination($dst);

$binding->send($lr);
