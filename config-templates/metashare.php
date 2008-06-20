<?php

/*
 * Configuration for the MetaShare service.
 */
$config = array(

	/*
	 * Whether the MetaShare service is enabled. Set to TRUE to enable the MetaShare service.
	 */
	'metashare.enable' => FALSE,

	/*
	 * The path we will store the metadata in. Set this to a directory which is writeable by
	 * the web server. We will attempt to create this directory if it doesn't exists.
	 *
	 * If the path name is relative, it will be interpreted to be relative to the simpleSAMLphp
	 * directory.
	 */
	'metashare.path' => '/tmp/metashare',

	/*
	 * Whether we should validate the metadata we receive against the schema before allowing them
	 * to be added.
	 */
	'metashare.validateschema' => TRUE,

	/*
	 * The MetaShare service can optionally sign the list of metadata it generates. Set this to
	 * TRUE to enable that.
	 */
	'metashare.signmetadatalist' => FALSE,

	/*
	 * When signing metadata, you need to provide a private key and a certificate. You can also
	 * specify a password for the private key. All paths are relative to the simpleSAMLphp cert
	 * directory.
	 */
	'metashare.privatekey' => NULL,
	'metashare.privatekey_pass' => NULL,
	'metashare.certificate' => NULL,

);

?>