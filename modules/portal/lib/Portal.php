<?php

namespace SimpleSAML\Module\portal;

class Portal
{
    /** @var array */
    private $pages;

    /** @var array|null */
    private $config;


    /**
     * @param array $pages
     * @param array|null $config
     */
    public function __construct($pages, $config = null)
    {
        $this->pages = $pages;
        $this->config = $config;
    }


    /**
     * @param string $thispage
     * @return array|null
     */
    public function getTabset($thispage)
    {
        if (!isset($this->config)) {
            return null;
        }
        foreach ($this->config as $set) {
            if (in_array($thispage, $set, true)) {
                return $set;
            }
        }
        return null;
    }


    /**
     * @param string $thispage
     * @return bool
     */
    public function isPortalized($thispage)
    {
        foreach ($this->config as $set) {
            if (in_array($thispage, $set, true)) {
                return true;
            }
        }
        return false;
    }


    /**
     * @param \SimpleSAML\Locate\Translate $translator
     * @param string $thispage
     * @return array
     */
    public function getLoginInfo($translator, $thispage)
    {
        $info = ['info' => '', 'translator' => $translator, 'thispage' => $thispage];
        \SimpleSAML\Module::callHooks('portalLoginInfo', $info);
        return $info['info'];
    }


    /**
     * @param string
     * @return string
     */
    public function getMenu($thispage)
    {
        $config = \SimpleSAML\Configuration::getInstance();
        $t = new \SimpleSAML\Locale\Translate($config);
        $tabset = $this->getTabset($thispage);
        $logininfo = $this->getLoginInfo($t, $thispage);
        $classes = 'tabset_tabs ui-tabs-nav ui-helper-reset ui-helper-clearfix ui-widget-header ui-corner-all';
        $text = '<ul class="'.$classes.'">';
        foreach ($this->pages as $pageid => $page) {
            if (isset($tabset) && !in_array($pageid, $tabset, true)) {
                continue;
            }
            $name = 'uknown';
            if (isset($page['text'])) {
                $name = $page['text'];
            }
            if (isset($page['shorttext'])) {
                $name = $page['shorttext'];
            }
            if (!isset($page['href'])) {
                $text .= '<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#">'.
                    $t->t($name).'</a></li>';
            } elseif ($pageid === $thispage) {
                $text .= '<li class="ui-state-default ui-corner-top ui-tabs-selected ui-state-active"><a href="#">'.
                    $t->t($name).'</a></li>';
            } else {
                $text .= '<li class="ui-state-default ui-corner-top"><a href="'.$page['href'].'">'.
                    $t->t($name).'</a></li>';
            }
        }
        $text .= '</ul>';
        if (!empty($logininfo)) {
            $text .= '<p class="logininfo" style="text-align: right; margin: 0px">'.$logininfo.'</p>';
        }
        return $text;
    }
}
