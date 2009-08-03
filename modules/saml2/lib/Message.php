<?php


/**
 * Common code for building SAML 2 messages based on the
 * available metadata.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_saml2_Message {

	/**
	 * Retrieve the destination we should send the message to.
	 *
	 * This will return a debug endpoint if we have debug enabled. If debug
	 * is disabled, NULL is returned, in which case the default destination
	 * will be used.
	 *
	 * @return string|NULL  The destination the message should be delivered to.
	 */
	public static function getDebugDestination() {

		$globalConfig = SimpleSAML_Configuration::getInstance();
		if (!$globalConfig->getValue('debug')) {
			return NULL;
		}

		return SimpleSAML_Module::getModuleURL('saml2/debug.php');
	}


	/**
	 * Add signature key and and senders certificate to message.
	 *
	 * @param SAML2_Message $message  The message we should add the data to.
	 * @param SimpleSAML_Configuration $metadata  The metadata of the sender.
	 */
	private static function addSign(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata, SAML2_message $message) {

		$signingEnabled = $dstMetadata->getBoolean('redirect.sign', NULL);
		if ($signingEnabled === NULL) {
			$signingEnabled = $srcMetadata->getBoolean('redirect.sign', FALSE);
		}
		if (!$signingEnabled) {
			return;
		}


		$srcMetadata = $srcMetadata->toArray();

		$keyArray = SimpleSAML_Utilities::loadPrivateKey($srcMetadata, TRUE);
		$certArray = SimpleSAML_Utilities::loadPublicKey($srcMetadata, FALSE);

		$privateKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type' => 'private'));
		if (array_key_exists('password', $keyArray)) {
			$privateKey->passphrase = $keyArray['password'];
		}
		$privateKey->loadKey($keyArray['PEM'], FALSE);

		$message->setSignatureKey($privateKey);

		if ($certArray === NULL) {
			/* We don't have a certificate to add. */
			return;
		}

		if (!array_key_exists('PEM', $certArray)) {
			/* We have a public key with only a fingerprint. */
			return;
		}

		$message->setCertificates(array($certArray['PEM']));
	}


	/**
	 * Build an authentication request based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $spMetadata  The metadata of the service provider.
	 * @param SimpleSAML_Configuration $idpMetadata  The metadata of the identity provider.
	 */
	public static function buildAuthnRequest(SimpleSAML_Configuration $spMetadata, SimpleSAML_Configuration $idpMetadata) {

		$ar = new SAML2_AuthnRequest();

		$ar->setNameIdPolicy(array(
			'Format' => $spMetadata->getString('NameIDFormat', SAML2_Const::NAMEID_TRANSIENT),
			'AllowCreate' => TRUE,
			));

		$ar->setIssuer($spMetadata->getString('entityid'));
		$ar->setDestination($idpMetadata->getString('SingleSignOnService'));

		$ar->setForceAuthn($spMetadata->getBoolean('ForceAuthn', FALSE));
		$ar->setIsPassive($spMetadata->getBoolean('IsPassive', FALSE));

		self::addSign($spMetadata, $idpMetadata, $ar);

		return $ar;
	}


	/**
	 * Build a logout request based on information in the metadata.
	 *
	 * @param SimpleSAML_Configuration $srcMetadata  The metadata of the sender.
	 * @param SimpleSAML_Configuration $dstpMetadata  The metadata of the recipient.
	 */
	public static function buildLogoutRequest(SimpleSAML_Configuration $srcMetadata, SimpleSAML_Configuration $dstMetadata) {

		$lr = new SAML2_LogoutRequest();

		$lr->setIssuer($srcMetadata->getString('entityid'));
		$lr->setDestination($dstMetadata->getString('SingleLogoutService'));

		self::addSign($srcMetadata, $dstMetadata, $lr);

		return $lr;
	}

}

?>