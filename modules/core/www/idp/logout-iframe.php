<?php

if (!isset($_REQUEST['id'])) {
    throw new SimpleSAML_Error_BadRequest('Missing required parameter: id');
}

if (isset($_REQUEST['type'])) {
    $type = (string) $_REQUEST['type'];
    if (!in_array($type, array('init', 'js', 'nojs', 'embed'), true)) {
        throw new SimpleSAML_Error_BadRequest('Invalid value for type.');
    }
} else {
    $type = 'init';
}

if ($type !== 'embed') {
    SimpleSAML\Logger::stats('slo-iframe '.$type);
    SimpleSAML_Stats::log('core:idp:logout-iframe:page', array('type' => $type));
}

$state = SimpleSAML_Auth_State::loadState($_REQUEST['id'], 'core:Logout-IFrame');
$idp = SimpleSAML_IdP::getByState($state);
$mdh = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler();

if ($type !== 'init') { // update association state
    foreach ($state['core:Logout-IFrame:Associations'] as $assocId => &$sp) {
        $spId = sha1($assocId);

        // move SPs from 'onhold' to 'inprogress'
        if ($sp['core:Logout-IFrame:State'] === 'onhold') {
            $sp['core:Logout-IFrame:State'] = 'inprogress';
        }

        // check for update through request
        if (isset($_REQUEST[$spId])) {
            $s = $_REQUEST[$spId];
            if ($s == 'completed' || $s == 'failed') {
                $sp['core:Logout-IFrame:State'] = $s;
            }
        }

        // check for timeout
        if (isset($sp['core:Logout-IFrame:Timeout']) && $sp['core:Logout-IFrame:Timeout'] < time()) {
            if ($sp['core:Logout-IFrame:State'] === 'inprogress') {
                $sp['core:Logout-IFrame:State'] = 'failed';
            }
        }

        // update the IdP
        if ($sp['core:Logout-IFrame:State'] === 'completed') {
            $idp->terminateAssociation($assocId);
        }

        if (!isset($sp['core:Logout-IFrame:Timeout'])) {
            if (method_exists($sp['Handler'], 'getAssociationConfig')) {
                $assocIdP = SimpleSAML_IdP::getByState($sp);
                $assocConfig = call_user_func(array($sp['Handler'], 'getAssociationConfig'), $assocIdP, $sp);
                $sp['core:Logout-IFrame:Timeout'] = $assocConfig->getInteger('core:logout-timeout', 5) + time();
            } else {
                $sp['core:Logout-IFrame:Timeout'] = time() + 5;
            }
        }
    }
}

$associations = $idp->getAssociations();
foreach ($state['core:Logout-IFrame:Associations'] as $assocId => &$sp) {
    // in case we are refreshing a page
    if (!isset($associations[$assocId])) {
        $sp['core:Logout-IFrame:State'] = 'completed';
    }

    try {
        $assocIdP = SimpleSAML_IdP::getByState($sp);
        $url = call_user_func(array($sp['Handler'], 'getLogoutURL'), $assocIdP, $sp, null);
        $sp['core:Logout-IFrame:URL'] = $url;
    } catch (Exception $e) {
        $sp['core:Logout-IFrame:State'] = 'failed';
    }
}

// get the metadata of the service that initiated logout, if any
$terminated = null;
if ($state['core:TerminatedAssocId'] !== null) {
    $mdset = 'saml20-sp-remote';
    if (substr($state['core:TerminatedAssocId'], 0, 4) === 'adfs') {
        $mdset = 'adfs-sp-remote';
    }
    $terminated = $mdh->getMetaDataConfig($state['saml:SPEntityId'], $mdset)->toArray();
}

// build an array with information about all services currently logged in
$remaining = array();
foreach ($state['core:Logout-IFrame:Associations'] as $association) {
    $key = sha1($association['id']);
    $mdset = 'saml20-sp-remote';
    if (substr($association['id'], 0, 4) === 'adfs') {
        $mdset = 'adfs-sp-remote';
    }

    $remaining[$key] = array(
        'id' => $association['id'],
        'expires_on' => $association['Expires'],
        'entityID' => $association['saml:entityID'],
        'subject' => $association['saml:NameID'],
        'status' => $association['core:Logout-IFrame:State'],
        'logoutURL' => $association['core:Logout-IFrame:URL'],
        'metadata' => $mdh->getMetaDataConfig($association['saml:entityID'], $mdset)->toArray(),
    );
    if (isset($association['core:Logout-IFrame:Timeout'])) {
        $remaining[$key]['timeout'] = $association['core:Logout-IFrame:Timeout'];
    }
}

$id = SimpleSAML_Auth_State::saveState($state, 'core:Logout-IFrame');
$globalConfig = SimpleSAML_Configuration::getInstance();

$template_id = 'core:logout-iframe.php';
if ($type === 'nojs') {
    $template_id = 'core:logout-iframe-wrapper.php';
}

$t = new SimpleSAML_XHTML_Template($globalConfig, $template_id);
$t->data['auth_state'] = $id;
/**
 * @deprecated The "id" variable will be removed. Please use "auth_state" instead.
 */
$t->data['id'] = $id;
$t->data['type'] = $type;
$t->data['terminated_service'] = $terminated;
$t->data['remaining_services'] = $remaining;

/** @deprecated The "from" array will be removed in 2.0, use the "terminated_service" array instead */
$t->data['from'] = $state['core:Logout-IFrame:From'];

/** @deprecated The "SPs" array will be removed, use the "remaining_services" array instead */
$t->data['SPs'] = $state['core:Logout-IFrame:Associations'];

if ($type !== 'nojs') {
    /** @deprecated The "jquery" array will be removed in 2.0 */
    $t->data['jquery'] = array('core' => true, 'ui' => false, 'css' => false);
}

$t->show();
