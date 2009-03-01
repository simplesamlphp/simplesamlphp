<?php


$config = SimpleSAML_Configuration::getInstance();
$sconfig = SimpleSAML_Configuration::getConfig('config-sanitycheck.php');

$info = array();
$errors = array();
$hookinfo = array(
	'info' => &$info, 
	'errors' => &$errors,
);
SimpleSAML_Module::callHooks('sanitycheck', $hookinfo);


if (isset($_REQUEST['output']) && $_REQUEST['output'] == 'text') {
	
	if (count($errors) === 0) {
		echo 'OK';
	} else {
		echo 'FAIL';
	}
	exit;
}

$htmlContentPre = array(); $htmlContentPost = array(); $htmlContentHead = array(); $jquery = array();
$hookinfo = array('pre' => &$htmlContentPre, 'post' => &$htmlContentPost, 'head' => &$htmlContentHead, 'jquery' => &$jquery, 'page' => 'santitycheck');
SimpleSAML_Module::callHooks('htmlinject', $hookinfo);


$t = new SimpleSAML_XHTML_Template($config, 'sanitycheck:check-tpl.php');
$t->data['errors'] = $errors;
$t->data['info'] = $info;
$t->data['htmlContentPre'] = $htmlContentPre;
$t->data['htmlContentPost'] = $htmlContentPost;
$t->data['htmlContentHead'] = $htmlContentHead;
$t->data['jquery'] = $jquery;
$t->show();
