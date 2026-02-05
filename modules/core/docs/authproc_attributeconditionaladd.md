# `core:AttributeConditionalAdd`

Filter that adds attributes to the user, optionally only doing so if certain attributes and values already exist the attribute list.

## Configuration Format

:   `class`
 declares that this block will configure an AttributeConditionalAdd authproc. It is required.

:   `%<flags>`
allows for optional (zero or more) flags to be specified. These start with a "%" character (eg `%replace`). See the **Flags** section below for more details.

:   `attributes`
defines a list of one or more attributes to add. It is required.

:   `conditions`
is optional. If it is specified, it contains a list of conditions that determine whether the `attributes` will be added or not. If the `conditions` section is not specified (or is an empty list), the `attributes` will be added. See the **Conditions** section below for more details.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            '%<flags>',
            'attributes' => [
                'attribute' => ['value'],
                [...],
            ],
            'conditions' => [],
        ],
    ],

## Conditions

Conditions are optional. If there are one or more conditions specified, the attributes will only be added if all of the conditions evaluate to true. By default, all `conditions` must evaluate to true, unless the `%anycondition` flag is specified, in which case the attributes will be added if any of the `conditions` are true.

### attrExistsAny

If the current attributes includes any of the listed attribute names, the new attributes in the `attributes` section will be added.

In the below example, if there is an attribute named **either** `customerId` OR `supplierId` (or both), then the `isExternalUser => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrExistsAny' => [
                    'customerId',
                    'supplierId',
                ],
            ],
            'attributes' => [
                'isExternalUser' => ['true'],
            ],
        ],
    ],

### attrExistsAll

If the current attributes includes all of the listed attribute names, the new attributes in the `attributes` section will be added.

In the below example, if there is an attribute named `customerId` AND there is an attribute named `companyName`, then the `isCompanyUser => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrExistsAll' => [
                    'customerId',
                    'companyName',
                ],
            ],
            'attributes' => [
                'isCompanyUser' => ['true'],
            ],
        ],
    ],

### attrExistsRegexAny

If the current attributes includes any attribute names that match any of the regular expressions listed, the new attributes in the `attributes` section will be added.

In the below example, if there is an attribute named **either** starting with `cust` OR ending with `PhoneNumber`, then the `isCustomer => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrExistsRegexAny' => [
                    '/^cust/',
                    '/PhoneNumber$/',
                ],
            ],
            'attributes' => [
                'isCustomer' => ['true'],
            ],
        ],
    ],

### attrExistsRegexAll

If each of the regular expressions listed matches at least one of the current attributes, the new attributes in the `attributes` section will be added.

In the below example, if there is an attribute name starting with `email` AND there is an attribute name starting with `member`, then the `isCustomer => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrExistsRegexAll' => [
                    '/^email/',
                    '/^member/',
                ],
            ],
            'attributes' => [
                'isCustomer' => ['true'],
            ],
        ],
    ],

### attrValueIsAny

If the current attributes includes any of the listed attribute names, and at least one of those has any of the listed values for that attribute, the new attributes in the `attributes` section will be added.

In the below example, if the user has the `departmentName` attribute set, and one of the values of that attribute is `Physics` or `Chemistry`, or they have a `managementRole` attribute set, and that attribute includes the value of `Vice Chancellor`, then the `newSystemPilotUser => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrValueIsAny' => [ // any of the attributes listed below has any of the values listed
                    'departmentName' => ['Physics', Chemistry],
                    'managementRole' => ['Vice Chancellor'],
                ],
            ],
            'attributes' => [
                'newSystemPilotUser' => ['true'],
            ],
        ],
    ],

### attrValueIsAll

If the current attributes includes all of the listed attribute names, and each of those attributes include all of the listed values for that attribute, the new attributes in the `attributes` section will be added.

Note: this does not mean the values listed are the only values present. They are a subset of the values present.

In the below example, only the Dean of the Physics department will have the `newSystemPilotUser => ['true']` attribute added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrValueIsAll' => [ // all of the attributes listed below has all of the values listed
                    'departmentName' => ['Physics'],
                    'managementRole' => ['Dean'],
                ],
            ],
            'attributes' => [
                'newSystemPilotUser' => ['true'],
            ],
        ],
    ],

### attrValueIsRegexAny

If the current attributes includes any of the listed attribute names, and has at least one existing value for those attributes that matches any of the listed regular expressions for that attribute, the new attributes in the `attributes` section will be added.

In the below example, if the user has the `qualifications` attribute set, and one of the values of that attributes starts with `Certified` or ends with `Assessor`, then the `qualifiedTradie => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrValueIsRegexAny' => [ // any of the attributes listed below has a value that matches any of the regex values listed
                    'qualifications' => ['/^Certfied/', '/Assessor$/'],
                ],
            ],
            'attributes' => [
                'qualifiedTradie' => ['true'],
            ],
        ],
    ],

### attrValueIsRegexAll

If the current attributes includes all of the listed attribute names, and all of the existing values for those attributes matches any of the listed regular expressions for that attribute, the new attributes in the `attributes` section will be added.

In the below example, if the user has the `email` attribute set, and all email addresses listed as values in that attribue end with either `/@staff.example.edu$/` OR `/@student.example.edu$/`, then the `'internalUser' => ['true']` attribute will be added.

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrValueIsRegexAll' => [ // all of the attributes listed below, every value matches one of the regex values listed
                    'email' => ['/@staff.example.edu$/', '/@student.example.edu$/'],
                ],
            ],
            'attributes' => [
                'internalUser' => ['true'],
            ],
        ],
    ],

## Flags

`%replace`
:   can be used to replace all existing values in target with new ones (any existing values will be lost)

`%nodupe`
:   removes all duplicate values from the `attributes` being added. Without this flag being specified, the default behaviour is that if a value already exists that we're appending to the attribute, a duplicate is added. Note that if there are pre-existing duplicate values in the attributes being added to, those values will also be de-duplicated.

`%anycondition`
:   if there are multiple `conditions`, any of those conditions evaluating to true will cause the `attributes` to be added. Without this flag being specified, the default behaviour is that all those conditions must be true for the values in the `attributes` section to be added.

## Examples

The most basic case - unconditionally add a SAML attribute named `source`:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'attributes' => [
                'source' => ['myidp'],
            ],
        ],
    ],

You can specify multiple attributes, and attributes can have multiple values:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'attributes' => [
                'eduPersonPrimaryAffiliation' => 'student',
                'eduPersonAffiliation' => ['student', 'employee', 'members'],
            ],
        ],
    ],

Append to an existing attribute if a condition is satisfied, removing duplicate values (if they already have the 'management' value in the 'groups' attribute, don't add it again):

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            '%nodupe',
            'conditions' => [
                'attrValueIsAny' => [
                    'role' => ['Manager', 'Director']
                ],
            ],
            'attributes' => [
                'groups' => ['management'],
            ],
        ],
    ],

Replace an existing attribute if a given attribute satisfies a condition:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            '%replace',
            'conditions' => [
                'attrValueIsAll' => [
                    'userType' => ['Customer'],
                    'onStopSupply' => ['true'],
                ],
            ],
            'attributes' => [
                'uid' => ['guest'],
            ],
        ],
    ],

Multiple conditions, where all must be true:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            'conditions' => [
                'attrExistsAny' => [
                    'staffId',
                ],
                'attrValueIsAny' => [
                    'departmentName' => ['Physics'],
                ],
            ],
            'attributes' => [
                'groups' => ['StaffPhysics'],
            ],
        ],
    ],

Multiple conditions, where any can be true. In the below case, the user must either have a `supplierId` attribute, or have the "staff" role and be in the "Procurement" department to receive the `'allowedSystems' => ['procurement']` attribute:

    'authproc' => [
        50 => [
            'class' => 'core:AttributeConditionalAdd',
            '%anycondition',
            'conditions' => [
                'attrExistsAny' => [
                    'supplierId',
                ],
                'attrValueIsAll' => [
                    'role' => ['Staff'],
                    'departmentName' => ['Procurement'],
                ],
            ],
            'attributes' => [
                'allowedSystems' => ['procurement'],
            ],
        ],
    ],
