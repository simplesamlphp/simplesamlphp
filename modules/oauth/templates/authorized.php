<?php

$this->data['header'] = 'OAuth Authorization';
$this->includeAtTemplateBase('includes/header.php');

?>

    <p style="margin-top: 2em">
       You are now successfully authenticated, and you may click <em>Continue</em> in the application where you initiated authentication.
    </p>


<?php
$this->includeAtTemplateBase('includes/footer.php');
?>