# Upgrade notes for SimpleSAMLphp 2.5

SimpleSAMLphp 2.5 is a minor new release which introduces a few new features.
The following changes are relevant for installers and/or developers.

## Software requirements

- The minimum PHP version required is now PHP 8.3.
- Symfony was upgraded to 7.4 (LTS).

## Web-proxy

- This release replaces several cases of `file_get_contents()` and direct use of
  `curl_`-functions with the Symfony HTTP-client. If you have a proxy set in `config.php`,
  please ensure that is has `http://` or `https://` as a scheme, appropriate to
  your use-case. The old `tcp://` scheme may no longer work correctly for all use-cases.

  To be even more future-proof, set the proxy-configuration to `null` and use environment-
  variables instead. See: https://symfony.com/doc/current/http_client.html#http-proxies

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
