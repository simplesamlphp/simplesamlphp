`saml:SubjectID`
===================

Filter to insert a subject-id that complies with the
[SAML V2.0 Subject Identifier Attributes Profile][specification].

[specification]: http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/saml-subject-id-attr-v1.0.pdf

This filter will take an attribute and a scope as input and transforms this
into a scoped identifier that is globally unique for a given user.

**Note**
If privacy is of your concern, you may want to hash the unique part of the subject-id. Hashing also ensures
that the output is compliant with the specification. If you do not want to hash the unique part, you _have_
to ensure that the `identifyingAttribute` always contains a value that is in line with the specification!

If you are also worried about correlation of IDs between diffent SP's, use the PairwiseID-filter instead.

**Note**
Since the subject-id is specified as single-value attribute, only the first
value of `identifyingAttribute` and `scopeAttribute` are considered.

Examples
--------

```php
    'authproc' => [
        50 => [
            'class' => 'saml:SubjectID',
            'identifyingAttribute' => 'uid',
            'scopeAttribute' => 'scope',
            'hashed' => true,
        ],
    ],
```
