`core:AttributeAdd`
===================

Filter that adds attributes to the user.

If the attribute already exists, the values added will be merged into a multi-valued attribute.
If you instead want to replace the existing attribute, you may add the `%replace` option.

If you want to only add the attribute if another attribute (or attributes) already exist, you can
specify the optional `%if_attr_exists` (for plain strings) or `%if_attr_regex_matches`
(for regular expressions). Both can be specified as either a single value or an array of values.

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

Add a single-valued attribute if at least one of two existing attributes exist:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            '%if_attr_exists' => ['studentId', 'staffId'],
            'internalUser' => ['true'],
        ],
    ],

Add a single-valued attribute if a regular expression matches an existing attribute. In this case,
if there is an existing attribute where the attribute name starts with "graduateOf", then add a
new "hasGraduated" attribute:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeAdd',
            '%if_attr_regex_matches' => '/^graduateOf/',
            'hasGraduated' => ['true'],
        ],
    ],
