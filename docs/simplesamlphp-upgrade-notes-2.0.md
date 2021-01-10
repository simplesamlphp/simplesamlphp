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
