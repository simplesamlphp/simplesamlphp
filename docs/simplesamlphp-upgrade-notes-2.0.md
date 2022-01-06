Upgrade notes for SimpleSAMLphp 2.0
====================================

SimpleSAMLphp 2.0 is a major new release which has cleaned up support for a
lot of things that have been marked deprecated in previous SimpleSAMLphp
releases. The following changes are relevant for installers and/or developers.

Software requirements
---------------------
- The minimum PHP version required is now PHP 7.4.
- Dropped support for Symfony 4 and Twig 2.

Not all modules included by default
-----------------------------------
The set of modules included in the base installation has been reduced.
If you used some of the modules that were shipped with SimpleSAMLphp, you now have to manually install them using Composer.
For example, to use the LDAP module:

  composer require simplesamlphp/simplesamlphp-module-ldap --update-no-dev

Functional changes
------------------
- Modules must be enabled through the `module.enable` option in `config.php`. Modules can no longer be enabled by having
  a file named `enable` or `default-enable` in the module's root directory.
- SAML AuthnRequests that are signed will have their signature validated unless specifically disabled
  by setting `validate.authnrequest` to `false`. If unset (or set to true) signatures will be
  validated if present and requests not passing validation will be refused.
- In the  core:TargetedID authproc-filter, the `attributename` setting has been renamed to `identifyingAttribute`.
- The default encryption algorithm is set from `AES128_CBC` to `AES128_GCM`.
  It is possible to switch back via the `sharedkey_algorithm`. Note however that CBC is vulnerable to the Padding oracle attack.
- All support for the Shibboleth 1.3 / SAML 1.1 protocol has been removed.

Configuration changes
---------------------
Quite some options have been changed or removed. We recommend to start with a fresh
template from `config-templates/` and migrate the settings you require to the new
config file= manualy.

Configuration options that have been removed:
 - languages[priorities]
 - attributes.extradictionaries. Add an attributes.po to your configured theme instead.
 - admin.protectindexpage. Replaced by the admin module which always requires login.

Changes relevant for (module) developers
----------------------------------------
The following changes are relevant for those having custom developed modules, authentication
processing filters or interface with the SimpleSAMLphp development API.

- Old JSON-formatted dictionaries have been replaced by gettext / .po-files;
    You can find a migration guide here: https://github.com/simplesamlphp/simplesamlphp/wiki/Migrating-translations-(pre-migration)
- Old PHP templates have been replaced by Twig-templates; you can find a migration
    guide here: https://github.com/simplesamlphp/simplesamlphp/wiki/Twig:-Migrating-templates
- The source was completely typehinted; if you have custom authsources or authproc filters, 
    make sure you change them to reflect the method signatures of the base classes.
- Some hooks are no longer called:
  - `frontpage`: replace with `configpage`
  - `htmlinject`: use a Twig template override instead.
  - `metadata_hosted`: no replacement
- The following classes have been migrated to non-static:
  + \SimpleSAML\Utils\Arrays
  + \SimpleSAML\Utils\Attributes
  + \SimpleSAML\Utils\Auth
  + \SimpleSAML\Utils\Config
  + \SimpleSAML\Utils\Crypto
  + \SimpleSAML\Utils\EMail
  + \SimpleSAML\Utils\HTTP
  + \SimpleSAML\Utils\Net
  + \SimpleSAML\Utils\Random
  + \SimpleSAML\Utils\System
  + \SimpleSAML\Utils\Time
  + \SimpleSAML\Utils\XML

  If you use any of these classes in your modules or themes, you will now have to instantiate them so that:

  // Old style
  $x = \SimpleSAML\Utils\Arrays::arrayize($someVar)

  becomes:

  // New style
  $arrayUtils = new \SimpleSAML\Utils\Arrays();
  $x = $arrayUtils->arrayize($someVar);

- Database table schemes have been flattened. Upgrade paths are:
  - Generic KVStore:  1.16+ > 2.0
  - Logout store:     1.18+ > 2.0

- Data stores have been refactored:
  - lib/SimpleSAML/Store.php has been renamed to lib/SimpleSAML/Store/StoreFactory.php and is now solely a Factory-class
  - All store implementations now implement \SimpleSAML\Store\StoreInterface:
    - lib/SimpleSAML/Store/SQL.php has been renamed to lib/SimpleSAML/Store/SQLStore.php
    - lib/SimpleSAML/Store/Memcache.php has been renamed to lib/SimpleSAML/Store/MemcacheStore.php
    - lib/SimpleSAML/Store/Redis.php has been renamed to lib/SimpleSAML/Store/RedisStore.php

