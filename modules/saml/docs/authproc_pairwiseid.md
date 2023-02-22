`saml:PairwiseID`
===================

Filter to insert a pairwise-id that complies with the
[SAML V2.0 Subject Identifier Attributes Profile][specification].

[specification]: http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/saml-subject-id-attr-v1.0.pdf

This filter will take an attribute and a scope as input and transforms this
into a anonymized and scoped identifier that is globally unique for a given
user & service provider combination.

Note:
Since the subject-id is specified as single-value attribute, only the first
value of `identifyingAttribute` and `scopeAttribute` are considered.

Examples
--------

```php
    'authproc' => [
        50 => [
            'class' => 'saml:PairwiseID',
            'identifyingAttribute' => 'uid',
            'scopeAttribute' => 'scope',
        ],
    ],
```
