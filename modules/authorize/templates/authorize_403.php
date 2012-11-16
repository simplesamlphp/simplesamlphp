<?php
/**
 * Template which is shown when there is only a short interval since the user was last authenticated.
 *
 * Parameters:
 * - 'target': Target URL.
 * - 'params': Parameters which should be included in the request.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

global $state;

$this->data['403_header'] = $this->t('{authorize:Authorize:403_header}');
$this->data['403_text'] = $this->t('{authorize:Authorize:403_text}');

$this->includeAtTemplateBase('includes/header.php');
?>
<h1><?php echo $this->data['403_header']; ?></h1>
<p><?php echo $this->data['403_text']; ?></p>
<p><a href="<?php echo SimpleSAML_Module::getModuleURL('core/authenticate.php', array('as' => $state['Source']['auth']))."&logout"; ?>"><?php echo $this->t('{status:logout}'); ?></a></p>
<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
