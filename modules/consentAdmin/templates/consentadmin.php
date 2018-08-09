<?php 
    $this->data['head'] = '<link rel="stylesheet" type="text/css" href="'.SimpleSAML\Module::getModuleURL("consentAdmin/assets/css/consentAdmin.css").'" />'."\n";
    $this->data['head'] .= '<script type="text/javascript" src="'.SimpleSAML\Module::getModuleURL("consentAdmin/assets/js/consentAdmin.js").'"></script>';
    $this->data['head'] .= '<script type="text/javascript" src="'.SimpleSAML\Module::getModuleURL("consentAdmin/assets/js/consentSimpleAjax.js").'"></script>';
    // default theme
    $this->includeAtTemplateBase('includes/header.php');
?>
        <!-- <h2><?php if (isset($this->data['header'])) { echo $this->t($this->data['header']); } else { echo "Some error occurred"; } ?></h2> -->
        <h2><?php echo $this->t('{consentAdmin:consentadmin:consentadmin_header}') ?></h2>
        <p>
        <?php echo $this->t('{consentAdmin:consentadmin:consentadmin_description1}') ?> </p>


            <table>
            <tr>
                <th width="80%"><?php echo $this->t('{consentAdmin:consentadmin:service_provider_header}') ?></th>
                <th width="140"><?php echo $this->t('{consentAdmin:consentadmin:status_header}') ?></th>
            </tr>
            <?php
            $spList = $this->data['spList'];
            $show_spid = 0;
            $show_text = $this->t('{consentAdmin:consentadmin:show}');
            $hide_text = $this->t('{consentAdmin:consentadmin:hide}');
            $attributes_text = $this->t('{consentAdmin:consentadmin:attributes_text}');
            foreach ($spList as $spName => $spValues) {
                $this->getTranslator()->includeInlineTranslation('spname', $spValues['name']);
                $this->getTranslator()->includeInlineTranslation('spdescription', $spValues['description']);
                if (!is_null($spValues['serviceurl'])) {
                    $htmlSpName = '<a href="'.$spValues['serviceurl'].'" style="color: black; font-weight: bold;">'.htmlspecialchars($this->t('spname', array(), false, true)).'</a>';
                } else {
                    $htmlSpName = htmlspecialchars($this->t('spname', array(), false, true));
                }
                $spDescription = htmlspecialchars($this->t('spdescription', array(), false, true));
                $checkedAttr = $spValues['consentStatus'] == 'ok' ? 'checked="checked"' : '';
                $consentValue = $spValues['consentValue'];
                $consentText = $spValues['consentStatus'] == 'changed' ? "attributes has changed" : "";
                $row_class = ($show_spid % 2) ? "row0" : "row1";
                echo <<<TRSTART
<tr class="$row_class">
<td>
    <table>
      <tr class="$row_class"><td><span class='caSPName'><span title='$spDescription'>$htmlSpName</span>&emsp;<span style="font-size: 80%;"onclick="javascript:toggleShowAttributes('$show_spid');"><span id='showing_$show_spid'>$show_text</span><span id='hiding_$show_spid' style='display:none;'>$hide_text</span>$attributes_text</span></span></td></tr>
      <tr><td colspan="2" class="caAttributes"><div id="attributes_$show_spid" style="display: none;">
TRSTART;
                $attributes = $spValues['attributes_by_sp'];
                if ($this->data['showDescription']) {
                    echo '<p>'.$this->t('{consentAdmin:consentadmin:consentadmin_purpose}').' '.$spDescription.'</p>';
                }
                echo "\n<ul>\n";
                foreach ($attributes as $name => $value) {

                if (isset($this->data['attribute_'.htmlspecialchars(strtolower($name))])) {
                    $name = $this->data['attribute_'.htmlspecialchars(strtolower($name))];
                }
                $name = $this->getTranslator()->getAttributeTranslation($name); // translate
                if (sizeof($value) > 1) {
                        echo "<li>".htmlspecialchars($name).":\n<ul>\n";
                        foreach ($value as $v) {
                            echo '<li>'.htmlspecialchars($v)."</li>\n";
                        }
                        echo "</ul>\n</li>\n";
                    } else {
                        echo "<li>".htmlspecialchars($name).": ".htmlspecialchars($value[0])."</li>\n";
                    }
                }
                echo "</ul>";
                echo <<<TRSTART
      </div></td></tr>
  </table>
</td>

<td class='caAllowed'><input onclick="javascript:checkConsent(this.value, $show_spid, this.checked)" value='$consentValue' type='checkbox' $checkedAttr /><span id="consentText$show_spid">$consentText</span></td>
TRSTART;
                echo "</tr>\n";
                $show_spid++;
            }
            ?>
            </table>

            <p>
        <?php echo $this->t('{consentAdmin:consentadmin:consentadmin_description2}') ?> </p>

        <h2>Logout</h2>

            <p><a href="<?php echo \SimpleSAML\Module::getModuleURL('consentAdmin/consentAdmin.php', array('logout' => 1)); ?>">Logout</a></p>

<?php $this->includeAtTemplateBase('includes/footer.php');
