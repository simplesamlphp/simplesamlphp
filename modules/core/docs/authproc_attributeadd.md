`core:AttributeAdd`
===================

Filter that adds attributes to the user.

If the attribute already exists, the values added will be merged into a multi-valued attribute.
If you instead want to replace the existing attribute, you may add the `%replace` option.

Examples
--------

Add a single-valued attributes:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            'source' => ['myidp'],
        ],
    ],

Add a multi-valued attribute:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            'groups' => ['users', 'members'],
        ],
    ],

Add multiple attributes:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            'eduPersonPrimaryAffiliation' => 'student',
            'eduPersonAffiliation' => ['student', 'employee', 'members'],
        ],
    ],

Replace an existing attributes:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            '%replace',
            'uid' => ['guest'],
        ],
    ],
