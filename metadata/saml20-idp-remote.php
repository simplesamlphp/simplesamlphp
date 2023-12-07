<?php

/**
 * SAML 2.0 remote IdP metadata for SimpleSAMLphp.
 *
 * Remember to remove the IdPs you don't use from this file.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-idp-remote
 */

global $DB;
$records = $DB->get_records_sql("SELECT * FROM ac_simplesamlphp_saml_idp_remote");
foreach ($records as $record) {
    try {
        $metadata[$record->shortname] = json_decode($record->data, true);
    } catch (\Exception $e) {
        // $e
    }
}
