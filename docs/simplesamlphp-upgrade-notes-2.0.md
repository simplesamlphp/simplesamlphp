Upgrade notes for SimpleSAMLphp 2.0
====================================

- The minimum PHP version required is now PHP 7.4.
- Old JSON-formatted dictionaries have been replaced by gettext / .po-files;
    You can find a migration guide here: https://github.com/simplesamlphp/simplesamlphp/wiki/Migrating-translations-(pre-migration)
- Old PHP templates have been replaced by Twig-templates; you can find a migration
    guide here: https://github.com/simplesamlphp/simplesamlphp/wiki/Twig:-Migrating-templates
- The source was completely typehinted; if you have custom authsources or authproc filters, 
    make sure you change them to reflect the method signatures of the base classes.
- If you used some of the modules that were shipped with SimpleSAMLphp, you now have to manually install them using Composer;
    For example, to use the ldap-module: bin/composer.phar require simplesamlphp/simplesamlphp-module-ldap --update-no-dev
- If you're using the core:TargetedID authproc-filter, note that the `attributename` setting has been renamed to `identifyingAttribute`.
- The default encryption algorithm is set from AES128_CBC to AES128_GCM. If you're upgrading from an existing implementation, you may want
    to manually switch back the `sharedkey_algorithm`. Note that CBC is vulnerable to the Padding oracle attack.
- In compliancy with SAML2INT, AuthnRequests that are signed will have their signature validated unless specifically disabled by setting `validate.authnrequest` to `false`.  If unset, or set to true, signatures will be validated and requests not passing validation will be refused.
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

- Configuration options removed:
  - languages[priorities]
