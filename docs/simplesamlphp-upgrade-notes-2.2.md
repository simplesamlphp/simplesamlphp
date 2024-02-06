# Upgrade notes for SimpleSAMLphp 2.2

SimpleSAMLphp 2.2 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

## Software requirements

- The minimum PHP version required is now PHP 8.1.
- Symfony was upgraded to 6.4 (LTS).

## Deprecations

The following methods were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Error\ErrorCodes::defaultGetAllErrorCodeTitles - Use getDefaultTitles instead
- SimpleSAML\Error\Errorcodes::getCustomErrorCodeTitles - Use getCustomTitles instead
- SimpleSAML\Error\Errorcodes::getAllErrorCodeTitles - Use getAllTitles instead
- SimpleSAML\Error\Errorcodes::defaultGetAllErrorCodeDescriptions - Use getDefaultDescriptions instead
- SimpleSAML\Error\Errorcodes::getCustomErrorCodeDescriptions - Use getCustomErrorCodeDescriptions instead
- SimpleSAML\Error\Errorcodes::getAllErrorCodeDescriptions - Use getAllDescriptions instead
- SimpleSAML\Error\Errorcodes::getAllErrorCodeMessages - Use getAllMessages instead
- SimpleSAML\Error\Errorcodes::getErrorCodeTitle - Use getTitle instead
- SimpleSAML\Error\Errorcodes::getErrorCodeDescription - Use getDescription instead
- SimpleSAML\Error\Errorcodes::getErrorCodeMessage - Use getMessage instead

The `tempdir` configuration setting was marked `deprecated`. Use `cachedir` instead.

The core:StatisticsWithAttribute authproc-filter was removed from SimpleSAMLphp.
It is now available in the statistics-module from v2.1 and up.
