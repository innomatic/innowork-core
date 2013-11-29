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

// ----- Initialization -----
//

require_once('innomatic/wui/Wui.php');
require_once('innomatic/wui/dispatch/WuiDispatcher.php');
require_once('innomatic/locale/LocaleCatalog.php');
require_once('innowork/core/InnoworkCore.php');
require_once('innowork/core/clipping/InnoworkClipping.php');

    global $gPage_status, $gLocale;
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars, $gInnowork_core, $customers;
    
$gInnowork_core = InnoworkCore::instance('innoworkcore', InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
$gLocale = new LocaleCatalog('innowork-core::clippings', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());

$gWui = Wui::instance('wui');
$gWui->LoadWidget('xml');
$gWui->LoadWidget('innomaticpage');
$gWui->LoadWidget('innomatictoolbar');

$gXml_def = $gPage_status = '';
$gPage_title = $gLocale->getStr('innoworkclippings.title');
$gCore_toolbars = $gInnowork_core->GetMainToolBar();
$gToolbars['mail'] = array('clippings' => array('label' => $gLocale->getStr('clippings.toolbar'), 'themeimage' => 'listicons', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('done' => 'false'))))), 'newclipping' => array('label' => $gLocale->getStr('newclipping.toolbar'), 'themeimage' => 'filenew', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'newclipping', '')))));

// ----- Action dispatcher -----
//
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('newclipping', 'action_newclipping');
function action_newclipping($eventData) {
    global $gPage_status, $gLocale;

    $clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());

    if ($clipping->Create($eventData)) {
        $gPage_status = $gLocale->getStr('clipping_created.status');
    }
    else
        $gPage_status = $gLocale->getStr('clipping_not_created.status');
}

$gAction_disp->addEvent('editclipping', 'action_editclipping');
function action_editclipping($eventData) {
    global $gPage_status, $gLocale;

    $clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($clipping->Edit($eventData))
        $gPage_status = $gLocale->getStr('clipping_updated.status');
    else
        $gPage_status = $gLocale->getStr('clipping_not_updated.status');
}

$gAction_disp->addEvent('trashclipping', 'action_trashclipping');
function action_trashclipping($eventData) {
    global $gPage_status, $gLocale;

    $clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    if ($clipping->Trash(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()))
        $gPage_status = $gLocale->getStr('clipping_trashed.status');
    else
        $gPage_status = $gLocale->getStr('clipping_not_trashed.status');
}

$gAction_disp->addEvent('remove_item', 'action_removeitem');
function action_removeitem($eventData) {
    global $gPage_status, $gLocale;

    $clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $clipping->RemoveItem($eventData['itemtype'], $eventData['itemid']);
    //    if ( $clipping->Edit( $eventData ) ) $gPage_status = $gLocale->getStr( 'clipping_updated.status' );
    //    else $gPage_status = $gLocale->getStr( 'clipping_not_updated.status' );
}

$gAction_disp->Dispatch();

// ----- Main dispatcher -----
//
$gMain_disp = new WuiDispatcher('view');

function clippings_list_action_builder($pageNumber) {
    return WuiEventsCall::buildEventsCallString('', array(array('view', 'default', array('pagenumber' => $pageNumber))));
}

$gMain_disp->addEvent('default', 'main_default');
function main_default($eventData) {
    global $gLocale, $gPage_title, $gXml_def, $gPage_status, $gToolbars, $gInnowork_core, $customers;

    $innowork_clippings = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
    $search_results = $innowork_clippings->Search('', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId());

    $headers[1]['label'] = $gLocale->getStr('name.header');
    $headers[2]['label'] = $gLocale->getStr('description.header');

    $gXml_def = '
    <vertgroup>
      <children>
    
        <table><name>clippings</name>
          <args>
            <headers type="array">'.WuiXml::encode($headers).'</headers>
            <rowsperpage>15</rowsperpage>
            <pagesactionfunction>clippings_list_action_builder</pagesactionfunction>
            <pagenumber>'. (isset($eventData['pagenumber']) ? $eventData['pagenumber'] : '').'</pagenumber>
          </args>
          <children>';

    $row = 0;

    foreach ($search_results as $id => $clipping) {
        switch ($clipping['_acl']['type']) {
            case InnoworkAcl::TYPE_PRIVATE :
                $image = 'personal';
                break;

            case InnoworkAcl::TYPE_PUBLIC :
            case InnoworkAcl::TYPE_ACL :
                $image = 'kuser';
                break;
        }

        $gXml_def.= '<button row="'.$row.'" col="0"><name>acl</name>
          <args>
            <themeimage>'.$image.'</themeimage>
            <themeimagetype>mini</themeimagetype>
          </args>
        </button>
        <button row="'.$row.'" col="1">
              <args>
                <label type="encoded">'.urlencode($clipping['name']).'</label>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'showclipping', array('id' => $id))))).'</action>
              </args>
            </button>
            <label row="'.$row.'" col="2">
              <args>
                <label type="encoded">'.urlencode($clipping['description']).'</label>
                <compact>true</compact>
              </args>
            </label>
        <innomatictoolbar row="'.$row.'" col="3"><name>tools</name>
          <args>
            <frame>false</frame>
            <toolbars type="array">'.WuiXml::encode(array('show' => array('show' => array('label' => $gLocale->getStr('showclipping.button'), 'themeimage' => 'zoom', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'showclipping', array('id' => $clipping['id']))))), 'edit' => array('label' => $gLocale->getStr('editclipping.button'), 'themeimage' => 'pencil', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'editclipping', array('id' => $clipping['id']))))), 'trash' => array('label' => $gLocale->getStr('trashclipping.button'), 'themeimage' => 'trash', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'default', ''), array('action', 'trashclipping', array('id' => $clipping['id'])))))))).'</toolbars>
          </args>
        </innomatictoolbar>';

        $row ++;
    }

    $gXml_def.= '      </children>
        </table>
    
      </children>
    </vertgroup>';
}

$gMain_disp->addEvent('newclipping', 'main_newclipping');
function main_newclipping($eventData) {
    global $gXml_def, $gLocale, $customers;

    $headers[0]['label'] = $gLocale->getStr('newclipping.header');

    $gXml_def = '
    <vertgroup>
      <children>
    
        <table>
          <args>
            <headers type="array">'.WuiXml::encode($headers).'</headers>
          </args>
          <children>
    
            <form row="0" col="0">
              <name>newclipping</name>
              <args>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default'), array('action', 'newclipping')))).'</action>
              </args>
              <children>
                <grid>
                  <children>
    
                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode($gLocale->getStr('name.label')).'</label>
                      </args>
                    </label>
    
                    <string row="0" col="1"><name>name</name>
                      <args>
                        <disp>action</disp>
                        <size>20</size>
                      </args>
                    </string>
    
                    <label row="1" col="0">
                      <args>
                        <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                      </args>
                    </label>
    
                    <text row="1" col="1"><name>description</name>
                      <args>
                        <disp>action</disp>
                        <rows>5</rows>
                        <cols>40</cols>
                      </args>
                    </text>
    
                  </children>
                </grid>
              </children>
            </form>
    
            <horizgroup row="1" col="0">
              <children>
    
                <button>
                  <args>
                    <themeimage>buttonok</themeimage>
                    <label type="encoded">'.urlencode($gLocale->getStr('new_clipping.button')).'</label>
                    <formsubmit>newclipping</formsubmit>
                    <frame>false</frame>
                    <horiz>true</horiz>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default'), array('action', 'newclipping')))).'</action>
                  </args>
                </button>
    
              </children>
            </horizgroup>
    
          </children>
        </table>
    
      </children>
    </vertgroup>';
}

$gMain_disp->addEvent('editclipping', 'main_editclipping');
function main_editclipping($eventData) {
    global $gXml_def, $gLocale, $customers;

    $innowork_clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);
    $clipping_data = $innowork_clipping->GetItem();

    $headers[0]['label'] = $gLocale->getStr('editclipping.header');

    $gXml_def = '
    <horizgroup>
      <children>
    
        <table>
          <args>
            <headers type="array">'.WuiXml::encode($headers).'</headers>
          </args>
          <children>
    
            <form row="0" col="0"><name>editclipping</name>
              <args>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default'), array('action', 'editclipping', array('id' => $eventData['id']))))).'</action>
              </args>
              <children>
                <grid>
                  <children>
    
                    <label row="0" col="0">
                      <args>
                        <label type="encoded">'.urlencode($gLocale->getStr('name.label')).'</label>
                      </args>
                    </label>
    
                    <string row="0" col="1"><name>name</name>
                      <args>
                        <disp>action</disp>
                        <size>20</size>
                        <value type="encoded">'.urlencode($clipping_data['name']).'</value>
                      </args>
                    </string>
    
                    <label row="1" col="0">
                      <args>
                        <label type="encoded">'.urlencode($gLocale->getStr('description.label')).'</label>
                      </args>
                    </label>
    
                    <text row="1" col="1"><name>description</name>
                      <args>
                        <disp>action</disp>
                        <rows>5</rows>
                        <cols>40</cols>
                        <value type="encoded">'.urlencode($clipping_data['description']).'</value>
                      </args>
                    </text>
    
                  </children>
                </grid>
              </children>
            </form>
    
            <horizgroup row="1" col="0">
              <children>
    
                <button>
                  <args>
                    <themeimage>buttonok</themeimage>
                    <label type="encoded">'.urlencode($gLocale->getStr('edit_clipping.button')).'</label>
                    <formsubmit>editclipping</formsubmit>
                    <frame>false</frame>
                    <horiz>true</horiz>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'default'), array('action', 'editclipping', array('id' => $eventData['id']))))).'</action>
                  </args>
                </button>
    
              </children>
            </horizgroup>
    
          </children>
        </table>
    
      <innoworkitemacl><name>itemacl</name>
        <args>
          <itemtype>clipping</itemtype>
          <itemid>'.$eventData['id'].'</itemid>
          <itemownerid>'.$clipping_data['ownerid'].'</itemownerid>
          <defaultaction type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'editclipping', array('id' => $eventData['id']))))).'</defaultaction>
        </args>
      </innoworkitemacl>
    
    
      </children>
    </horizgroup>';
}

$gMain_disp->addEvent('showclipping', 'main_showclipping');
function main_showclipping($eventData) {
    global $gXml_def, $gLocale, $customers, $gInnowork_core;

    $summaries = $gInnowork_core->GetSummaries();

    $innowork_clipping = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $eventData['id']);

    $clippings = $innowork_clipping->GetItems();

    $gXml_def = '
        <innoworksearch><name>clipping</name>
          <args>
            <searchresult type="array">'.WuiXml::encode($clippings['result']).'</searchresult>
            <summaries type="array">'.WuiXml::encode($summaries).'</summaries>
            <clipping>true</clipping>
            <clippingid>'.$eventData['id'].'</clippingid>
          </args>
        </innoworksearch>';
}

$gMain_disp->Dispatch();

// ----- Rendering -----
//
$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'folder_txt', 'toolbars' => array(new WuiInnomaticToolBar('core', array('toolbars' => $gToolbars, 'toolbar' => 'true')), new WuiInnomaticToolbar('view', array('toolbars' => $gCore_toolbars, 'toolbar' => 'true'))), 'maincontent' => new WuiXml('page', array('definition' => $gXml_def)), 'status' => $gPage_status)));

$gWui->render();

?>
