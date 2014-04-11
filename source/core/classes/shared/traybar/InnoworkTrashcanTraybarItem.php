<?php
namespace Shared\Traybar;

use \Innomatic\Core\InnomaticContainer;

class InnoworkTrashcanTraybarItem extends \Innomatic\Desktop\Traybar\TraybarItem
{

    public function prepare()
    {}

    public function getHTML()
    {
        $locale_catalog = new \Innomatic\Locale\LocaleCatalog('innowork-core::misc', InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
        
        $theme = \Innomatic\Wui\Wui::instance('\Innomatic\Wui\Wui')->getTheme();
        $icon = $theme->mIconsBase . $theme->mIconsSet['light']['trash']['base'] . '/light/' . $theme->mIconsSet['light']['trash']['file'];
        return '<a href="' . \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'trashcan', ''))) . '" alt="' . $locale_catalog->getStr('trashcan.menu') . '"><img width="25" height="25" align="right" src="' . $icon . '" alt="' . $locale_catalog->getStr('trashcan.menu') . '" /></a>';
    }
}
