`core:AttributeCopy`
====================

Filter that copies attributes.

Examples
--------

Copy a single attribute (user's `uid` will be copied to the user's `username`):

    'authproc' => [
        50 => [
            'class' => 'core:AttributeCopy',
            'uid' => 'username',
        ],
    ],

Copy a single attribute to more than one attribute (user's `uid` will be copied to the user's `username` and to `urn:mace:dir:attribute-def:uid`)

    'authproc' => [
        50 => [
            'class' => 'core:AttributeCopy',
            'uid' => ['username', 'urn:mace:dir:attribute-def:uid'],
        ],
    ],
