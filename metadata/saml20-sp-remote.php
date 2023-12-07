<?php

/**
 * SAML 2.0 remote SP metadata for SimpleSAMLphp.
 *
 * See: https://simplesamlphp.org/docs/stable/simplesamlphp-reference-sp-remote
 */

/*
 * Example SimpleSAMLphp SAML 2.0 SP
 */
global $DB;
$records = $DB->get_records_sql("SELECT * FROM ac_simplesamlphp_saml_sp_remote");
foreach ($records as $record) {
    try {
        $metadata[$record->shortname] = json_decode($record->data, true);
    } catch (\Exception $e) {
        // $e
    }
}
