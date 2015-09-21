<?php
/*
* AUTHOR: Samuel Muñoz Hidalgo
* EMAIL: samuel.mh@gmail.com
* LAST REVISION: 16-DEC-08
* DESCRIPTION: some useful functions.
*/

class sspmod_InfoCard_Utils {
	
	/*
	*INPUT:  a PEM-encoded certificate
	*OUTPUT: a PEM-encoded certificate without the BEGIN and END headers
	*/
	static public function takeCert($cert) {
		$begin = "CERTIFICATE-----";
		$end = "-----END";
		$pem = file_get_contents($cert);
		$pem = substr($pem, strpos($pem, $begin)+strlen($begin));
		$pem = substr($pem, 0, strpos($pem, $end));
		return str_replace("\n", "", $pem);
	}
	
	
	/*
	*INPUT:  a XML document
	*OUTPUT: a canonicalized XML document
	*/
	static public function canonicalize($XMLdoc){
		$dom = new DOMDocument();
		$dom->loadXML($XMLdoc);
		return ($dom->C14N(true, false));
	}
	
	
	static public function thumbcert($cert){
		return base64_encode(sha1(base64_decode($cert), true));
	}
	

	/*
	*INPUT:  a x509 certificate
	*OUTPUT: Common Name or a self issued value if no input is given
	*EXTRA: The output is used as issuer
	*/
	static public function getIssuer($cert){
		if ($cert==NULL){
			return 'http://schemas.xmlsoap.org/ws/2005/05/identity/issuer/self';
		}else{
			$resource = file_get_contents($cert);
			$check_cert = openssl_x509_read($resource);
			$array = openssl_x509_parse($check_cert);
			openssl_x509_free($check_cert);
			$schema = $array['name'];
			$pattern='/.*CN=/';
			$replacement='';
			$CN=preg_replace($pattern,$replacement,$schema);
			return $CN;
		}
	}


	

	
	/*
	* INPUT: claims schema (string) and a DOMNodelist with the requested claims in uri style
	* OUTPUT: array of requested claims
	* 
	*/
	static public function extractClaims($ICschema, $nodeList){
		//Returns the Uri attribute from an attribute list
		function getUri($attrList){
			$uri = null;
			$end=false;	
			$i=0;
			do{
				if ($i > $attrList->length){
					$end = true;
				} else if (strcmp($attrList->item($i)->name,'Uri')==0){
					$end = true;
					$uri = $attrList->item($i)->value;
				} else {
					$i++;
				}
			} while (!$end);
			return $uri;
		}
	$requiredClaims = array();
	$schema = $ICschema."/claims/";
	SimpleSAML_Logger::debug("schema:   ".$schema);
	$pattern='/\//';
	$replacement='\/';
	$schema= '/'.preg_replace($pattern,$replacement,$schema).'/';
	for ($i=0;$i<($nodeList->length);$i++) {
		$replacement='';
		$uri = getUri($nodeList->item($i)->attributes);
		$claim = preg_replace($schema,$replacement,$uri);
		$requiredClaims[$i]=$claim;
		SimpleSAML_Logger::debug("uri:   ".$uri);
		SimpleSAML_Logger::debug("claim: ".$claim);
	}
	return $requiredClaims;
}


}
?>