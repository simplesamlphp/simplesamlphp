<?php

$attributemap = [
    /**
     * Renamed Attributes to match other 2name mappings
     */
    "http://schemas.microsoft.com/identity/claims/objectidentifier" => 'uid',
    "http://schemas.microsoft.com/identity/claims/displayname" => 'displayName',
    "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname" => 'givenName',
    "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname" => 'sn',
    "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress" => 'emailAddress',
    "http://schemas.xmlsoap.org/ws/2005/05/identity/claims/name" => 'mail',
    
    /**
     * Additional/Optional Claim, using default value
     */
    "http://schemas.microsoft.com/ws/2008/06/identity/claims/groups" => 'groups',

    /**
     * Additional Attributes from Entra
     */
    "http://schemas.microsoft.com/claims/authnmethodsreferences" => 'authNMethodsReferences',
    "http://schemas.microsoft.com/identity/claims/identityprovider" => 'idp',
    "http://schemas.microsoft.com/identity/claims/tenantid" => 'tenantId',
];