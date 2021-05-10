`core:PairwiseID`
===================

Filter to insert a pairwise-id that complies with the following specification;
http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/saml-subject-id-attr-v1.0.pdf

This filter will take an attribute and a scope as input and transforms this into a anonymized and scoped
identifier that is globally unique for a given user & service provider combination.

Note:
Since the subject-id is specified as single-value attribute, only the first value of `identifyingAttribute`
 and `scopeAttribute` are considered.

Examples
--------

    'authproc' => [
        50 => [
            'class' => 'core:PairwiseID',
            'identifyingAttribute' => 'uid',
            'scopeAttribute' => 'scope',
        ],
    ],
