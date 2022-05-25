# Authentication source selector

The Authentication source selector is a special kind of Authentication Source
that delegates the actual authentication to a secondary Authentication Source
based on some form of policy decision.

## AbstractSourceSelector

The AbstractSourceSelector extends from `\SimpleSAML\Auth\Source` and as such
act as an Authentication Source. Any derivative classes must implement the
abstract `selectAuthSource` method. This method must return the name of the
Authentication Source to use, based on whatever logic is necessary.

## IPSourceSelector

The IPSourceSelector is an implementation of the `AbstractSourceSelector` and
uses the client IP to decide what Authentication Source is called.
It works by defining zones with corresponding IP-ranges and Authentication
Sources. The 'default' zone is required and acts as a fallback when none
of the zones match a client's IP-address.

An example configuration would look like this:

```php
    'selector' => [
        'core:IPSourceSelector',

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

## YourCustomSourceSelector

If you have a use-case for a custom Authentication source selector, all you
have to do is to create your own class, make it extend `AbstractSourceSelector`
and make it implement the abstract `selectAuthSource` method containing
your own logic. The method should return the name of the Authentication
source to use.
