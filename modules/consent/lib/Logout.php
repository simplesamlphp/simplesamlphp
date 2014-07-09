<?php

/**
 * Class defining the logout completed handler for the consent page.
 *
 * @package simpleSAMLphp
 */
class sspmod_consent_Logout {

	public static function postLogout(SimpleSAML_IdP $idp, array $state) {
		$url = SimpleSAML_Module::getModuleURL('consent/logout_completed.php');
		SimpleSAML_Utilities::redirectTrustedURL($url);
	}

}
