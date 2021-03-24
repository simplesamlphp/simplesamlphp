`core:PairwiseID`
===================

Filter to insert a pairwise-id that complies with the following specification;
http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/saml-subject-id-attr-v1.0.pdf

This filter will take an attribute and a scope as input and transforms this into a anonymized and scoped
identifier that is globally unique for a given user & service provider combination.

Examples
--------

    'authproc' => [
        50 => [
            'class' => 'core:PairwiseID',
            'identifyingAttribute' => 'uid',
            'scope' => 'example.org',
        ],
    ],
