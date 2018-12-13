Upgrade notes for SimpleSAMLphp 1.17
====================================

The minimum PHP version required is now PHP 5.5.

All (remaining) classes have been changed to use namespaces. There are mappings
from the legacy names so calling code should keep working. Custom code
(e.g. modules) that test for class names explicitly, e.g. when catching specific
exceptions, may need to be changed.

The possibility to omit sending a NameIDPolicy in authentication requests has
been reintroduced by setting `NameIDPolicy` to `false`. The preferred way is
to configure it as an array `[ 'Format' => format, 'AllowCreate' => true/false ]`,
which is now also the format used in the `saml:NameIDPolicy` variable
in the state array.

Code, config and documentation have switched to using the modern PHP
array syntax. This should not have an impact as both will remain working
equally, but the code examples and config templates look slightly different.
The following are equivalent:

    // Old style array syntax
    $config = array(
        'authproc' => array(
            60 => 'class:etc'
        ),
        'other example' => 1
    );

    // Current style array syntax
    $config = [
        'authproc' => [
            60 => 'class:etc'
        ],
        'other example' => 1
    ];

Finally, a new experimental user interface has been introduced. The interface
is split in two:

* A user interface targeted at end users, which allows them to authenticate and
  see their information, as well as manage any options relevant to them.
* An admin interface with most of the pages in the current web interface. This
  new interface is implemented in an admin module that can be disabled, effectively
  removing the administrator interface completely.

In order to test this new user interface, a temporary configuration option
`usenewui` needs to be set to `true`. This configuration option will disappear
in SimpleSAMLphp 2.0, where the new user interface will be the only one available.