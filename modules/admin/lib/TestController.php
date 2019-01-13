<?php

namespace SimpleSAML\Module\admin;

use SimpleSAML\HTTP\RunnableResponse;
use SimpleSAML\Locale\Translate;
use SimpleSAML\Utils\HTTP;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller class for the admin module.
 *
 * This class serves the 'Test authentication sources' views available in the module.
 *
 * @package SimpleSAML\Module\admin
 */
class TestController
{

    /** @var \SimpleSAML\Configuration */
    protected $config;

    /** @var Menu */
    protected $menu;

    /** @var \SimpleSAML\Session */
    protected $session;


    /**
     * ConfigController constructor.
     *
     * @param \SimpleSAML\Configuration $config The configuration to use.
     * @param \SimpleSAML\Session $session The current user session.
     */
    public function __construct(\SimpleSAML\Configuration $config, \SimpleSAML\Session $session)
    {
        $this->config = $config;
        $this->session = $session;
        $this->menu = new Menu();
    }


    /**
     * Display the list of available authsources.
     *
     * @return \SimpleSAML\XHTML\Template
     */
    public function main(Request $request, $as)
    {
        \SimpleSAML\Utils\Auth::requireAdmin();
        if (is_null($as)) {
            $t = new \SimpleSAML\XHTML\Template($this->config, 'admin:authsource_list.twig');
            $t->data = [
                'sources' => \SimpleSAML\Auth\Source::getSources(),
            ];
        } else {
            $authsource = new \SimpleSAML\Auth\Simple($as);
            if (!is_null($request->query->get('logout'))) {
                $authsource->logout($this->config->getBasePath().'logout.php');
            } elseif (!is_null($request->query->get(\SimpleSAML\Auth\State::EXCEPTION_PARAM))) {
                // This is just a simple example of an error
                $state = \SimpleSAML\Auth\State::loadExceptionState();
                assert(array_key_exists(\SimpleSAML\Auth\State::EXCEPTION_DATA, $state));
                throw $state[\SimpleSAML\Auth\State::EXCEPTION_DATA];
            }

            if (!$authsource->isAuthenticated()) {
                $url = \SimpleSAML\Module::getModuleURL('admin/test/' .$as, []);
                $params = [
                    'ErrorURL' => $url,
                    'ReturnTo' => $url,
                ];
                $authsource->login($params);
            }

            $attributes = $authsource->getAttributes();
            $authData = $authsource->getAuthDataArray();
            $nameId = !is_null($authsource->getAuthData('saml:sp:NameID')) ? $authsource->getAuthData('saml:sp:NameID') : false;

            $t = new \SimpleSAML\XHTML\Template($this->config, 'admin:status.twig', 'attributes');
            $t->data = [
                'attributes' => $attributes,
                'attributesHtml' => $this->getAttributesHTML($t, $attributes, ''),
                'authData' => $authData,
                'nameid' => $nameId,
                'logouturl' => \SimpleSAML\Utils\HTTP::getSelfURLNoQuery().'?as='.urlencode($as).'&logout',
            ];

            if ($nameId !== false) {
                $t->data['nameidHtml'] = $this->getNameIDHTML($t, $nameId);
            }
        }

        \SimpleSAML\Module::callHooks('configpage', $t);
        $this->menu->addOption('logout', \SimpleSAML\Utils\Auth::getAdminLogoutURL(), Translate::noop('Log out'));
        return $this->menu->insert($t);
    }


    private function getNameIDHTML(\SimpleSAML\XHTML\Template $t, \SAML2\XML\saml\NameID $nameId)
    {
        $result = '';
        if ($nameId->getValue() === null) {
            $list = ["NameID" => [$t->t('{status:subject_notset}')]];
            $result .= "<p>NameID: <span class=\"notset\">".$t->t('{status:subject_notset}')."</span></p>";
        } else {
            $list = [
                "NameId" => [$nameId->getValue()],
            ];
            if ($nameId->getFormat() !== null) {
                $list[$t->t('{status:subject_format}')] = [$nameId->getFormat()];
            }
            if ($nameId->getNameQualifier() !== null) {
                $list['NameQualifier'] = [$nameId->getNameQualifier()];
            }
            if ($nameId->getSPNameQualifier() !== null) {
                $list['SPNameQualifier'] = [$nameId->getSPNameQualifier()];
            }
            if ($nameId->getSPProvidedID() !== null) {
                $list['SPProvidedID'] = [$nameId->getSPProvidedID()];
            }
        }
        return $result.$this->getAttributesHTML($t, $list, '');
    }


    private function getAttributesHTML(\SimpleSAML\XHTML\Template $t, $attributes, $nameParent)
    {
        $alternate = ['pure-table-odd', 'pure-table-even'];
        $i = 0;
        $parentStr = (strlen($nameParent) > 0) ? strtolower($nameParent).'_' : '';
        $str = (strlen($nameParent) > 0) ? '<table class="pure-table pure-table-attributes" summary="attribute overview">' :
            '<table id="table_with_attributes" class="pure-table pure-table-attributes" summary="attribute overview">';
        foreach ($attributes as $name => $value) {
            $nameraw = $name;
            $trans = $t->getTranslator();
            $name = $trans->getAttributeTranslation($parentStr.$nameraw);
            if (preg_match('/^child_/', $nameraw)) {
                $parentName = preg_replace('/^child_/', '', $nameraw);
                foreach ($value as $child) {
                    $str .= '<tr class="odd"><td colspan="2" style="padding: 2em">'.
                        $this->getAttributesHTML($t, $child, $parentName).'</td></tr>';
                }
            } else {
                if (sizeof($value) > 1) {
                    $str .= '<tr class="'.$alternate[($i++ % 2)].'"><td class="attrname">';
                    if ($nameraw !== $name) {
                        $str .= htmlspecialchars($name).'<br/>';
                    }
                    $str .= '<code>'.htmlspecialchars($nameraw).'</code>';
                    $str .= '</td><td class="attrvalue"><ul>';
                    foreach ($value as $listitem) {
                        if ($nameraw === 'jpegPhoto') {
                            $str .= '<li><img src="data:image/jpeg;base64,'.htmlspecialchars($listitem).'" /></li>';
                        } else {
                            $str .= '<li>'.$this->present_assoc($listitem).'</li>';
                        }
                    }
                    $str .= '</ul></td></tr>';
                } elseif (isset($value[0])) {
                    $str .= '<tr class="'.$alternate[($i++ % 2)].'"><td class="attrname">';
                    if ($nameraw !== $name) {
                        $str .= htmlspecialchars($name).'<br/>';
                    }
                    $str .= '<code>'.htmlspecialchars($nameraw).'</code>';
                    $str .= '</td>';
                    if ($nameraw === 'jpegPhoto') {
                        $str .= '<td class="attrvalue"><img src="data:image/jpeg;base64,'.htmlspecialchars($value[0]).
                            '" /></td></tr>';
                    } elseif (is_a($value[0], 'DOMNodeList')) {
                        // try to see if we have a NameID here
                        /** @var \DOMNodeList $value [0] */
                        $n = $value[0]->length;
                        for ($idx = 0; $idx < $n; $idx++) {
                            $elem = $value[0]->item($idx);
                            /* @var \DOMElement $elem */
                            if (!($elem->localName === 'NameID' && $elem->namespaceURI === \SAML2\Constants::NS_SAML)) {
                                continue;
                            }
                            $str .= $this->present_eptid($trans, new \SAML2\XML\saml\NameID($elem));
                            break; // we only support one NameID here
                        }
                        $str .= '</td></tr>';
                    } elseif (is_a($value[0], '\SAML2\XML\saml\NameID')) {
                        $str .= $this->present_eptid($trans, $value[0]);
                        $str .= '</td></tr>';
                    } else {
                        $str .= '<td class="attrvalue">'.htmlspecialchars($value[0]).'</td></tr>';
                    }
                }
            }
            $str .= "\n";
        }
        $str .= '</table>';
        return $str;
    }

    private function present_list($attr)
    {
        if (is_array($attr) && count($attr) > 1) {
            $str = '<ul>';
            foreach ($attr as $value) {
                $str .= '<li>'.htmlspecialchars($attr).'</li>';
            }
            $str .= '</ul>';
            return $str;
        } else {
            return htmlspecialchars($attr[0]);
        }
    }

    private function present_assoc($attr)
    {
        if (is_array($attr)) {
            $str = '<dl>';
            foreach ($attr as $key => $value) {
                $str .= "\n".'<dt>'.htmlspecialchars($key).'</dt><dd>'.$this->present_list($value).'</dd>';
            }
            $str .= '</dl>';
            return $str;
        } else {
            return htmlspecialchars($attr);
        }
    }

    private function present_eptid(\SimpleSAML\Locale\Translate $t, \SAML2\XML\saml\NameID $nameID)
    {
        $eptid = [
            'NameID' => [$nameID->getValue()],
        ];
        if ($nameID->getFormat() !== null) {
            $eptid[$t->t('{status:subject_format}')] = [$nameID->getFormat()];
        }
        if ($nameID->getNameQualifier() !== null) {
            $eptid['NameQualifier'] = [$nameID->getNameQualifier()];
        }
        if ($nameID->getSPNameQualifier() !== null) {
            $eptid['SPNameQualifier'] = [$nameID->getSPNameQualifier()];
        }
        if ($nameID->getSPProvidedID() !== null) {
            $eptid['SPProvidedID'] = [$nameID->getSPProvidedID()];
        }
        return '<td class="attrvalue">'.$this->present_assoc($eptid);
    }
}
