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

require_once('shared/wui/WuiXml.php');

/*!
 @class WuiInnoworkSummary
 */
class WuiInnoworkSummary extends WuiXml {
    /*! @var mAppSummaries array - Array of the summaries to be showed as "app". */
    var $mAppSummaries = array();
    /*! @var mListSummaries array - Array of the summaries to be showed as "list". */
    var $mListSummaries = array();

    /*!
     @function WuiInnoworkSummary
     */
    function __construct($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);

        if (isset($this->mArgs['appsummaries']) and is_array($this->mArgs['appsummaries']))
            $this->mAppSummaries = $this->mArgs['appsummaries'];
        if (isset($this->mArgs['listsummaries']) and is_array($this->mArgs['listsummaries']))
            $this->mListSummaries = $this->mArgs['listsummaries'];

        $this->_FillDefinition();
    }

    /*!
     @function _FillDefinition
     */
    function _FillDefinition() {
        $result = false;
        require_once('innomatic/locale/LocaleCountry.php');
        $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

        $date_array['hours'] = date('H');
        $date_array['minutes'] = date('i');
        $date_array['seconds'] = date('s');
        $date_array['mon'] = date('m');
        $date_array['mday'] = date('d');
        $date_array['year'] = date('Y');
        $date = $country->FormatShortArrayDate($date_array);

        $this->mDefinition = '
                    <vertgroup>
                      <name>summary</name>
                      <args><align>left</align><valign>top</valign><width>100%</width></args>
                      <children>
                        <label>
                          <args>
                            <bold>true</bold>
                            <label type="encoded">'.WuiXml::cdata(urlencode($date)).'</label>
                          </args>
                        </label>
                        <horizbar/>';

        $start = true;

        if (is_array($this->mAppSummaries) and is_array($this->mListSummaries))
            $this->mDefinition.= '<grid><name>summary</name><children><vertgroup row="0" col="1" halign="" valign="top"><name>vertgroup</name><children>';

        if (is_array($this->mListSummaries)) {
            $this->mDefinition.= '<horizgroup><children><vertgroup><children>';
            $rows = floor((count($this->mListSummaries) + 1) / 2);
            $row = 0;
            require_once('shared/wui/WuiSessionkey.php');

            while (list ($key, $val) = each($this->mListSummaries)) {
				/*
                if (!$start)
                    $this->mDefinition.= '<horizbar><name>hbar</name></horizbar>';
				 */
                $start = FALSE;

                require_once('innomatic/wui/dispatch/WuiEventsCall.php');
                require_once('innomatic/wui/dispatch/WuiEvent.php');
                $itemtype_call = new WuiEventsCall($val['domainpanel']);
                $itemtype_call->addEvent(new WuiEvent('view', $val['adminevent'], ''));

                $innowork_item_sk = new WuiSessionKey('innowork_itemtypesummary_'.$key.'_closed');

                $this->mDefinition.= '    <horizgroup>
                                          <name>itemtypegroup</name>
                                          <args><align>center</align></args>
                                          <children>
                                    
                                            <button>
                                              <name>itemtypeimage</name>
                                              <args><themeimage>'.$val['icon'].'</themeimage><themeimagetype>'.$val['icontype'].'</themeimagetype><action>'.$itemtype_call->GetEventsCallString().'</action></args>
                                            </button>
                                    
                                            <vertgroup>
                                              <name>itemtypevgroup</name>
                                              <children>
                                    
                                                <horizgroup>
                                                  <args>
                                                    <align>middle</align>
                                                  </args>
                                                  <children>
                                    
                                                    <button>
                                                      <args>
                                                        <image>'.WuiXml::cdata($innowork_item_sk->mValue != '1' ? $this->mThemeHandler->mStyle['arrowdown'] : $this->mThemeHandler->mStyle['arrowright']).'</image>
                                                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('', array(array('view', 'default'), array('wui', $innowork_item_sk->mValue != '1' ? 'innoworkitemclose' : 'innoworkitemopen', array('innoworkitemtype' => $key))))).'</action>
                                                      </args>
                                                    </button>
                                    
                                                    <link><name>itemtypelink</name><args><label type="encoded">'.WuiXml::cdata(urlencode('<strong>'.$val['label']).'</strong>').'</label><link>'.$itemtype_call->GetEventsCallString().'</link></args></link>
                                    
                                                  </children>
                                                </horizgroup>';

                if ($innowork_item_sk->mValue != '1')
                    $this->mDefinition.= $val['widget'];

                $this->mDefinition.= '
                                              </children>
                                            </vertgroup>
                                    
                                          </children>
                                        </horizgroup>';

                $row ++;
                if ($row == $rows) {
                    $this->mDefinition.= '</children></vertgroup><vertgroup><children>';
                    $start = true;
                }
            }

            $this->mDefinition.= '</children></vertgroup></children></horizgroup>';
        }

        $start = true;

        if (is_array($this->mAppSummaries) and is_array($this->mListSummaries))
            $this->mDefinition.= '</children></vertgroup><vertgroup row="0" col="2" halign="" valign="top"><name>vertgroup</name><children></children></vertgroup><vertgroup row="0" col="2" halign="" valign="top"><name>vertgroup</name><children>';

        if (is_array($this->mAppSummaries)) {
            while (list ($key, $val) = each($this->mAppSummaries)) {
				/*
                if (!$start)
                    $this->mDefinition.= '<horizbar><name>hbar</name></horizbar>';
				 */
                $start = FALSE;

                require_once('innomatic/wui/dispatch/WuiEventsCall.php');
                require_once('innomatic/wui/dispatch/WuiEvent.php');
                $itemtype_call = new WuiEventsCall($val['domainpanel']);
                $itemtype_call->addEvent(new WuiEvent('view', $val['adminevent'], ''));

                $this->mDefinition.= '
                                        <horizgroup>
                                          <name>itemtypegroup</name>
                                          <args><align>center</align></args>
                                          <children>
                                    
                                            <button>
                                              <name>itemtypeimage</name>
                                              <args><themeimage>'.$val['icon'].'</themeimage><themeimagetype>'.$val['icontype'].'</themeimagetype><action>'.$itemtype_call->GetEventsCallString().'</action></args>
                                            </button>
                                    
                                            <vertgroup>
                                              <name>itemtypevgroup</name>
                                              <children>
                                    
                                                <link><name>itemtypelink</name><args><label type="encoded">'.WuiXml::cdata('<strong>'.urlencode($val['label']).'</strong>').'</label><link>'.$itemtype_call->GetEventsCallString().'</link></args></link>';

                $this->mDefinition.= $val['widget'];

                $this->mDefinition.= '
                                    
                                              </children>
                                            </vertgroup>
                                    
                                          </children>
                                        </horizgroup>
                                    ';
            }
        }

        if (is_array($this->mAppSummaries) and is_array($this->mListSummaries))
            $this->mDefinition.= '</children></vertgroup></children></grid>';

        $this->mDefinition.= '
                    
                      </children>
                    </vertgroup>
                    ';

        return $result;
    }
}