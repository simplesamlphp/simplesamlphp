Upgrade notes for SimpleSAMLphp 1.18
====================================

The minimum PHP version required is now PHP 5.6.

The use of the PHP Memcache-extension was deprecated in favour of the Memcached-extension.
In order to keep using Memcache-functionality you have to move to the PHP Memchached-extension,
  which is available from PECL; see https://pecl.php.net/package/memcached

  There are a few options here:
   - Depending on your distribution, the package may just be available to install
   - You could use the package from the REMI-repository if you're on RHEL; https://rpms.remirepo.net/
   - Download the source from https://pecl.php.net/package/memcached and compile the source as a PHP-extension manually;
     https://www.php.net/manual/en/install.pecl.phpize.php
