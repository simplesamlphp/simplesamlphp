<?php

global $DB, $CFG;

$sendEmail = $DB->record_exists_sql("SELECT * FROM ac_simplesamlphp_config WHERE name = 'sendEmail' AND value IS NOT NULL");
$sendUsername = $DB->record_exists_sql("SELECT * FROM ac_simplesamlphp_config WHERE name = 'sendUsername' AND value IS NOT NULL");
$emailDomain = $DB->record_exists_sql("SELECT * FROM ac_simplesamlphp_config WHERE name = 'emailDomain' AND value IS NOT NULL");

$config = [
    'admin' => [
        'core:AdminPassword',
    ],
    'acorn-default' => [
        'acorn:AcornAuth',
        'acornDirectory' => $CFG->dirroot,
        'opts' => [
            'sendEmail' => $sendEmail,
            'sendUsername' => $sendUsername,
            'emailDomain' => (!empty($emailDomain) ? $emailDomain->value : null)
        ]
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
