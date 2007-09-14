<?php


/**
 * SimpleSAMLphp
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @author Andreas Åkre Solberg, UNINETT AS. <andreas.solberg@uninett.no>
 */
 
require_once('SimpleSAML/Configuration.php');
 
/**
 * Configuration of SimpleSAMLphp
 */
class SimpleSAML_XHTML_Template {

	private $configuration = null;
	private $template = 'default.php';
	
	public $data = null;

	function __construct(SimpleSAML_Configuration $configuration, $template) {
		$this->configuration = $configuration;
		$this->template = $template;
		
		$this->data['baseurlpath'] = $this->configuration->getValue('baseurlpath');
	}
	
	public function show() {
		$data = $this->data;
		$filename = $this->configuration->getValue('templatedir') . '/' . $this->template;
		if (!file_exists($filename)) {
			throw new Exception('Could not find template file [' . $this->template . '] at [' . $filename . ']');
		}
		require_once($filename);
	}
	
	
}

?>