Attribute Aggregator module
==============

The Attribute Aggregator module is implemented as an Authentication Processing Filter. 
It can be configured in the SP's config.php file.

It is recommended to run the Attribute Aggregator module at the SP and configure the
filter to run after the federated id, usually eduPersonPrincipalName is resolved.

  * [Read more about processing filters in simpleSAMLphp](simplesamlphp-authproc)


How to setup the attributeaggregator module
-------------------------------

The only required option of the module is the `entityId` of the Attribute Authority to 
be queried. The AA must support `urn:oasis:names:tc:SAML:2.0:bindings:SOAP` binding.


To enable the module, create an `enable` file in the
module directory:

    touch modules/attributeaggregator/enable

Example:

                59 => array(
                   'class' => 'attributeaggregator:attributeaggregator',
                   'entityId' => 'https://aa.example.com:8443/aa',

                  /**
                   * The subject of the attribute query. Default: urn:oid:1.3.6.1.4.1.5923.1.1.1.6 (eduPersonPrincipalName)
                   */
                   //'attributeId' => 'urn:oid:1.3.6.1.4.1.5923.1.1.1.6',

                   /** 
                    * If set to TRUE, the module will throw an exception if attributeId is not found.
                    */
                   // 'required' => FALSE,

                   /** 
                    * The format of attributeId. Default is 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent'
                    */
                   //'nameIdFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',


                   /**
                    * The name Format of the attribute names.
                    */
                   //'attributeNameFormat' => 'urn:oasis:names:tc:SAML:2.0:attrname-format:uri',

                   /**
                    * The requested attributes. If not present, we will get all
                    * the attributes. The keys of the array is the attribute name in (''urn:oid'') format.
                    * values:
                    *   the array of acceptable values. If not defined, the filter will accept all values.
                    * multiSource:
                    *   merge:    merge the existing and the new values, this is the default behaviour,
                    *   override: drop the existing values and set the values from AA,
                    *   keep:     drop the new values from AA and keep the original values.
                    */
                   // 'attributes' => array(
                   //         "urn:oid:attribute-OID-1" => array (
                   //               "values" => array ("value1", "value2"),
                   //               "multiSource" => "override"
                   //               ),
                   //         "urn:oid:attribute-OID-2" => array (
                   //               "multiSource" => "keep"
                   //               ),
                   //         "urn:oid:attribute-OID-3" => array (
                   //               "values" => array ("value1", "value2"),
                   //               ),
                   //         "urn:oid:attribute-OID-4" => array ()
                   //        ),

                ),


Options
-------

The following options can be used when configuring the '''attributeaggregation''' module

`entityId`
:   The entityId of the Attribute Authority. The metadata of the AA must be in the
    attributeauthority-remote metadata set, otherwise you will get an error message.

`attributeId`
:   This is the *Subject* in the issued AttributeQuery. The attribute must be previously 
resolved by an authproc module. The default attribute is urn:oid:1.3.6.1.4.1.5923.1.1.1.6 
(eduPersonPrincipalName).

`attributeNameFormat`
:   The format of the NameID in the issued AttributeQuery. The default value is 
`urn:oasis:names:tc:SAML:2.0:attrname-format:uri`.

`attributes`
:   You can list the expected attributes from the Attrubute Authority in the *attributes* 
array. The array contains key-value pairs, where the keys are attribute names in full 
federated (''urn:oid'') format and the values are arrays with the expected values for 
that attribute. If the value is an empty array, all the values of the attributes are 
resolved, otherwise only the matching ones. If the `attributes` option is not defined, 
every attribute is resolved from the response from the AA.
