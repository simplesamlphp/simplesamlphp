# Upgrade notes for SimpleSAMLphp 2.3

SimpleSAMLphp 2.3 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

## Deprecations

The following classes were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Utils\Net

The following methods were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Utils\Crypto::pwHash - Use \Symfony\Component\PasswordHasher\NativePasswordHasher::hash instead
- SimpleSAML\Utils\Crypto::pwValid - Use \Symfony\Component\PasswordHasher\NativePasswordHasher::verify instead
- SimpleSAML\Utils\Crypto::secureCompare - Use hash_equals() instead
- SimpleSAML\Utils\Net::ipCIDRcheck - Use \Symfony\Component\HttpFoundation\IpUtils::checkIp instead

The following properties were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Locale\Language::$language_names - Use \Symfony\Component\Intl\Languages::getNames() instead
