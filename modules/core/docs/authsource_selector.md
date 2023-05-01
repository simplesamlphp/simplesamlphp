# Authentication source selector

The Authentication source selector is a special kind of Authentication Source
that delegates the actual authentication to a secondary Authentication Source
based on some form of policy decision.

## AbstractSourceSelector

The AbstractSourceSelector extends from `\SimpleSAML\Auth\Source` and as such
act as an Authentication Source. Any derivative classes must implement the
abstract `selectAuthSource` method. This method must return the name of the
Authentication Source to use, based on whatever logic is necessary.

## SourceIPSelector

The SourceIPSelector is an implementation of the `AbstractSourceSelector` that
uses the client IP to decide what Authentication Source is called.
It works by defining zones with corresponding IP-ranges and Authentication
Sources. The 'default' zone is optional and acts as a fallback when none
of the zones match a client's IP-address. When set to `null` a NotFound-
exception will be thrown.

An example configuration would look like this:

```php
    'selector' => [
        'core:SourceIPSelector',

        'zones' => [
            'internal' => [
                'source' => 'ldap',
                'subnet' => [
                    '10.0.0.0/8',
                    '2001:0DB8::/108',
                ],
            ],

            'other' => [
                'source' => 'radius',
                'subnet' => [
                    '172.16.0.0/12',
                    '2002:1234::/108',
                ],
            ],

            'default' => 'yubikey',
        ],
    ],
```

## RequestedAuthnContextSelector

The RequestedAuthnContextSelector is an implementation of the `AbstractSourceSelector` that
uses the RequestedAuthnContext to decide what Authentication Source is called.
It works by defining AuthnContexts with their corresponding Authentication
Sources. The 'default' key will be used as a default when no RequestedAuthnContext
is passed in the request.

An example configuration would look like this:

```php
    'selector' => [
        'core:RequestedAuthnContextSelector',

        'contexts' => [
            10 => [
                'identifier' => 'urn:x-simplesamlphp:loa1',
                'source' => 'ldap',
            ],

            20 => [
                'identifier' => 'urn:x-simplesamlphp:loa2',
                'source' => 'radius',
            ],

            'default' => [
                'identifier' => 'urn:x-simplesamlphp:loa0',
                'source' => 'sql',
            ],
        ],
    ],
```

## YourCustomSourceSelector

If you have a use-case for a custom Authentication source selector, all you
have to do is to create your own class, make it extend `AbstractSourceSelector`
and make it implement the abstract `selectAuthSource` method containing
your own logic. The method should return the name of the Authentication
source to use.
