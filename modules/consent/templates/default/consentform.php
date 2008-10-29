<?php

/**
 * Template form for giving consent.
 *
 * Parameters:
 * - 'srcMetadata': Metadata/configuration for the source.
 * - 'dstMetadata': Metadata/configuration for the destination.
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'yesData': Parameters which should be included in the yes-request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'noData': Parameters which should be included in the no-request.
 * - 'attributes': The attributes which are about to be released.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */
assert('is_array($this->data["srcMetadata"])');
assert('is_array($this->data["dstMetadata"])');
assert('is_string($this->data["yesTarget"])');
assert('is_array($this->data["yesData"])');
assert('is_string($this->data["noTarget"])');
assert('is_array($this->data["noData"])');
assert('is_array($this->data["attributes"])');
assert('$this->data["sppp"] === FALSE || is_string($this->data["spp"])');


/* Parse parameters. */

if (array_key_exists('name', $this->data['srcMetadata'])) {
	$srcName = $this->data['srcMetadata']['name'];
	if (is_array($srcName)) {
		$srcName = $this->t($srcName);
	}
} else {
	$srcName = $this->data['srcMetadata']['entityid'];
}

if (array_key_exists('name', $this->data['dstMetadata'])) {
	$dstName = $this->data['dstMetadata']['name'];
	if (is_array($dstName)) {
		$dstName = $this->t($dstName);
	}
} else {
	$dstName = $this->data['dstMetadata']['entityid'];
}

$spPurpose = 'unspecified';
if (array_key_exists('descr_purpose', $this->data['dstMetadata'])) {
	$spPurpose = $this->data['dstMetadata']['descr_purpose'];
	if (is_array($spPurpose)) {
		$spPurpose = $this->t($spPurpose);
	}
}



$attributes = $this->data['attributes'];


$this->data['header'] = 'Consent'; /* TODO: translation */
$this->includeAtTemplateBase('includes/header.php');
?>
<div id="content">

<p>
<!-- notice:<?php echo $this->t('{consent:consent_notice}'); ?> <strong><?php echo htmlspecialchars($dstName); ?></strong>. -->
<?php echo $this->t('{consent:consent_accept}', array(
	'IDPNAME' => $srcName,
	'SPNAME' => $dstName,
	'SPDESC' => $spPurpose,
)) ?>
</p>

<?php
if ($this->data['sppp'] !== FALSE) {
	echo "<p>" . htmlspecialchars($this->t('consent_privacypolicy')) . " ";
	echo "<a target='_new_window' href='" . htmlspecialchars($this->data['sppp']) . "'>" . htmlspecialchars($dstName) . "</a>";
	echo "</p>";
}
?>

<form style="display: inline" action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>">
	<input type="submit" name="yes" id="yesbutton" value="<?php echo $this->t('{consent:yes}') ?>" />
<?php
foreach ($this->data['yesData'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
if ($this->data['usestorage']) {
	echo('<input type="checkbox" name="saveconsent" value="1" /> ' . $this->t('{consent:remember}'));
}
?>
</form>

<form style="display: inline; margin-left: .5em;" action="<?php echo htmlspecialchars($this->data['noTarget']); ?>" method="GET">
<?php
foreach ($this->data['noData'] as $name => $value) {
	echo('<input type="hidden" name="' . htmlspecialchars($name) . '" value="' . htmlspecialchars($value) . '" />');
}
?>
	<input type="submit" id="nobutton" value="<?php echo htmlspecialchars($this->t('{consent:no}')) ?>" />
</form>

<p>
<table style="font-size: x-small">
<?php
foreach ($attributes as $name => $value) {
	$nameTag = '{attributes:attribute_' . strtolower($name) . '}';
	if ($this->getTag($nameTag) !== NULL) {
		$name = $this->t($nameTag);
	}

	if (sizeof($value) > 1) {
		echo '<tr><td>' . htmlspecialchars($name) . '</td><td><ul>';
		foreach ($value AS $v) {
			echo '<li>' . htmlspecialchars($v) . '</li>';
		}
		echo '</ul></td></tr>';
	} else {
		echo '<tr><td>' . htmlspecialchars($name) . '</td><td>' . htmlspecialchars($value[0]) . '</td></tr>';
	}
}

?>
</table>
</p>

<?php
$this->includeAtTemplateBase('includes/footer.php');
?>