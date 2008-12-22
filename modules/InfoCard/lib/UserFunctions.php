<?php
/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 22-DEC-08
* DESCRIPTION: edit this functions to fit your needs
*/ 

class sspmod_InfoCard_UserFunctions {

	/* Called by getinfocard.php and tokenservice.php
	* INPUT: user and password
	* OUTPUT: true if the data is correct or false in other case
	*/
	static public function validateUser($user,$pass){
		$status=false;
		if( (strcmp($user,'usuario')==0) && (strcmp($pass,'clave')==0) ){
			$status=true;
		}
		return $status;
	}
	
	
	
	/* Called by tokenservice.php
	* INPUT: username, configured required claims, configured optional claims and requested claims
	* OUTPUT: array of claims wiht value and display tag.
	*/
	static public function fillClaims($user, $configuredRequiredClaims, $configuredOptionalClaims, $requiredClaims){
		$claimValues = array();
		foreach ($requiredClaims as $claim){
			if (array_key_exists($claim,$configuredRequiredClaims) ){
				//The claim exists
				$claimValues[$claim]['value']="value-".$claim;
				$claimValues[$claim]['displayTag']=$configuredRequiredClaims[$claim]['displayTag'];
			}else if (array_key_exists($claim,$configuredOptionalClaims) ){
				//The claim exists
				$claimValues[$claim]['value']="value-".$claim;
				$claimValues[$claim]['displayTag']=$configuredOptionalClaims[$claim]['displayTag'];
			}else{
				//The claim DOES NOT exist
				$claimValues[$claim]['value']="unknown-value";
				$claimValues[$claim]['displayTag']=$claim;
			}
		}
		return $claimValues;
	}

	
	/* Called by getinfocard.php
	* INPUT: valid username
	* OUTPUT: array containing user data to create its InfoCard
	*/
	static public function fillICdata($user) {
		$ICdata = array();
		$ICdata['CardId'] = 'urn:sts.uah.es:'.$user;
		$ICdata['CardName'] = $user."-IC";
		$ICdata['CardImage'] = '/var/simplesaml/modules/InfoCard/www/resources/demoimage.png';
		$ICdata['TimeExpires'] = "9999-12-31T23:59:59Z";
		
		//Credentials
		$ICdata['DisplayCredentialHint'] = 'Enter your password';
		$ICdata['UserCredential'] = 'UsernamePasswordCredential'; //UsernamePasswordCredential, KerberosV5Credential, X509V3Credential, SelfIssuedCredential
		$ICdata['UserName'] = 'usuario'; //UsernamePasswordCredential
		$ICdata['KeyIdentifier'] = NULL; //X509V3Credential
		$ICdata['PPID'] = NULL; //SelfIssuedCredential
		
SimpleSAML_Logger::debug('ICDATA: '.$ICdata['CardImage']);
		return $ICdata;
	}


}
?>