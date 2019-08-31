<?php

namespace SimpleSAML\Module\portal;

class Portal
{
    private $pages;
    private $config;

    public function __construct($pages, $config = null)
    {
        $this->pages = $pages;
        $this->config = $config;
    }

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

    public function isPortalized($thispage)
    {
        foreach ($this->config as $set) {
            if (in_array($thispage, $set, true)) {
                return true;
            }
        }
        return false;
    }

    public function getLoginInfo($translator, $thispage)
    {
        $info = ['info' => '', 'translator' => $translator, 'thispage' => $thispage];
        \SimpleSAML\Module::callHooks('portalLoginInfo', $info);
        return $info['info'];
    }

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
