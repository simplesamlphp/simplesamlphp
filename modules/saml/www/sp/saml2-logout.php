<?php

/**
 * Logout endpoint handler for SAML SP authentication client.
 *
 * This endpoint handles both logout requests and logout responses.
 */

use Exception;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\LogoutResponse;
use SAML2\LogoutRequest;
use SAML2\SOAP;
use SAML2\XML\saml\Issuer;
use SimpleSAML\Auth;
use SimpleSAML\Error;
use SimpleSAML\Logger;
use SimpleSAML\Metadata;
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
} catch (Exception $e) {
    // TODO: look for a specific exception
    // This is dirty. Instead of checking the message of the exception, \SAML2\Binding::getCurrentBinding() should throw
    // an specific exception when the binding is unknown, and we should capture that here
    if ($e->getMessage() === 'Unable to find the current binding.') {
        throw new Error\Error('SLOSERVICEPARAMS', $e, 400);
    } else {
        throw $e; // do not ignore other exceptions!
    }
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

$destination = $message->getDestination();
if ($destination !== null && $destination !== Utils\HTTP::getSelfURLNoQuery()) {
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

    if ($message->isNameIdEncrypted()) {
        try {
            $keys = Module\saml\Message::getDecryptionKeys($idpMetadata, $spMetadata);
        } catch (Exception $e) {
            throw new Error\Exception('Error decrypting NameID: ' . $e->getMessage());
        }

        $blacklist = Module\saml\Message::getBlacklistedAlgorithms($idpMetadata, $spMetadata);

        $lastException = null;
        foreach ($keys as $i => $key) {
            try {
                $message->decryptNameId($key, $blacklist);
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

    $nameId = $message->getNameId();
    $sessionIndexes = $message->getSessionIndexes();

    /** @psalm-suppress PossiblyNullArgument  This will be fixed in saml2 5.0 */
    $numLoggedOut = Module\saml\SP\LogoutStore::logoutSessions($sourceId, $nameId, $sessionIndexes);
    if ($numLoggedOut === false) {
        // This type of logout was unsupported. Use the old method
        $source->handleLogout($idpEntityId);
        $numLoggedOut = count($sessionIndexes);
    }

    // Create and send response
    $lr = Module\saml\Message::buildLogoutResponse($spMetadata, $idpMetadata);
    $lr->setRelayState($message->getRelayState());
    $lr->setInResponseTo($message->getId());

    if ($numLoggedOut < count($sessionIndexes)) {
        Logger::warning('Logged out of ' . $numLoggedOut . ' of ' . count($sessionIndexes) . ' sessions.');
    }

    $dst = $idpMetadata->getEndpointPrioritizedByBinding(
        'SingleLogoutService',
        [
            Constants::BINDING_HTTP_REDIRECT,
            Constants::BINDING_HTTP_POST
        ]
    );

    if (!($binding instanceof SOAP)) {
        $binding = Binding::getBinding($dst['Binding']);
        if (isset($dst['ResponseLocation'])) {
            $dst = $dst['ResponseLocation'];
        } else {
            $dst = $dst['Location'];
        }
        $binding->setDestination($dst);
    } else {
        $lr->setDestination($dst['Location']);
    }

    $binding->send($lr);
} else {
    throw new Error\BadRequest('Unknown message received on logout endpoint: ' . get_class($message));
}
