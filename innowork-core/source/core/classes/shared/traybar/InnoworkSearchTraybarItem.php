<?php
namespace Shared\Traybar;

use \Innomatic\Core\InnomaticContainer;

class InnoworkSearchTraybarItem extends \Innomatic\Desktop\Traybar\TraybarItem
{

    public function prepare()
    {}

    public function getHTML()
    {
        $locale_catalog = new \Innomatic\Locale\LocaleCatalog('innowork-core::misc', InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
        
        $theme = \Innomatic\Wui\Wui::instance('\Innomatic\Wui\Wui')->getTheme();
        $icon = $theme->mIconsBase . $theme->mIconsSet['light']['zoom']['base'] . '/light/' . $theme->mIconsSet['light']['zoom']['file'];
        return '<a href="' . \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'search', ''))) . '" alt="' . $locale_catalog->getStr('search.menu') . '"><img width="25" height="25" align="right" src="' . $icon . '" alt="' . $locale_catalog->getStr('search.menu') . '" /></a>';
    }
}
