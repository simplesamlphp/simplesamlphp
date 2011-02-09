<?php

/**
 * Exception which will show a page telling the user
 * that we don't know what to do.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
class SimpleSAML_Error_NoState extends SimpleSAML_Error_Error {


	/**
	 * Create the error
	 */
	public function __construct() {
		parent::__construct('NOSTATE');
	}


	/**
	 * Show the error to the user.
	 *
	 * This function does not return.
	 */
	public function show() {

		header('HTTP/1.0 500 Internal Server Error');

		$errorData = $this->saveError();

		$session = SimpleSAML_Session::getInstance();
		$attributes = $session->getAttributes();
		if (isset($attributes['mail'][0])) {
			$email = $attributes['mail'][0];
		} else {
			$email = '';
		}


		$globalConfig = SimpleSAML_Configuration::getInstance();
		$t = new SimpleSAML_XHTML_Template($globalConfig, 'core:no_state.tpl.php');

		/* Enable error reporting if we have a valid technical contact email. */
		if($globalConfig->getString('technicalcontact_email', 'na@example.org') !== 'na@example.org') {
			/* Enable error reporting. */
			$baseurl = SimpleSAML_Utilities::getBaseURL();
			$t->data['errorReportAddress'] = $baseurl . 'errorreport.php';
			$t->data['reportId'] = $errorData['reportId'];
			$t->data['email'] = $email;
		}

		$t->show();
		exit();
	}

}
