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

global $gLocale, $gPage_status, $innowork_core;
global $gPage_content, $innowork_core, $gLocale, $gWui, $gPage_status, $gPage_title;

require_once('innomatic/locale/LocaleCatalog.php');
$gLocale = new LocaleCatalog('innowork-core::core', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

require_once('innowork/core/InnoworkCore.php');
$innowork_core = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

require_once('innomatic/wui/Wui.php');
$gWui = Wui::instance('wui');
$gWui->loadWidget( 'xml' );
$gWui->loadWidget( 'innomatictoolbar' );
$gWui->loadWidget( 'innomaticpage' );
$gPage_content = false;
$gPage_status = '';
$gPage_title = $gLocale->getStr('innoworksummary.title');

//$summaries['directorycompany']['widget'] = new WuiLabel( 'mycompany', array( 'label' => 'My company' ) );

// Action dispatcher

require_once('innomatic/wui/dispatch/WuiDispatcher.php');
$gAction_disp = new WuiDispatcher('action');

$gAction_disp->addEvent('empty_trashcan', 'action_empty_trashcan');
function action_empty_trashcan($eventData) {
	global $gLocale, $gPage_status, $innowork_core;
	$innowork_core->EmptyTrashcan();
	$gPage_status = $gLocale->getStr('trashcan_cleaned.status');
}

$gAction_disp->addEvent('restore_item', 'action_restore_item');
function action_restore_item($eventData) {
	global $gLocale, $gPage_status, $innowork_core;

	$summaries = $innowork_core->getSummaries();

	$class_name = $summaries[$eventData['itemtype']]['classname'];
	if (!class_exists($class_name)) {
		return false;
	}
	$tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);

	$tmp_class->Restore();
	$gPage_status = $gLocale->getStr('item_restored.status');
}

$gAction_disp->Dispatch();

// Main dispatcher

$main_disp = new WuiDispatcher('view');

$main_disp->addEvent('default', 'main_default');
function main_default($eventData) {
	global $gPage_content, $innowork_core, $gWui;
	$gWui->LoadWidget('innoworksummary');
	$gPage_content = new WuiInnoworkSummary('innoworksummary', array('appsummaries' => $innowork_core->GetSummaries('app', true), 'listsummaries' => $innowork_core->GetSummaries('list', true)));
}

$main_disp->addEvent('search', 'main_search');
function main_search($eventData) {
	global $gPage_content, $innowork_core, $gWui, $gPage_status, $gPage_title, $gLocale;
	require_once('innowork/core/InnoworkItem.php');
	$summaries = $innowork_core->GetSummaries();
	$types[''] = $gLocale->getStr('alltypes.label');

	while (list ($key, $val) = each($summaries)) {
		if ($val['searchable'])
		$types[$key] = $val['label'];
	}
	reset($summaries);

	if (!isset($eventData['restrictto'])) {
		$eventData['restrictto'] = InnoworkItem::SEARCH_RESTRICT_NONE;
	}

	require_once('innomatic/wui/dispatch/WuiEventsCall.php');
	$xml_def = '
    <vertgroup><name>search</name><children>
      <form><name>search</name>
        <args>
          <method>post</method>
          <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'search')))).'</action>
        </args>
        <children>
    
          <grid>
            <children>
    
              <label row="0" col="0"><name>searchlabel</name><args><label type="encoded">'.urlencode($gLocale->getStr('search.label')).'</label></args></label>
          <horizgroup row="0" col="1"><args><align>center</align></args><children>
            <string><name>searchkey</name>
              <args>
                <disp>view</disp>
                <size>20</size>
                <required>true</required>
                <checkmessage type="encoded">'.urlencode($gLocale->getStr('searchkeys_required.confirm')).'</checkmessage>
                <value type="encoded">'.urlencode(isset($eventData['searchkey']) ? $eventData['searchkey'] : '').'</value>
              </args>
            </string>
            <combobox><name>type</name>
              <args>
                <disp>view</disp>
                <elements type="array">'.WuiXml::encode($types).'</elements>
                <default>'. (isset($eventData['type']) ? $eventData['type'] : '').'</default>
              </args>
            </combobox>
            <button><name>submit</name>
              <args>
                <themeimage>zoom</themeimage>
                <horiz>true</horiz>
                <label type="encoded">'.urlencode($gLocale->getStr('search.submit')).'</label>
                <formsubmit>search</formsubmit>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'search')))).'</action>
              </args>
            </button>
          </children></horizgroup>
    
<!--
              <label row="1" col="0" halign="" valign="top"><args><label type="encoded">'.urlencode($gLocale->getStr('restrict_to.label')).'</label></args></label>
    
              <vertgroup row="1" col="1">
                <children>
    
                  <radio><name>restrictto</name>
                    <args>
                      <disp>view</disp>
                      <label type="encoded">'.urlencode($gLocale->getStr('restrict_to_none.label')).'</label>
                      <value>'.InnoworkItem::SEARCH_RESTRICT_NONE.'</value>
                      <checked>'. ($eventData['restrictto'] == InnoworkItem::SEARCH_RESTRICT_NONE ? 'true' : 'false').'</checked>
                    </args>
                  </radio>
    
                  <radio><name>restrictto</name>
                    <args>
                      <disp>view</disp>
                      <label type="encoded">'.urlencode($gLocale->getStr('restrict_to_owner.label')).'</label>
                      <value>'.InnoworkItem::SEARCH_RESTRICT_TO_OWNER.'</value>
                      <checked>'. ($eventData['restrictto'] == InnoworkItem::SEARCH_RESTRICT_TO_OWNER ? 'true' : 'false').'</checked>
                    </args>
                  </radio>
    
                  <radio><name>restrictto</name>
                    <args>
                      <disp>view</disp>
                      <label type="encoded">'.urlencode($gLocale->getStr('restrict_to_responsible.label')).'</label>
                      <value>'.InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE.'</value>
                      <checked>'. ($eventData['restrictto'] == InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE ? 'true' : 'false').'</checked>
                    </args>
                  </radio>
    
                  <radio><name>restrictto</name>
                    <args>
                      <disp>view</disp>
                      <label type="encoded">'.urlencode($gLocale->getStr('restrict_to_participant.label')).'</label>
                      <value>'.InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT.'</value>
                      <checked>'. ($eventData['restrictto'] == InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT ? 'true' : 'false').'</checked>
                    </args>
                  </radio>
    
                </children>
              </vertgroup>
-->
            </children>
          </grid>
        </children>
      </form>';

	if (isset($eventData['searchkey'])) {
		require_once('innowork/core/InnoworkKnowledgeBase.php');
		 
		$innowork_kb = new InnoworkKnowledgeBase(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
		$global_search = $innowork_kb->GlobalSearch($eventData['searchkey'], $eventData['type'], false, 0, $eventData['restrictto']);

		if ($global_search['founditems']) {
			$xml_def.= '  <horizbar><name>hb</name></horizbar>
              <innoworksearch><name>searchresult</name><args><searchresult type="array">'.WuiXml::encode($global_search['result']).'</searchresult><summaries type="array">'.WuiXml::encode($innowork_core->GetSummaries()).'</summaries></args></innoworksearch>';
		}

		$gPage_status = sprintf($gLocale->getStr('found_items.status'), $global_search['founditems']);
	}

	$xml_def.= '</children></vertgroup>
    ';

	$gPage_content = new WuiXml('page', array('definition' => $xml_def));
	$gPage_title.= ' - '.$gLocale->getStr('globalsearch.title');
}

$main_disp->addEvent('relateditems', 'main_relateditems');
function main_relateditems($eventData) {
	global $gPage_content, $innowork_core, $gWui, $gPage_status, $gPage_title, $gLocale;

	$summaries = $innowork_core->GetSummaries();

	if (isset($summaries[$eventData['itemtype']])) {
		$class_name = $summaries[$eventData['itemtype']]['classname'];
		if (!class_exists($class_name)) {
			return false;
		}
		$tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $eventData['itemid']);

		$global_search = $tmp_class->GetRelatedItems();

		$xml_def = '';

		if ($global_search['founditems']) {
			$xml_def = '
            <vertgroup><name>relateditems</name>
              <children>
                <innoworksearch><name>searchresult</name>
                  <args>
                    <searchresult type="array">'.WuiXml::encode($global_search['result']).'</searchresult>
                    <summaries type="array">'.WuiXml::encode($summaries).'</summaries>
                  </args>
                </innoworksearch>
              </children>
            </vertgroup>';
		}

		$gPage_status = sprintf($gLocale->getStr('found_items.status'), $global_search['founditems']);
		$gPage_content = new WuiXml('page', array('definition' => $xml_def));
	}
	$gPage_title.= ' - '.$gLocale->getStr('relateditems.title');
}

$main_disp->addEvent('trashcan', 'main_trashcan');
function main_trashcan($eventData) {
	global $gPage_content, $innowork_core, $gLocale, $gWui, $gPage_status, $gPage_title;

	$summaries = $innowork_core->GetSummaries();

	require_once('innowork/core/InnoworkKnowledgeBase.php');
	$innowork_kb = new InnoworkKnowledgeBase(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());

	$global_search = $innowork_kb->GlobalSearch('', '', true);

	$xml_def = '';

	/*
	 echo '<pre>';
	 print_r( $global_search );
	 echo '</pre>';
	 */
	if ($global_search['founditems']) {
		require_once('innomatic/wui/dispatch/WuiEventsCall.php');
		$xml_def = '
        <vertgroup><name>trashcan</name>
          <children>
        
            <innoworksearch><name>searchresult</name>
              <args>
                <searchresult type="array">'.WuiXml::encode($global_search['result']).'</searchresult>
                <summaries type="array">'.WuiXml::encode($summaries).'</summaries>
                <trashcan>true</trashcan>
              </args>
            </innoworksearch>
        
            <horizbar/>
        
            <button><name>emptytrashcan</name>
              <args>
                <label type="encoded">'.urlencode($gLocale->getStr('empty_trashcan.button')).'</label>
                <themeimage>buttonok</themeimage>
                <horiz>true</horiz>
                <frame>false</frame>
                <needconfirm>true</needconfirm>
                <confirmmessage type="encoded">'.urlencode($gLocale->getStr('empty_trashcan.confirm')).'</confirmmessage>
                <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'trashcan'), array('action', 'empty_trashcan')))).'</action>
              </args>
            </button>
        
          </children>
        </vertgroup>';
	}

	$gPage_status = sprintf($gLocale->getStr('found_items.status'), $global_search['founditems']);
	$gPage_content = new WuiXml('page', array('definition' => $xml_def));

	$gPage_title.= ' - '.$gLocale->getStr('trashcan.title');
}

$main_disp->addEvent('today_activities', 'main_today_activities');
function main_today_activities($eventData) {
	global $gPage_content, $innowork_core, $gLocale, $gWui, $gPage_status, $gPage_title;

	if (!isset($eventData['date'])) {
		$date = array();
		$date['year'] = date('Y');
		$date['mon'] = date('m');
		$date['mday'] = date('d');
	}
	else {
		require_once('innomatic/locale/LocaleCountry.php');
		$country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

		$date = $country->GetDateArrayFromShortDateStamp($eventData['date']);
	}

	$summaries = $innowork_core->GetSummaries();
	$activities = $innowork_core->GetTodayActivities($date);

	require_once('innomatic/wui/dispatch/WuiEventsCall.php');
	$xml_def = '
    <vertgroup>
      <children>
    
        <form><name>date</name>
          <args>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'today_activities')))).'</action>
          </args>
          <children>
    
            <horizgroup>
              <args>
                <align>middle</align>
              </args>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('day_activities.label')).'</label>
                  </args>
                </label>
    
                <date><name>date</name>
                  <args>
                    <disp>view</disp>
                    <value type="array">'.WuiXml::encode($date).'</value>
                  </args>
                </date>
    
                <button>
                  <args>
                    <horiz>true</horiz>
                    <frame>false</frame>
                    <themeimage>down</themeimage>
                    <label type="encoded">'.urlencode($gLocale->getStr('filter_day_activities.button')).'</label>
                    <formsubmit>date</formsubmit>
            <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'today_activities')))).'</action>
                  </args>
                </button>
    
              </children>
            </horizgroup>
    
          </children>
        </form>';

	if ($activities['founditems']) {
		$xml_def.= '    <horizbar/>
            <innoworksearch><name>searchresult</name>
              <args>
                <searchresult type="array">'.WuiXml::encode($activities['result']).'</searchresult>
                <summaries type="array">'.WuiXml::encode($summaries).'</summaries>
              </args>
            </innoworksearch>';
	}

	$xml_def.= '  </children>
    </vertgroup>';

	$gPage_status = sprintf($gLocale->getStr('found_items.status'), $activities['founditems']);
	$gPage_content = new WuiXml('page', array('definition' => $xml_def));

	$gPage_title.= ' - '.$gLocale->getStr('today_activities.title');
}

$main_disp->addEvent('aboutinnowork', 'main_aboutinnowork');
function main_aboutinnowork($eventData) {
	global $gPage_title, $gPage_content, $gLocale;

	$xml_def = '
    <vertgroup>
      <args>
        <groupalign>center</groupalign>
        <align>center</align>
      </args>
      <children>
    
        <image>
          <args>
            <imageurl type="encoded">'.urlencode(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getBaseUrl(false).'/shared/innowork-core_logo_innowork.png').'</imageurl>
            <width>289</width>
            <height>24</height>
          </args>
        </image>
    
        <link>
          <args>
            <label type="encoded">'.urlencode($gLocale->getStr('innowork_copyright.label')).'</label>
            <link type="encoded">'.urlencode('http://www.innomatica.it/').'</link>
          </args>
        </link>
    
        <link>
          <args>
            <label type="encoded">'.urlencode('http://www.innomatica.it/prodotti/innowork/').'</label>
            <link type="encoded">'.urlencode('http://www.innomatica.it/prodotti/innowork/').'</link>
            <target>_blank</target>
          </args>
        </link>
    
      </children>
    </vertgroup>';

	$gPage_content = new WuiXml('page', array('definition' => $xml_def));

	$gPage_title.= ' - '.$gLocale->getStr('about_innowork.title');
}

$main_disp->addEvent('stats', 'main_stats');
function main_stats($eventData) {
	global $gPage_title, $gPage_content, $gLocale;

	$stats_ok = false;
	require_once('innomatic/locale/LocaleCountry.php');
	$locale_country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

	if (isset($eventData['statsfrom']) and isset($eventData['statsto'])) {
		//$stats_ok = true;

		$from_date = $locale_country->GetDateArrayFromShortDateStamp($eventData['statsfrom']);
		$from_ts = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetTimestampFromDateArray($from_date);
		$from_secs = mktime(0, 0, 0, $from_date['mon'], $from_date['mday'], $from_date['year']);

		$to_date = $locale_country->GetDateArrayFromShortDateStamp($eventData['statsto']);
		$to_date['hours'] = 23;
		$to_date['minutes'] = 59;
		$to_date['seconds'] = 59;
		$to_ts = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetTimestampFromDateArray($to_date);
		$to_secs = mktime(23, 59, 59, $to_date['mon'], $to_date['mday'], $to_date['year']);

		$stats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT * '.'FROM innowork_core_itemslog '.'WHERE eventtime>='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($from_ts).' '.'AND eventtime<='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($to_ts));

		$users_stats_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT username,count(username) AS count '.'FROM innowork_core_itemslog '.'WHERE eventtime>='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($from_ts).' '.'AND eventtime<='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($to_ts).' '.'GROUP BY username');

		$stats_data = $_stats_data = array();

		while (!$stats_query->eof) {
			$tmp_date = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($stats_query->getFields('eventtime'));

			if (!isset($_stats_data[$tmp_date['year'].$tmp_date['mon'].$tmp_date['mday']])) {
				$_stats_data[$tmp_date['year'].$tmp_date['mon'].$tmp_date['mday']]['changes'] = 1;
			}
			else {
				$_stats_data[$tmp_date['year'].$tmp_date['mon'].$tmp_date['mday']]['changes']++;
			}
			$_stats_data[$tmp_date['year'].$tmp_date['mon'].$tmp_date['mday']]['day'] = $tmp_date['mday'];

			$stats_query->MoveNext();
		}

		for ($i = $from_secs; $i <= $to_secs; $i += 60 * 60 * 24) {
			$tmp_date_array = $locale_country->GetDateArrayFromUnixTimestamp($i);

			if (!isset($_stats_data[$tmp_date_array['year'].$tmp_date_array['mon'].$tmp_date_array['mday']])) {
				$_stats_data[$tmp_date_array['year'].$tmp_date_array['mon'].$tmp_date_array['mday']]['changes'] = 0;
				$_stats_data[$tmp_date_array['year'].$tmp_date_array['mon'].$tmp_date_array['mday']]['day'] = $tmp_date_array['mday'];
			}
		}

		ksort($_stats_data);

		$x_array = $y_array = array();
		$cont = 1;

		foreach ($_stats_data as $id => $value) {
			$x_array[] = $cont ++;
			$y_array[] = $value['changes'];

		}
		reset($_stats_data);

		require_once('phplot/PHPlot.php');
		$regression_data = phplot_regression($x_array, $y_array);

		$cont = 0;

		foreach ($_stats_data as $value) {
			$stats_data[] = array($value['day'], $value['changes'], $regression_data[$cont ++][2]);
		}

		$users_stats_data = array();

		while (!$users_stats_query->eof) {
			if (strpos($users_stats_query->getFields('username'), '@')) {
				$username = substr($users_stats_query->getFields('username'), 0, strpos($users_stats_query->getFields('username'), '@'));
			}
			else {
				$username = $users_stats_query->getFields('username');
			}

			$users_stats_data[] = array(ucfirst($username), $users_stats_query->getFields('count'));

			$users_stats_query->MoveNext();
		}

		$stats_ok = true;
	}
	else {
		$from_date = $locale_country->getDateArrayFromSafeTimestamp($locale_country->SafeFormatTimestamp(time() - (60 * 60 * 24 * 30)));

		$to_date = $locale_country->getDateArrayFromSafeTimestamp($locale_country->SafeFormatTimestamp());
	}

	require_once('innomatic/wui/dispatch/WuiEventsCall.php');
	$xml_def = '
    <vertgroup>
      <children>
    
        <form><name>stats</name>
          <args>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'stats')))).'</action>
          </args>
          <children>
    
            <horizgroup>
              <args>
                <align>middle</align>
              </args>
              <children>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('stats_from.label')).'</label>
                  </args>
                </label>
    
                <date><name>statsfrom</name>
                  <args>
                    <disp>view</disp>
                    <value type="array">'.WuiXml::encode($from_date).'</value>
                  </args>
                </date>
    
                <label>
                  <args>
                    <label type="encoded">'.urlencode($gLocale->getStr('stats_to.label')).'</label>
                  </args>
                </label>
    
                <date><name>statsto</name>
                  <args>
                    <disp>view</disp>
                    <value type="array">'.WuiXml::encode($to_date).'</value>
                  </args>
                </date>
    
                <button>
                  <args>
                    <themeimage>buttonok</themeimage>
                    <horiz>true</horiz>
                    <label type="encoded">'.urlencode($gLocale->getStr('get_stats.button')).'</label>
                    <formsubmit>stats</formsubmit>
                    <action type="encoded">'.urlencode(WuiEventsCall::buildEventsCallString('', array(array('view', 'stats')))).'</action>
                  </args>
                </button>
    
              </children>
            </horizgroup>
    
          </children>
        </form>';

	if ($stats_ok) {
		$legend = array($gLocale->getStr('activites_legend.label'), $gLocale->getStr('trend_legend.label'));

		$xml_def.= '<horizbar/>
        
        <phplot>
          <args>
            <data type="array">'.WuiXml::encode($stats_data).'</data>
            <width>600</width>
            <height>350</height>
            <title type="encoded">'.urlencode($gLocale->getStr('statistics.title')).'</title>
            <legend type="array">'.WuiXml::encode($legend).'</legend>
            <pointsize>1</pointsize>
          </args>
        </phplot>
        
        <phplot>
          <args>
            <data type="array">'.WuiXml::encode($users_stats_data).'</data>
            <width>600</width>
            <height>350</height>
            <title type="encoded">'.urlencode($gLocale->getStr('statistics_users.title')).'</title>
            <plottype>bars</plottype>
            <pointsize>1</pointsize>
          </args>
        </phplot>';
	}

	$xml_def.= '  </children>
    </vertgroup>';

	$gPage_content = new WuiXml('page', array('definition' => $xml_def));

	$gPage_title.= ' - '.$gLocale->getStr('statistics.title');
}

$main_disp->Dispatch();

$toolbars = $innowork_core->GetMainToolBar();
$toolbars['help'] = array('help' => array('label' => $gLocale->getStr('help.button'), 'themeimage' => 'info', 'horiz' => 'true', 'action' => WuiEventsCall::buildEventsCallString('', array(array('view', 'help', '')))));

$innomatictoolbars = array(new WuiInnomaticToolBar('view', array('toolbars' => $toolbars, 'toolbar' => 'true')));

// search
// acl
// settings
// relations

$gWui->addChild(new WuiInnomaticPage('page', array('pagetitle' => $gPage_title, 'icon' => 'desktop', 'toolbars' => $innomatictoolbars, 'maincontent' => $gPage_content, 'status' => $gPage_status)));

$gWui->render();

?>
