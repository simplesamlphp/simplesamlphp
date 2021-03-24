`core:SubjectID`
===================

Filter to insert a subject-id that complies with the following specification;
http://docs.oasis-open.org/security/saml-subject-id-attr/v1.0/saml-subject-id-attr-v1.0.pdf

This filter will take an attribute and a scope as input and transforms this into a scoped identifier that is globally unique for a given user.

Note:
-----
If privacy is of your concern, you may want to use the PairwiseID-filter instead.

Examples
--------

    'authproc' => [
        50 => [
            'class' => 'core:SubjectID',
            'identifyingAttribute' => 'uid',
            'scope' => 'example.org',
        ],
    ],
