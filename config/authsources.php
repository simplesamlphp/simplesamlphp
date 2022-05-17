<?php
global $DB, $CFG;

$config = [
    'admin' => [
        'core:AdminPassword',
    ],
    'acorn-default' => [
        'acorn:AcornAuth',
        'acornDirectory' => $CFG->dirroot
    ]
];

$records = $DB->get_records_sql("SELECT * FROM ac_simplesamlphp_authsources");
foreach ($records as $record) {
    try {
        $config[$record->shortname] = json_decode($record->data, true);
    } catch (\Exception $e) {
        // $e
    }
}
