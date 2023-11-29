# Upgrade notes for SimpleSAMLphp 2.1

SimpleSAMLphp 2.1 is a minor new release which bumps some
of the minimum requirements to be able to run SimpleSAMLphp.
The following changes are relevant for installers and/or developers.

## Software requirements

- The minimum PHP version required is now PHP 8.0.
- Dropped support for Symfony 5.4.

## Two builds

As of SimpleSAMLphp 2.1 two builds are created for every release.
The 'slim' build is a lightweight build without any modules other than the core modules installed (alike the 2.0 build).
A new 'full' build is added that will come with the most used modules pre-installed.

## Default security-headers

The default security headers have been adjusted to a more strict set. This may cause issues if you use any modules
or custom themes that use inline CSS or JavaScript. You can adjust these settings in `config.php` using the
`headers.security` setting, but we recommend updating the custom module/theme and move and inline CSS or JavaScript
to a file. All modules within the `simplesamlphp/*` namespace are fixed and should not cause any issues.
