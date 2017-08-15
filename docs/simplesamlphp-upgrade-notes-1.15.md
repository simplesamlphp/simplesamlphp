Upgrade notes for SimpleSAMLphp 1.15
====================================

A new templating system based on Twig has been introduced. The old templating
system is still available but should be considered deprecated.

The integrated _Auth Memcookie_ support is now deprecated and will no longer
be available starting in SimpleSAMLphp 2.0. Please use the new
[memcookie module](https://github.com/simplesamlphp/simplesamlphp-module-memcookie)
instead.

The option to specify a SAML certificate by its fingerprint, `certFingerprint`
has been deprecated and will be removed in a future release. Please use the
full certificate in `certData` instead.

The `core:AttributeRealm` authproc filter has been deprecated.
Please use `core:ScopeFromAttribute`, which is a generalised version of this.
