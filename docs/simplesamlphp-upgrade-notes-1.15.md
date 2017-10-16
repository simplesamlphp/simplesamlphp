Upgrade notes for SimpleSAMLphp 1.15
====================================

The minimum required PHP version is now 5.4. The dependency on mcrypt has been
dropped.

A new templating system based on Twig has been introduced. The old templating
system is still available but should be considered deprecated.

A new internationalization system based on Gettext has been introduced. While
old templates can use either the old or the new system (refer to the
"language.i18n.backend" configuration option for more information on how to
choose the internationalization backend), new Twig templates can only use the
new Gettext internationalization system.

The integrated _Auth Memcookie_ support is now deprecated and will no longer
be available starting in SimpleSAMLphp 2.0. Please use the new
[memcookie module](https://github.com/simplesamlphp/simplesamlphp-module-memcookie)
instead.

The option to specify a SAML certificate by its fingerprint, `certFingerprint`
has been deprecated and will be removed in a future release. Please use the
full certificate in `certData` instead.

The `core:AttributeRealm` authproc filter has been deprecated.
Please use `core:ScopeFromAttribute`, which is a generalised version of this.

The following modules are no longer shipped with the SimpleSAMLphp:

* `aggregator`
* `aggregator2`
* `aselect`
* `autotest`
* `consentSimpleAdmin`
* `discojuice`
* `InfoCard`
* `logpeek`
* `metaedit`
* `modinfo`
* `papi`
* `openid`
* `openidProvider`
* `saml2debug`
* `themefeidernd`
