<?php
/**
 * Template which is shown when when an attribute violates a cardinality rule
 *
 * Parameters:
 * - 'target': Target URL.
 * - 'params': Parameters which should be included in the request.
 *
 * @package SimpleSAMLphp
 */


$this->data['cardinality_header'] = $this->t('{core:cardinality:cardinality_header}');
$this->data['cardinality_text'] = $this->t('{core:cardinality:cardinality_text}');
$this->data['problematic_attributes'] = $this->t('{core:cardinality:problematic_attributes}');

$this->includeAtTemplateBase('includes/header.php');
?>
<h1><?php echo $this->data['cardinality_header']; ?></h1>
<p><?php echo $this->data['cardinality_text']; ?></p>
<h3><?php echo $this->data['problematic_attributes']; ?></h3>
<dl class="cardinalityErrorAttributes">
<?php foreach ($this->data['cardinalityErrorAttributes'] as $attr => $v) { ?>
        <dt><?php echo $attr ?></td>
        <dd><?php echo $this->t('{core:cardinality:got_want}', array('%GOT%' => $v[0], '%WANT%' => htmlspecialchars($v[1]))) ?></dd>
    </tr>
<?php } ?>
</dl>
<?php
if (isset($this->data['LogoutURL'])) {
?>
<p><a href="<?php echo htmlspecialchars($this->data['LogoutURL']); ?>"><?php echo $this->t('{status:logout}'); ?></a></p>
<?php
}
?>
<?php
$this->includeAtTemplateBase('includes/footer.php');
