<?php


/**
 * Interface to a SAML 2 element which may be signed.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
interface SAML2_SignedElement {

	/**
	 * Validate this element against a public key.
	 *
	 * If no signature is present, FALSE is returned. If a signature is present,
	 * but cannot be verified, an exception will be thrown.
	 *
	 * @param XMLSecurityKey $key  The key we should check against.
	 * @return boolean  TRUE if successful, FALSE if we don't have a signature that can be verified.
	 */
	public function validate(XMLSecurityKey $key);


	/**
	 * Retrieve the certificates that are included in the element (if any).
	 *
	 * @return array  An array of certificates.
	 */
	public function getCertificates();

}