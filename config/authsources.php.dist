<?php

$config = [
    /*
     * When multiple authentication sources are defined, you can specify one to use by default
     * in order to authenticate users. In order to do that, you just need to name it "default"
     * here. That authentication source will be used by default then when a user reaches the
     * SimpleSAMLphp installation from the web browser, without passing through the API.
     *
     * If you already have named your auth source with a different name, you don't need to change
     * it in order to use it as a default. Just create an alias by the end of this file:
     *
     * $config['default'] = &$config['your_auth_source'];
     */

    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ],


    // An authentication source which can authenticate against SAML 2.0 IdPs.
    'default-sp' => [
        'saml:SP',

        // The entity ID of this SP.
        'entityID' => 'https://myapp.example.org/',

        // The entity ID of the IdP this SP should contact.
        // Can be NULL/unset, in which case the user will be shown a list of available IdPs.
        'idp' => null,

        // The URL to the discovery service.
        // Can be NULL/unset, in which case a builtin discovery service will be used.
        'discoURL' => null,

        /*
         * If SP behind the SimpleSAMLphp in IdP/SP proxy mode requests
         * AuthnContextClassRef, decide whether the AuthnContextClassRef will be
         * processed by the IdP/SP proxy or if it will be passed to the original
         * IdP in front of the IdP/SP proxy.
         */
        'proxymode.passAuthnContextClassRef' => false,

        /*
         * The attributes parameter must contain an array of desired attributes by the SP.
         * The attributes can be expressed as an array of names or as an associative array
         * in the form of 'friendlyName' => 'name'. This feature requires 'name' to be set.
         * The metadata will then be created as follows:
         * <md:RequestedAttribute FriendlyName="friendlyName" Name="name" />
         */
        /*
        'name' => [
            'en' => 'A service',
            'no' => 'En tjeneste',
        ],

        'attributes' => [
            'attrname' => 'urn:oid:x.x.x.x',
        ],
        'attributes.required' => [
            'urn:oid:x.x.x.x',
        ],
        */
    ],


    /*
    'example-sql' => [
        'sqlauth:SQL',
        'dsn' => 'pgsql:host=sql.example.org;port=5432;dbname=simplesaml',
        'username' => 'simplesaml',
        'password' => 'secretpassword',
        'query' => 'SELECT uid, givenName, email, eduPersonPrincipalName FROM users WHERE uid = :username ' .
            'AND password = SHA2(CONCAT((SELECT salt FROM users WHERE uid = :username), :password), 256);',
    ],
    */

    /*
    'example-static' => [
        'exampleauth:StaticSource',
        'uid' => ['testuser'],
        'eduPersonAffiliation' => ['member', 'employee'],
        'cn' => ['Test User'],
    ],
    */

    /*
    'example-userpass' => [
        'exampleauth:UserPass',

        // Give the user an option to save their username for future login attempts
        // And when enabled, what should the default be, to save the username or not
        //'remember.username.enabled' => false,
        //'remember.username.checked' => false,

        'users' => [
            'student:studentpass' => [
                'uid' => ['test'],
                'eduPersonAffiliation' => ['member', 'student'],
            ],
            'employee:employeepass' => [
                'uid' => ['employee'],
                'eduPersonAffiliation' => ['member', 'employee'],
            ],
        ],
    ],
    */

    /*
    'crypto-hash' => [
        'authcrypt:Hash',
        // hashed version of 'verysecret', made with bin/pwgen.php
        'professor:{SSHA256}P6FDTEEIY2EnER9a6P2GwHhI5JDrwBgjQ913oVQjBngmCtrNBUMowA==' => [
            'uid' => ['prof_a'],
            'eduPersonAffiliation' => ['member', 'employee', 'board'],
        ],
    ],
    */

    /*
    'htpasswd' => [
        'authcrypt:Htpasswd',
        'htpasswd_file' => '/var/www/foo.edu/legacy_app/.htpasswd',
        'static_attributes' => [
            'eduPersonAffiliation' => ['member', 'employee'],
            'Organization' => ['University of Foo'],
        ],
    ],
    */

    /*
    // This authentication source serves as an example of integration with an
    // external authentication engine. Take a look at the comment in the beginning
    // of modules/exampleauth/lib/Auth/Source/External.php for a description of
    // how to adjust it to your own site.
    'example-external' => [
        'exampleauth:External',
    ],
    */

    /*
    'yubikey' => [
        'authYubiKey:YubiKey',
         'id' => '000',
        // 'key' => '012345678',
    ],
    */

    /*
    'facebook' => [
        'authfacebook:Facebook',
        // Register your Facebook application on http://www.facebook.com/developers
        // App ID or API key (requests with App ID should be faster; https://github.com/facebook/php-sdk/issues/214)
        'api_key' => 'xxxxxxxxxxxxxxxx',
        // App Secret
        'secret' => 'xxxxxxxxxxxxxxxx',
        // which additional data permissions to request from user
        // see http://developers.facebook.com/docs/authentication/permissions/ for the full list
        // 'req_perms' => 'email,user_birthday',
        // Which additional user profile fields to request.
        // When empty, only the app-specific user id and name will be returned
        // See https://developers.facebook.com/docs/graph-api/reference/v2.6/user for the full list
        // 'user_fields' => 'email,birthday,third_party_id,name,first_name,last_name',
    ],
    */

    /*
    // Twitter OAuth Authentication API.
    // Register your application to get an API key here:
    //  http://twitter.com/oauth_clients
    'twitter' => [
        'authtwitter:Twitter',
        'key' => 'xxxxxxxxxxxxxxxx',
        'secret' => 'xxxxxxxxxxxxxxxx',
        // Forces the user to enter their credentials to ensure the correct users account is authorized.
        // Details: https://dev.twitter.com/docs/api/1/get/oauth/authenticate
        'force_login' => false,
    ],
    */

    /*
    // Microsoft Account (Windows Live ID) Authentication API.
    // Register your application to get an API key here:
    //  https://apps.dev.microsoft.com/
    'windowslive' => [
        'authwindowslive:LiveID',
        'key' => 'xxxxxxxxxxxxxxxx',
        'secret' => 'xxxxxxxxxxxxxxxx',
    ],
    */

    /*
    // Example of a LDAP authentication source.
    'example-ldap' => [
        'ldap:Ldap',

        // The connection string for the LDAP-server.
        // You can add multiple by separating them with a space.
        'connection_string' => 'ldap.example.org',

        // Whether SSL/TLS should be used when contacting the LDAP server.
        // Possible values are 'ssl', 'tls' or 'none'
        'encryption' => 'ssl',

        // The LDAP version to use when interfacing the LDAP-server.
        // Defaults to 3
        'version' => 3,

        // Set to TRUE to enable LDAP debug level. Passed to the LDAP connector class.
        //
        // Default: FALSE
        // Required: No
        'ldap.debug' => false,

        // The LDAP-options to pass when setting up a connection
        // See [Symfony documentation][1]
        'options' => [

            // Set whether to follow referrals.
            // AD Controllers may require 0x00 to function.
            // Possible values are 0x00 (NEVER), 0x01 (SEARCHING),
            //   0x02 (FINDING) or 0x03 (ALWAYS).
            'referrals' => 0x00,

            'network_timeout' => 3,
        ],

        // The connector to use.
        // Defaults to '\SimpleSAML\Module\ldap\Connector\Ldap', but can be set
        // to '\SimpleSAML\Module\ldap\Connector\ActiveDirectory' when
        // authenticating against Microsoft Active Directory. This will
        // provide you with more specific error messages.
        'connector' => '\SimpleSAML\Module\ldap\Connector\Ldap',

        // Which attributes should be retrieved from the LDAP server.
        // This can be an array of attribute names, or NULL, in which case
        // all attributes are fetched.
        'attributes' => null,

         // Which attributes should be base64 encoded after retrieval from
         // the LDAP server.
        'attributes.binary' => [
            'jpegPhoto',
            'objectGUID',
            'objectSid',
            'mS-DS-ConsistencyGuid'
        ],

        // The pattern which should be used to create the user's DN given
        // the username. %username% in this pattern will be replaced with
        // the user's username.
        //
        // This option is not used if the search.enable option is set to TRUE.
        'dnpattern' => 'uid=%username%,ou=people,dc=example,dc=org',

        // As an alternative to specifying a pattern for the users DN, it is
        // possible to search for the username in a set of attributes. This is
        // enabled by this option.
        'search.enable' => false,

        // An array on DNs which will be used as a base for the search. In
        // case of multiple strings, they will be searched in the order given.
        'search.base' => [
            'ou=people,dc=example,dc=org',
        ],

        // The scope of the search. Valid values are 'sub' and 'one' and
        // 'base', first one being the default if no value is set.
        'search.scope' => 'sub',

        // The attribute(s) the username should match against.
        //
        // This is an array with one or more attribute names. Any of the
        // attributes in the array may match the value the username.
        'search.attributes' => ['uid', 'mail'],

        // Additional filters that must match for the entire LDAP search to
        // be true.
        //
        // This should be a single string conforming to [RFC 1960][2]
        // and [RFC 2544][3]. The string is appended to the search attributes
        'search.filter' => '(&(objectClass=Person)(|(sn=Doe)(cn=John *)))',

        // The username & password where SimpleSAMLphp should bind to before
        // searching. If this is left NULL, no bind will be performed before
        // searching.
        'search.username' => null,
        'search.password' => null,
    ],
    */

    /*
    // Example of an LDAPMulti authentication source.
    'example-ldapmulti' => [
        'ldap:LdapMulti',

         // The way the organization as part of the username should be handled.
         // Three possible values:
         // - 'none':   No handling of the organization. Allows '@' to be part
         //             of the username.
         // - 'allow':  Will allow users to type 'username@organization'.
         // - 'force':  Force users to type 'username@organization'. The dropdown
         //             list will be hidden.
         //
         // The default is 'none'.
        'username_organization_method' => 'none',

        // Whether the organization should be included as part of the username
        // when authenticating. If this is set to TRUE, the username will be on
        // the form <username>@<organization identifier>. If this is FALSE, the
        // username will be used as the user enters it.
        //
        // The default is FALSE.
        'include_organization_in_username' => false,

        // A list of available LDAP servers.
        //
        // The index is an identifier for the organization/group. When
        // 'username_organization_method' is set to something other than 'none',
        // the organization-part of the username is matched against the index.
        //
        // The value of each element is an array in the same format as an LDAP
        // authentication source.
        'mapping' => [
            'employees' => [
                // A short name/description for this group. Will be shown in a
                // dropdown list when the user logs on.
                //
                // This option can be a string or an array with
                // language => text mappings.
                'description' => 'Employees',
                'authsource' => 'example-ldap',
            ],

            'students' => [
                'description' => 'Students',
                'authsource' => 'example-ldap-2',
            ],
        ],
    ],
    */
];
