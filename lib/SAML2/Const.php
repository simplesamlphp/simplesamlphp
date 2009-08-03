<?php

/**
 * Various SAML 2 constants.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SAML2_Const {

	/**
	 * Top-level status code indicating successful processing of the request.
	 */
	const STATUS_SUCCESS = 'urn:oasis:names:tc:SAML:2.0:status:Success';

	/**
	 * Top-level status code indicating that there was a problem with the request.
	 */
	const STATUS_REQUESTER = 'urn:oasis:names:tc:SAML:2.0:status:Requester';

	/**
	 * Top-level status code indicating that there was a problem generating the response.
	 */
	const STATUS_RESPONDER = 'urn:oasis:names:tc:SAML:2.0:status:Responder';

	/**
	 * Top-level status code indicating that the request was from an unsupported version of the SAML protocol.
	 */
	const STATUS_VERSION_MISMATCH = 'urn:oasis:names:tc:SAML:2.0:status:VersionMismatch';


	/**
	 * Second-level status code for NoPassive errors.
	 */
	const STATUS_NO_PASSIVE = 'urn:oasis:names:tc:SAML:2.0:status:NoPassive';

}

?>