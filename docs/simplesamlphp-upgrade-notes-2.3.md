# Upgrade notes for SimpleSAMLphp 2.3

SimpleSAMLphp 2.3 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

- Session ID's are now hashed when stored in a database. This means all old sessions are effectively
  invalidated by this upgrade. We recommend clearing your session store as part of the upgrade-routine.

## Deprecations

The following classes were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Utils\Net

The following methods were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Utils\Crypto::aesDecrypt - use the xml-security library instead - See commit `52ef3a78d1faf22e040efd5d0fd1f234da2458eb` for an example.
- SimpleSAML\Utils\Crypto::aesEncrypt - use the xml-security library instead - See commit `52ef3a78d1faf22e040efd5d0fd1f234da2458eb` for an example.
- SimpleSAML\Utils\Crypto::pwHash - Use \Symfony\Component\PasswordHasher\NativePasswordHasher::hash instead
- SimpleSAML\Utils\Crypto::pwValid - Use \Symfony\Component\PasswordHasher\NativePasswordHasher::verify instead
- SimpleSAML\Utils\Crypto::secureCompare - Use hash_equals() instead
- SimpleSAML\Utils\Net::ipCIDRcheck - Use \Symfony\Component\HttpFoundation\IpUtils::checkIp instead

The following properties were marked `deprecated` and will be removed in a next major release.

- SimpleSAML\Locale\Language::$language_names - Use \Symfony\Component\Intl\Languages::getNames() instead

## BC break

- Plain-text admin-passwords are no longer allowed.
  Please use the `bin/pwgen.php` script to generate a secure password hash.

- The language codes `pt-br` and `zh-tw` have been renamed to `pt_BR` and `zh_TW`.
  Please update your configuration to match the new names.

- Endpoints in metadata (e.g. "SingleSignOnLocation" and "AssertionCosumerService") can no longer be simple strings and are now only accepted in array-style. The old string-style was deprecated for 9 yrs
  already and was broken anyway. See [endpoints] for the current format.

[endpoints]: https://simplesamlphp.org/docs/stable/simplesamlphp-metadata-endpoints.html
