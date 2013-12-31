<?php
/* ***** BEGIN LICENSE BLOCK *****
 * Version: MPL 1.1
 *
 * The contents of this file are subject to the Mozilla Public License Version
 * 1.1 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * The Original Code is Innowork.
 *
 * The Initial Developer of the Original Code is Innoteam.
 * Portions created by the Initial Developer are Copyright (C) 2002-2009
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Alex Pagnoni <alex.pagnoni@innoteam.it>
 *
 * ***** END LICENSE BLOCK ***** */

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/wui/dispatch/WuiEventsCall.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innowork/core/InnoworkCore.php');

global $gLocale, $gPage_status, $innowork_core;
global $gXml_def, $innowork_core, $gWui, $gPage_status, $gPage_title;
	
$gLocale = new LocaleCatalog(
	'innowork-core::coreprefs',
	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
$innowork_core = InnoworkCore::instance(
	'innoworkcore',
	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomatictoolbar' );
$gWui->loadWidget( 'innomaticpage' );

$gXml_def = '';
$gPage_status = '';
$gPage_title = $gLocale->getStr('innoworkcoreprefs.title');

//$summaries['directorycompany']['widget'] = new WuiLabel( 'mycompany', array( 'label' => 'My company' ) );

// Action dispatcher

$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('empty_trashcan', 'action_empty_trashcan');
function action_empty_trashcan($eventData) {
    global $gLocale, $gPage_status, $innowork_core;

    $innowork_core->EmptyTrashcan();

    $gPage_status = $gLocale->getStr('trashcan_cleaned.status');
}

$gAction_disp->Dispatch();

// Main dispatcher

$main_disp = new WuiDispatcher('view');

function main_tab_action_handler($tab) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('maintab' => $tab))));
}

$main_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gXml_def, $innowork_core, $gLocale, $gWui, $gPage_status, $gPage_title;

    $summaries = $innowork_core->GetSummaries();

    $types[''] = $gLocale->getStr('alltypes.label');

    while (list ($key, $val) = each($summaries)) {
        if ($val['searchable'])
            $types[$key] = $val['label'];
    }
    reset($summaries);
    
    $tab_labels[0]['label'] = $gLocale->getStr('defacls.tab');
    
    $gXml_def =
'
<vertgroup>
  <children>
    <tab>
      <name>settings</name>
      <args>
        <tabs type="array">'.WuiXml::encode($tab_labels).'</tabs>
        <tabactionfunction>main_tab_action_handler</tabactionfunction>
        <activetab>'. (isset($eventData['maintab']) ? $eventData['maintab'] : '').'</activetab>
      </args>
      <children>
        <!-- Default ACLs tab -->
        <vertgroup>
          <children>
            <label>
              <args>
                <bold>true</bold>
                <label type="encoded">'.urlencode($gLocale->getStr('defacls.title')).'</label>
              </args>
            </label>
            <horizbar/>
            <horizgroup>
              <children>
                <vertgroup>
                  <children>';
        
    foreach($summaries as $type => $opts) {
        $class_name = $opts['classname'];
		if (!class_exists($class_name)) {
			continue;
		}
        $tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), 0);

        if ($type != 'defaultaclitem' and $tmp_class->mNoAcl != true and !isset($tmp_class->_mSkipAclSet) and !isset($tmp_class->_mCreationAcl) ) {
            $gXml_def .= '
              <button>
                <args>
                  <label type="encoded">'.urlencode($opts['label']).'</label>
                  <themeimage>'.$opts['icon'].'</themeimage>
                  <themeimagetype>'.$opts['icontype'].'</themeimagetype>
                  <horiz>true</horiz>
                  <disabled>'.( $eventData['setdefacl']==$type ? 'true' : 'false' ).'</disabled>
                  <action type="encoded">'.urlencode(
                    WuiEventsCall::buildEventsCallString(
                        '',
                        array(
                            array(
                                'view',
                                'default',
                                array(
                                    'setdefacl' => $type
                                    )
                                )
                            )
                        )
                    ).'</action>
                </args>
              </button>';
        }
    }
    
    $gXml_def .=
'                  </children>
                </vertgroup>';

    if (isset($eventData['setdefacl'])) {
        
        $check_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute('SELECT * FROM innowork_core_acls_defaults WHERE ownerid='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId().' AND itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($eventData['setdefacl']));
        
        if ( $check_query->getNumberRows() ) {
            $id = $check_query->getFields('id');
        }
        else
        {
            $item = new InnoworkDefaultAclItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
            $params['itemtype'] = $eventData['setdefacl'];
            $item->Create($params);
            $id = $item->mItemId;
        }
        
        $gXml_def .= '
                <vertbar/>
                <vertgroup>
                  <args>
                    <align>center</align>
                  </args>
                  <children>
                    <label>
                      <args>
                        <label type="encoded">'.urlencode($summaries[$eventData['setdefacl']]['label']).'</label>
                        <bold>true</bold>
                      </args>
                    </label>
                    <horizbar/>
      <innoworkitemacl><name>itemacl</name>
        <args>
          <itemtype>defaultaclitem</itemtype>
          <itemid>'.$id.'</itemid>
          <itemownerid>'.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId().'</itemownerid>
          <defaultaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('setdefacl' => $eventData['setdefacl']))))).'</defaultaction>
        </args>
      </innoworkitemacl>
                  </children>
                </vertgroup>
';
    }
    
    $gXml_def .=
'              </children>
            </horizgroup>
          </children>
        </vertgroup>
      </children>
    </tab>
  </children>
</vertgroup>';
}

$main_disp->Dispatch();

$toolbars = $innowork_core->GetMainToolBar();
$innomatictoolbars = array(new WuiInnomaticToolBar('view', array('toolbars' => $toolbars, 'toolbar' => 'true')));
$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'settings1', 'toolbars' => $innomatictoolbars, 'maincontent' => new WuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status)));
$gWui->render();

?>
