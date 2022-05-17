<?php

/**
 * SAML 2.0 IdP configuration for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-hosted
 */

$metadata['acorn-default-hosted'] = [
    'host' => '__DEFAULT__',
    'privatekey' => 'server.key',
    'certificate' => 'server.pem',
    'auth' => 'acorn-default'
];

global $DB;
$records = $DB->get_records_sql("SELECT * FROM ac_simplesamlphp_saml_idp_hosted");
foreach ($records as $record) {
    try {
        $metadata[$record->shortname] = json_decode($record->data, true);
    } catch (\Exception $e) {
        // $e
    }
}
