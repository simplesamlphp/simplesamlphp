`core:AttributeDump`
===================

Filter that outputs to the system log file attributes and their values that match a given criteria.

This is particularly useful for adding debug points in your list of authproc filters as you are configuring your SimpleSAMLphp.

Parameters
----------

`class` (required)
:   This is the name of the filter.
    It must be `core:AttributeDump`.

`attributes`
:   An array of attribute names that are to be output to the SimpleSAMLphp logs.
    If not specified, and `attributesRegex` is not specified, all attributes will be output.

`attributesRegex`
:   An array of regular expressions. Any attribute name that matches any of the regular expressions
    in this list are to be output to the SimpleSAMLphp logs.
    If not specified, and `attributes` is also not specified, all attributes will be output.

`prefix`
:   A string to prefix each log line to be outputted.
    Defaults to "AttributeDump".

`logLevel`
:   The level to log at. For the message to appear in the SimpleSAMLphp log files it needs to be at a level
    equal to or higher than the log value you've configured in your `config.php`.
    Valid values are: "emergency", "critical", "alert", "error", "warning", "notice", "info" or "debug".

Examples
--------

If no attribute list or list of attribute regular expressions is provided, it will simply dump all attributes:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeDump',
        ],
    ],

This will output the `uid` and `groups` attributes only to the logs:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeDump',
            'attributes' => ['uid', 'groups'],
        ],
    ],

This will output any attribute that ends in the letter `n` (eg. `fn`, `sn`, `cn`):

    'authproc' => [
        50 => [
            'class' => 'core:AttributeDump',
            'attributesRegex' => ['/n$/'],
        ],
    ],

This will output the `uid` and `groups` attributes, as well as any attribute that ends in the letter `n` (eg. `fn`, `sn`, `cn`) to the logs:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeDump',
            'attributes' => ['uid', 'groups'],
            'attributesRegex' => ['/n$/'],
        ],
    ],

Optionally, you can specify a prefix to the log message and a log level to log at:

    'authproc' => [
        49 => [
            'class' => 'core:AttributeAdd',
            [...]
        ],

        50 => [
            'class' => 'core:AttributeDump',
            'prefix' => 'After running AttributeAdd but before applying AttributeLimit filter',
            'logLevel' => 'debug',
        ],

        51 => [
            'class' => 'core:AttributeLimit',
            [...]
        ],
    ],
