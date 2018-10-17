<?php
/**
 * Template which is shown when there is only a short interval since the user was last authenticated.
 *
 * Parameters:
 * - 'target': Target URL.
 * - 'params': Parameters which should be included in the request.
 *
 * @package SimpleSAMLphp
 */


$this->data['403_header'] = $this->t('{authorize:Authorize:403_header}');
$this->data['403_text'] = $this->t('{authorize:Authorize:403_text}');

$this->includeAtTemplateBase('includes/header.php');

echo '<h1>'.$this->data['403_header'].'</h1>';
echo '<p>'.$this->data['403_text'].'</p>';
if (isset($this->data['logoutURL'])) {
    echo '<p><a href="'.htmlspecialchars($this->data['logoutURL']).'">'.$this->t('{status:logout}').'</a></p>';
}

$this->includeAtTemplateBase('includes/footer.php');
