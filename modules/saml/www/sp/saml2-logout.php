<?php

/**
 * Logout endpoint handler for SAML SP authentication client.
 *
 * This endpoint handles both logout requests and logout responses.
 */

use Exception;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
use SimpleSAML\Module;
use SimpleSAML\SAML2\Binding;
use SimpleSAML\SAML2\Constants;
use SimpleSAML\SAML2\Exception\Protocol\UnsupportedBindingException;
use SimpleSAML\SAML2\SOAP;
use SimpleSAML\SAML2\XML\saml\EncryptedID;
use SimpleSAML\SAML2\XML\saml\Issuer;
use SimpleSAML\SAML2\XML\samlp\LogoutResponse;
use SimpleSAML\SAML2\XML\samlp\LogoutRequest;
use SimpleSAML\Utils;

if (!array_key_exists('PATH_INFO', $_SERVER)) {
    throw new Error\BadRequest('Missing authentication source ID in logout URL');
}

$sourceId = substr($_SERVER['PATH_INFO'], 1);

/** @var \SimpleSAML\Module\saml\Auth\Source\SP $source */
$source = Auth\Source::getById($sourceId);
if ($source === null) {
    throw new Exception('Could not find authentication source with id ' . $sourceId);
} elseif (!($source instanceof \SimpleSAML\Module\saml\Auth\Source\SP)) {
    throw new Error\Exception('Source type changed?');
}

try {
    $binding = Binding::getCurrentBinding();
} catch (UnsupportedBindingException $e) {
    throw new Error\Error('SLOSERVICEPARAMS', $e, 400);
}
$message = $binding->receive();

$issuer = $message->getIssuer();
if ($issuer instanceof Issuer) {
    $idpEntityId = $issuer->getValue();
} else {
    $idpEntityId = $issuer;
}

if ($idpEntityId === null) {
    // Without an issuer we have no way to respond to the message.
    throw new Error\BadRequest('Received message on logout endpoint without issuer.');
}

$spEntityId = $source->getEntityId();

$metadata = Metadata\MetaDataStorageHandler::getMetadataHandler();
$idpMetadata = $source->getIdPMetadata($idpEntityId);
$spMetadata = $source->getMetadata();

Module\saml\Message::validateMessage($idpMetadata, $spMetadata, $message);

$httpUtils = new Utils\HTTP();
$destination = $message->getDestination();
if ($destination !== null && $destination !== $httpUtils->getSelfURLNoQuery()) {
    throw new Error\Exception('Destination in logout message is wrong.');
}

if ($message instanceof LogoutResponse) {
    $relayState = $message->getRelayState();
    if ($relayState === null) {
        // Somehow, our RelayState has been lost.
        throw new Error\BadRequest('Missing RelayState in logout response.');
    }

    if (!$message->isSuccess()) {
        Logger::warning(
            'Unsuccessful logout. Status was: ' . Module\saml\Message::getResponseError($message)
        );
    }

    $state = Auth\State::loadState($relayState, 'saml:slosent');
    $state['saml:sp:LogoutStatus'] = $message->getStatus();
    \SimpleSAML\Auth\Source::completeLogout($state);
} elseif ($message instanceof LogoutRequest) {
    Logger::debug('module/saml2/sp/logout: Request from ' . $idpEntityId);
    Logger::stats('saml20-idp-SLO idpinit ' . $spEntityId . ' ' . $idpEntityId);

    /** @psalm-var \SimpleSAML\SAML2\XML\saml\IdentifierInterface $nameId */
    $nameId = $message->getIdentifier();
    if ($nameId instanceof EncryptedID) {
        /** @psalm-var \SimpleSAML\SAML2\XML\EncryptedElementInterface $encId */
        $encId = $nameId;

        try {
            $keys = Module\saml\Message::getDecryptionKeys($idpMetadata, $spMetadata);
        } catch (Exception $e) {
            throw new Error\Exception('Error decrypting NameID: ' . $e->getMessage());
        }

        $blacklist = Module\saml\Message::getBlacklistedAlgorithms($idpMetadata, $spMetadata);

        $lastException = null;
        foreach ($keys as $i => $key) {
            try {
                $nameId = $encId->decrypt($key, $blacklist);
                Logger::debug('Decryption with key #' . $i . ' succeeded.');
                $lastException = null;
                break;
            } catch (Exception $e) {
                Logger::debug('Decryption with key #' . $i . ' failed with exception: ' . $e->getMessage());
                $lastException = $e;
            }
        }
        if ($lastException !== null) {
            throw $lastException;
        }
    }

    $sessionIndexes = $message->getSessionIndexes();

    /** @psalm-var \SimpleSAML\SAML2\XML\saml\IdentifierInterface $nameId */
    $numLoggedOut = Module\saml\SP\LogoutStore::logoutSessions($sourceId, $nameId, $sessionIndexes);
    if ($numLoggedOut === false) {
        // This type of logout was unsupported. Use the old method
        $source->handleLogout($idpEntityId);
        $numLoggedOut = count($sessionIndexes);
    }

    // Create and send response
    $lr = Module\saml\Message::buildLogoutResponse($spMetadata, $idpMetadata, $binding, $message->getId());
    $lr->setRelayState($message->getRelayState());

    if ($numLoggedOut < count($sessionIndexes)) {
        Logger::warning('Logged out of ' . $numLoggedOut . ' of ' . count($sessionIndexes) . ' sessions.');
    }

    $binding->send($lr);
} else {
    throw new Error\BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
}
