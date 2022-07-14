# Upgrade notes for SimpleSAMLphp 1.18

The minimum PHP version required is now PHP 5.6.

## Deprecations

* The use of the PHP `memcache` extension was deprecated in favour of `memcached`.
In order to keep using memcache functionality you have to move to the PHP `memcached` extension,
which is available from PECL; see [memcached][1]. The former is considered abandoned
and it's safe use can no longer be guaranteed.

  There are a few options here:

  * Depending on your distribution, the package may just be available for you to install
  * You could use the package from the [REMI repository][2] if you're on RHEL.
  * Download the source from [memcached][1] and [compile][3] the source as a PHP-extension manually.

* Support for SAML1.1 / Shibboleth 1.3 will be discontinued in a future release.
* The class `SimpleSAML\Auth\TimeLimitedToken` is now deprecated and will be removed in a future release
  If your custom module relies on this class, be sure to make a copy into your repository and
  make sure to also copy the unit tests that come along.
* Setting `privacypolicy` in metadata files will be removed in a future release. It was only used
  by the consent module, which supports `UIInfo`'s `PrivacyStatementURL`.
  See [Metadata extensions][4] on how to configure this.

[1]: https://pecl.php.net/package/memcached
[2]: https://rpms.remirepo.net
[3]: https://www.php.net/manual/en/install.pecl.phpize.php
[4]: https://simplesamlphp.org/docs/stable/simplesamlphp-metadata-extensions-ui
