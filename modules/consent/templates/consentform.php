<?php
/**
 * Template form for giving consent.
 *
 * Parameters:
 * - 'yesTarget': Target URL for the yes-button. This URL will receive a POST request.
 * - 'noTarget': Target URL for the no-button. This URL will receive a GET request.
 * - 'sppp': URL to the privacy policy of the destination, or FALSE.
 *
 * @package SimpleSAMLphp
 */
assert(is_string($this->data['yesTarget']));
assert(is_string($this->data['noTarget']));
assert($this->data['sppp'] === false || is_string($this->data['sppp']));

// Parse parameters
$dstName = $this->data['dstName'];
$srcName = $this->data['srcName'];

$this->data['header'] = $this->t('{consent:consent:consent_header}');
$this->data['head'] = '<link rel="stylesheet" type="text/css" href="'.
    SimpleSAML\Module::getModuleURL("consent/style.css").'" />'."\n";

$this->includeAtTemplateBase('includes/header.php');
?>
<p><?php echo $this->data['consent_accept']; ?></p>

<?php
if (isset($this->data['consent_purpose'])) {
    echo '<p>'.$this->data['consent_purpose'].'</p>';
}
?>

<form id="consent_yes" action="<?php echo htmlspecialchars($this->data['yesTarget']); ?>">
<?php
if ($this->data['usestorage']) {
    $checked = ($this->data['checked'] ? 'checked="checked"' : '');
    echo '<input type="checkbox" name="saveconsent" '.$checked.
        ' value="1" /> '.$this->t('{consent:consent:remember}');
} // Embed hidden fields...
?>
    <input type="hidden" name="StateId" value="<?php echo htmlspecialchars($this->data['stateId']); ?>" />
    <button type="submit" name="yes" class="btn" id="yesbutton">
        <?php echo htmlspecialchars($this->t('{consent:consent:yes}')) ?>
    </button>
</form>

<form id="consent_no" action="<?php echo htmlspecialchars($this->data['noTarget']); ?>">
    <input type="hidden" name="StateId" value="<?php echo htmlspecialchars($this->data['stateId']); ?>" />
    <button type="submit" class="btn" name="no" id="nobutton">
        <?php echo htmlspecialchars($this->t('{consent:consent:no}')) ?>
    </button>
</form>

<?php
if ($this->data['sppp'] !== false) {
    echo "<p>".htmlspecialchars($this->t('{consent:consent:consent_privacypolicy}'))." ";
    echo '<a target="_blank" href="'.htmlspecialchars($this->data['sppp']).'">'.$dstName."</a>";
    echo "</p>";
}

/**
 * Recursive attribute array listing function
 *
 * @param \SimpleSAML\XHTML\Template $t          Template object
 * @param array                      $attributes Attributes to be presented
 * @param string                     $nameParent Name of parent element
 *
 * @return string HTML representation of the attributes 
 */
function present_attributes($t, $attributes, $nameParent)
{
    $translator = $t->getTranslator();

    $alternate = array('odd', 'even');
    $i = 0;
    $summary = 'summary="'.$t->t('{consent:consent:table_summary}').'"';

    if (strlen($nameParent) > 0) {
        $parentStr = strtolower($nameParent).'_';
        $str = '<table class="attributes" '.$summary.'>';
    } else {
        $parentStr = '';
        $str = '<table id="table_with_attributes"  class="attributes" '.$summary.'>';
        $str .= "\n".'<caption>'.$t->t('{consent:consent:table_caption}').'</caption>';
    }

    foreach ($attributes as $name => $value) {
        $nameraw = $name;
        $name = $translator->getAttributeTranslation($parentStr.$nameraw);

        if (preg_match('/^child_/', $nameraw)) {
            // insert child table
            $parentName = preg_replace('/^child_/', '', $nameraw);
            foreach ($value as $child) {
                $str .= "\n".'<tr class="odd"><td style="padding: 2em">'.
                    present_attributes($t, $child, $parentName).'</td></tr>';
            }
        } else {
            // insert values directly

            $str .= "\n".'<tr class="'.$alternate[($i++ % 2)].
                '"><td><span class="attrname">'.htmlspecialchars($name).'</span>';

            $isHidden = in_array($nameraw, $t->data['hiddenAttributes'], true);
            if ($isHidden) {
                $hiddenId = SimpleSAML\Utils\Random::generateID();

                $str .= '<div class="attrvalue" style="display: none;" id="hidden_'.$hiddenId.'">';
            } else {
                $str .= '<div class="attrvalue">';
            }

            if (sizeof($value) > 1) {
                // we hawe several values
                $str .= '<ul>';
                foreach ($value as $listitem) {
                    if ($nameraw === 'jpegPhoto') {
                        $str .= '<li><img src="data:image/jpeg;base64,'.
                            htmlspecialchars($listitem).
                            '" alt="User photo" /></li>';
                    } else {
                        $str .= '<li>'.htmlspecialchars($listitem).'</li>';
                    }
                }
                $str .= '</ul>';
            } elseif (isset($value[0])) {
                // we hawe only one value
                if ($nameraw === 'jpegPhoto') {
                    $str .= '<img src="data:image/jpeg;base64,'.
                        htmlspecialchars($value[0]).
                        '" alt="User photo" />';
                } else {
                    $str .= htmlspecialchars($value[0]);
                }
            } // end of if multivalue
            $str .= '</div>';

            if ($isHidden) {
                $str .= '<div class="attrvalue consent_showattribute" id="visible_'.$hiddenId.'">';
                $str .= '... ';
                $str .= '<a class="consent_showattributelink" href="javascript:SimpleSAML_show(\'hidden_'.$hiddenId;
                $str .= '\'); SimpleSAML_hide(\'visible_'.$hiddenId.'\');">';
                $str .= $t->t('{consent:consent:show_attribute}');
                $str .= '</a>';
                $str .= '</div>';
            }

            $str .= '</td></tr>';
        }	// end else: not child table
    }	// end foreach
    $str .= isset($attributes) ? '</table>' : '';
    return $str;
}

echo '<h3 id="attributeheader">'.
    $this->t(
        '{consent:consent:consent_attributes_header}',
        array('SPNAME' => $dstName, 'IDPNAME' => $srcName)
    ).'</h3>';

echo $this->data['attributes_html'];

$this->includeAtTemplateBase('includes/footer.php');
