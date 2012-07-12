<?php 

$this->data['header'] = $this->t('{consentSimpleAdmin:consentsimpleadmin:headerstats}');
$this->includeAtTemplateBase('includes/header.php'); 

?>


<p><?php

echo '<p>' . $this->t('{consentSimpleAdmin:consentsimpleadmin:stattotal}', array('%NO%' => $this->data['stats']['total'])) . '</p>';
echo '<p>' . $this->t('{consentSimpleAdmin:consentsimpleadmin:statusers}', array('%NO%' => $this->data['stats']['users'])) . '</p>';
echo '<p>' . $this->t('{consentSimpleAdmin:consentsimpleadmin:statservices}', array('%NO%' => $this->data['stats']['services'])) . '</p>';



?></p>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
