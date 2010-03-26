<?php

/**
 * Editor for metadata
 *
 * @author Andreas Åkre Solberg <andreas@uninett.no>, UNINETT AS.
 * @package simpleSAMLphp
 * @version $Id$
 */
class sspmod_metaedit_MetaEditor {


	protected function getStandardField($request, &$metadata, $key) {
		if (array_key_exists('field_' . $key, $request)) {
			$metadata[$key] = $request['field_' . $key];
		} else {
			if (isset($metadata[$key])) unset($metadata[$key]);
		}
	}
	
	protected function getEndpointField($request, &$metadata, $key, $binding, $indexed) {
		if (array_key_exists('field_' . $key, $request)) {
			$e = array(
				'Binding' => $binding,
				'Location' => $request['field_' . $key]
			);
			if ($indexed) {
				$e['index'] = 0;
			}
			$metadata[$key] = array($e);
		} else {
			if (isset($metadata[$key])) unset($metadata[$key]);
		}
	}

	public function formToMeta($request, $metadata = array(), $override = NULL) {
		$this->getStandardField($request, $metadata, 'entityid');
		$this->getStandardField($request, $metadata, 'name');
		$this->getStandardField($request, $metadata, 'description');
		$this->getEndpointField($request, $metadata, 'AssertionConsumerService', SAML2_Const::BINDING_HTTP_POST, TRUE);
		$this->getEndpointField($request, $metadata, 'SingleLogoutService', SAML2_Const::BINDING_HTTP_REDIRECT, FALSE);
		// $this->getStandardField($request, $metadata, 'certFingerprint');
		$metadata['updated'] = time();
		
		if ($override) {
			foreach($override AS $key => $value) {
				$metadata[$key] = $value;
			}
		}
		
		return $metadata;
	}

	protected function requireStandardField($request, $key) {
		if (!array_key_exists('field_' . $key, $request))
			throw new Exception('Required field [' . $key . '] was missing.');
		if (empty($request['field_' . $key]))
			throw new Exception('Required field [' . $key . '] was empty.');
	}

	public function checkForm($request) {
		$this->requireStandardField($request, 'entityid');
		$this->requireStandardField($request, 'name');
	}
	

	protected function header($name) {
		return '<tr ><td>&nbsp;</td><td class="header">' . $name . '</td></tr>';
		
	}
	
	protected function readonlyDateField($metadata, $key, $name) {
		$value = '<span style="color: #aaa">Not set</a>';
		if (array_key_exists($key, $metadata))
			$value = date('j. F Y, G:i', $metadata[$key]);
		return '<tr>
			<td class="name">' . $name . '</td>
			<td class="data">' . $value . '</td></tr>';

	}
	
	protected function readonlyField($metadata, $key, $name) {
		$value = '';
		if (array_key_exists($key, $metadata))
			$value = $metadata[$key];
		return '<tr>
			<td class="name">' . $name . '</td>
			<td class="data">' . htmlspecialchars($value) . '</td></tr>';

	}
	
	protected function hiddenField($key, $value) {
		return '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '" />';
	}
	
	protected function flattenLanguageField(&$metadata, $key) {
		if (array_key_exists($key, $metadata)) {
			if (is_array($metadata[$key])) {
				if (isset($metadata[$key]['en'])) {
					$metadata[$key] = $metadata[$key]['en'];
				} else {
					unset($metadata[$key]);
				}
			}
		}
	}
	
	protected function standardField($metadata, $key, $name, $textarea = FALSE) {
		$value = '';
		if (array_key_exists($key, $metadata)) {
			$value = htmlspecialchars($metadata[$key]);
		}
		#echo '<tr><td><pre>'; print_r($metadata); echo '</pre></td></tr>';
		
		if ($textarea) {
			return '<tr><td class="name">' . $name . '</td><td class="data">
			<textarea name="field_' . $key . '" rows="5" cols="50">' . $value . '</textarea></td></tr>';
			
		} else {
			return '<tr><td class="name">' . $name . '</td><td class="data">
			<input type="text" size="60" name="field_' . $key . '" value="' . $value . '" /></td></tr>';
			
		}
	}
	
	protected function endpointField($metadata, $key, $name, $textarea = FALSE) {
		$value = '';
		if (array_key_exists($key, $metadata)) {
			if (is_array($metadata[$key])) {
				$value = htmlspecialchars($metadata[$key][0]['Location']);
			} else {
				$value = htmlspecialchars($metadata[$key]);	
			}
			
		}
		
		if ($textarea) {
			return '<tr><td class="name">' . $name . '</td><td class="data">
			<textarea name="field_' . $key . '" rows="5" cols="50">' . $value . '</textarea></td></tr>';
			
		} else {
			return '<tr><td class="name">' . $name . '</td><td class="data">
			<input type="text" size="60" name="field_' . $key . '" value="' . $value . '" /></td></tr>';
			
		}
	}

	public function metaToForm($metadata) {
		$this->flattenLanguageField($metadata, 'name');
		$this->flattenLanguageField($metadata, 'description');
		return '<form action="edit.php" method="post">' .
		
			(array_key_exists('entityid', $metadata) ? 
				$this->hiddenField('was-entityid', $metadata['entityid']) :
				'') .
		
			'<div id="tabdiv">' .
			'<ul>' .
			'<li><a href="#basic">Name and descrition</a></li>' . 
			'<li><a href="#saml">SAML 2.0</a></li>' . 
			// '<li><a href="#attributes">Attributes</a></li>' . 
			// '<li><a href="#orgs">Organizations</a></li>' . 
			// '<li><a href="#contacts">Contacts</a></li>' . 
			'</ul>' .
			'<div id="basic"><table class="formtable">' .
				$this->standardField($metadata, 'entityid', 'EntityID') .
				$this->standardField($metadata, 'name', 'Name of service') .
				$this->standardField($metadata, 'description', 'Description of service', TRUE) .
				$this->readonlyField($metadata, 'owner', 'Owner') .
				$this->readonlyDateField($metadata, 'updated', 'Last updated') .
				$this->readonlyDateField($metadata, 'expire', 'Expire') .

			'</table></div><div id="saml"><table class="formtable">' .
				$this->endpointField($metadata, 'AssertionConsumerService', 'AssertionConsumerService endpoint') .
				$this->endpointField($metadata, 'SingleLogoutService', 'SingleLogoutService endpoint') .
				// $this->standardField($metadata, 'certFingerprint', 'Certificate Fingerprint') .			
				
			'</table></div>' .
			'</div>' .
			'<input type="submit" name="submit" value="Save" style="margin-top: 5px" />' .
		'</form>';
	}
	
}


