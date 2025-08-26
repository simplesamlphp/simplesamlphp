# SimpleSAMLphp Testing installation

[TOC]

## Testing a SimpleSAMLphp installation

The admin interface available as `/module.php/admin/` on your site
allows for testing logins to the authentication sources available on
your installation.

You can also see configuration including which modules are enabled and
what extensions are available in your PHP environment.

To see the admin page you should set a password in the adminpassword
configuration directive (the config.php.dist has details of this
process). Also please check that the admin module must be enabled.

The relevant parts of config.php:

```php
    'auth.adminpassword' => '123',
    ...
    'module.enable' => [
        'admin' => true,
        ...
```

## Testing various user profiles

The ProfileAuth authentication source can be used to test login as
various users by simply clicking on the user. Note that this testing
method allows for login without password so should be disabled in any
non testing environment.

The ProfileAuth functionality can be accessed through the path
`/module.php/admin/test` page using for example a `profileauth`
authentication source with the following `config.php` fragment.

The module must be enabled (and admin to get to it easily) and a small
configuration to show what users you wish to "click to authenticate"
as. If there is only a single item in the users array then you will
not have to click and will be authenticated as that user when you
visit the authentication source.

```php
    'module.enable' => [
        'admin' => true,
        'exampleauth' => true,
        ...
    ],

    'profileauth' => [
        'exampleauth:UserClick',
        'users' => [
            [
                'uid' => ['student'],
                'displayName' => ['Student'],
                'eduPersonAffiliation' => ['student', 'member'],
            ],
            [
                'uid' => ['employee'],
                'displayName' => ['Employee'],
                'eduPersonAffiliation' => ['employee', 'member'],
            ],
        ],
    ],
```

