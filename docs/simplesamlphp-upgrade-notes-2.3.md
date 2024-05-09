# Upgrade notes for SimpleSAMLphp 2.3

SimpleSAMLphp 2.3 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

## Deprecations

The following properties were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Locale\Language::$language_names - Use \Symfony\Component\Intl\Languages::getNames() instead
