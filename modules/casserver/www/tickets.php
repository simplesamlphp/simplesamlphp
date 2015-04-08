<?php

function storeTicket($ticket, $value ) {
        $store = SimpleSAML_Store::getInstance();
        if ($store === FALSE) {
            throw new Exception('Unable to store ticket without a datastore configured.');
        }

        $store->set('casticket', $ticket, serialize($value), time() + 15*60);
}

function retrieveTicket($ticket, $unlink = true) {

	if (!preg_match('/^(ST|PT|PGT)-?[a-zA-Z0-9]+$/D', $ticket)) throw new Exception('Invalid characters in ticket');

        $store = SimpleSAML_Store::getInstance();
        if ($store === FALSE) {
            throw new Exception('Unable to retrieve ticket without a datastore configured.');
        }

        $content = $store->get('casticket', $ticket);

	if (!$content) {
		throw new Exception('Could not find ticket');
	}

	if ($unlink) {
		$store->delete('casticket', $ticket);
	}

	return unserialize($content);
}

function checkServiceURL($service, array $legal_service_urls) {
	foreach ($legal_service_urls AS $legalurl) {
		if (strpos($service, $legalurl) === 0) return TRUE;
	}
	return FALSE;
}
