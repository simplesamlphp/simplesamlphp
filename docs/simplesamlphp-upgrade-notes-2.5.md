# Upgrade notes for SimpleSAMLphp 2.5

SimpleSAMLphp 2.5 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

## Software requirements

- The minimum PHP version required is now PHP 8.3.
- Symfony was upgraded to 7.4 (LTS).

## General Upgrade Advice

When updating SimpleSAMLphp you might like to run the following to
remove any cached objects that might have older code signatures to the
new SimpleSAMLphp version. If you encounter a permissions error
running the clear-symfony-cache shown below please see the [the
SimpleSAMLphp installation instructions](simplesamlphp-install) for
information about how to update the filesystem permissions.

```sh
composer clear-symfony-cache
```

The above command is particularly useful if you see a message after
your update about "has required constructor arguments and does not
exist in the container" or "Did you forget to define the controller as
a service?" as these error messages might indicate a stale cache.
