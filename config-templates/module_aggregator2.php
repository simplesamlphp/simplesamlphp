<?php

/* This is the configuration file for the aggregator2-module. */
$config = array(

	/*
	 * 'example' will be one set of aggregated metadata.
	 * The aggregated metadata can be retrieved from:
	 *   https://.../simplesaml/module.php/aggregator2/get.php?id=example
	 */
	'example' => array(

		/* 'sources' is an array with the places we want to fetch metadata from. */
		'sources' => array(
			/* Metadata validated by the https-certificate of the server. */
			array(
				/* The URL we should fetch the metadata from. */
				'url' => 'https://sp.example.org/metadata.xml',

				/*
				 * To enable validation of the https-certificate, we must
				 * specify a file with valid CA certificates.
				 *
				 * This can be an absolute path, or a path relative to the
				 * cert-directory.
				 */
				'ssl.cafile' => '/etc/ssl/certs/ca-certificates.crt',
			),

			/* Metadata validated by its signature. */
			array(
				/* The URL we should fetch the metadata from. */
				'url' => 'http://idp.example.org/metadata.xml',

				/*
				 * To verify the signature in the metadata, we must specify
				 * a certificate that should be used. Note: This cannot
				 * be a CA certificate.
				 *
				 * This can be an absolute path, or a path relative to the
				 * cert-directory.
				 */
				'cert' => 'idp.example.org.crt',
			),

			/* Metadata from a file. */
			array(
				'url' => '/var/simplesaml/somemetadata.xml',
			),

		),

		/*
		 * Update this metadata during this cron tag.
		 *
		 * For this option to work, you must configure the cron-module,
		 * and also add a cache directory.
		 *
		 * This option is optional. If cron is not configured, the metadata
		 * caches will be updated when receiving requests for metadata.
		 */
		'cron.tag' => 'hourly',

		/*
		 * The directory we will store downloaded and generated metadata.
		 * This directory must be writeable by the web-server.
		 *
		 * This option is optional, but if unspecified, every request for the
		 * aggregated metadata will result in the aggregator fetching and
		 * parsing all metadata sources.
		 */
		'cache.directory' => '/var/cache/simplesaml-aggregator2',

		/*
		 * This is the number of seconds we will cache the metadata file we generate.
		 * This should be a longer time than the interval between each time the cron
		 * job is executed.
		 *
		 * This option is optional. If unspecified, the metadata will be generated
		 * on every request.
		 */
		'cache.generated' => 24*60*60,

		/*
		 * The generated metadata will have a validUntil set to the time it is generated
		 * plus this number of seconds.
		 */
		'valid.length' => 7*24*60*60,

		/*
		 * The private key we should use to sign the metadata, in pem-format.
		 *
		 * This is optional. If it is not specified, the metadata will not be signed.
		 */
		'sign.privatekey' => 'metadata.pem',

		/*
		 * The password for the private key.
		 *
		 * Optional, the private key is assumed to be unencrypted if this option
		 * isn't set.
		 */
		'sign.privatekey_pass' => 'secret',

		/*
		 * The certificate that corresponds to the private key.
		 *
		 * If specified, the certificate will be included in the signature in the metadata.
		 */
		'sign.certificate' => 'metadata.crt',
	),

);
