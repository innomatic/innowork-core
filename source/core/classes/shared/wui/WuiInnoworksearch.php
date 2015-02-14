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
 * The Initial Developer of the Original Code is Innomatic Company.
 * Portions created by the Initial Developer are Copyright (C) 2002-2009
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Alex Pagnoni <alex.pagnoni@innomatic.io>
 *
 * ***** END LICENSE BLOCK ***** */

require_once('shared/wui/WuiXml.php');

class WuiInnoworksearch extends WuiXml {
    var $mSummaries = array();
    var $mSearchResult = array();
    var $mTrashcan = 'false';
    var $mClipping = 'false';
    var $mClippingId = 0;

    /*!
     @function WuiInnoworkSearch
     */
    function __construct($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);

        if (isset($this->mArgs['summaries']) and is_array($this->mArgs['summaries'])) {
            $this->mSummaries = $this->mArgs['summaries'];
        }

        if (isset($this->mArgs['searchresult']) and is_array($this->mArgs['searchresult'])) {
            $this->mSearchResult = $this->mArgs['searchresult'];
        }

        if (isset($this->mArgs['trashcan']) and $this->mArgs['trashcan'] == 'true') {
            $this->mTrashcan = 'true';
        }

        if (isset($this->mArgs['clipping']) and $this->mArgs['clipping'] == 'true') {
            $this->mClipping = 'true';
        }

        if (isset($this->mArgs['clippingid'])) {
            $this->mClippingId = (int) $this->mArgs['clippingid'];
        }

        $this->_FillDefinition();
    }

    /*!
     @function _FillDefinition
     */
    function _FillDefinition() {
        $result = true;

        require_once('innomatic/locale/LocaleCatalog.php');
        require_once('innomatic/locale/LocaleCountry.php');
        $row = 0;
        $this->mDefinition = '
                    <vertgroup><name>searchresult</name><children>';

        while (list($type, $results) = each($this->mSearchResult)) {
            if (count($results)) {
                $tmp_locale = new LocaleCatalog($this->mSummaries[$type]['catalog'], \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

                /*
                                $itemtype_call = new WuiEventsCall( $val['domainpanel'] );
                                $itemtype_call->addEvent( new WuiEvent( 'view', 'default', '' ) );
                        <button>
                          <name>itemtypeimage</name>
                          <args><themeimage>'.$val['icon'].'</themeimage><themeimagetype>'.$val['icontype'].'</themeimagetype><action>'.$itemtype_call->getEventsCallString().'</action></args>
                        </button>
                */

                $this->mDefinition.= '<button><name>type</name>
                                      <args>
                                        <themeimage>'.$this->mSummaries[$type]['icon'].'</themeimage>
                                        <themeimagetype>'.$this->mSummaries[$type]['icontype'].'</themeimagetype>
                                        <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString($this->mSummaries[$type]['domainpanel'], array(array('view', 'default', '')))).'</action>
                                        <label type="encoded">'.WuiXml::cdata(urlencode($this->mSummaries[$type]['label'])).'</label>
                                        <horiz>true</horiz>
                                      </args>
                                    </button>';

                $headers        = array();
                $header_count   = 1;
                $locale_country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());

                while (list(, $keyname) = each($this->mSummaries[$type]['viewablesearchresultkeys'])) {
                    $headers[$header_count ++]['label'] = $tmp_locale->getStr($keyname);
                }

                $this->mDefinition.= '<table><name>searchresult</name><args><headers type="array">'.WuiXml::encode($headers).'</headers></args><children>';

                $row = 0;
                //$col = 1;

                while (list ($id, $result) = each($results)) {
                    $col = 1;

                    switch ($result['_acl']['type']) {
                        case InnoworkAcl::TYPE_PRIVATE :
                            $image = 'user';
                            break;

                        case InnoworkAcl::TYPE_PUBLIC :
                        case InnoworkAcl::TYPE_ACL :
                            $image = 'useradd';
                            break;
                    }

                    $this->mDefinition.= '<button row="'.$row.'" col="0"><name>acl</name>
                                              <args>
                                                <themeimage>'.$image.'</themeimage>
                                                <themeimagetype>mini</themeimagetype>
                                                <compact>true</compact>
                                                <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString($this->mSummaries[$type]['domainpanel'], array(array($this->mSummaries[$type]['showdispatcher'], $this->mSummaries[$type]['showevent'], array('id' => $result['id']))))).'</action>
                                              </args>
                                            </button>';

                    foreach ($this->mSummaries[$type]['viewablesearchresultkeys'] as $key) {
                        $value = $result[$key];

                        if ($col == 1) {
                            $this->mDefinition.= '<link row="'.$row.'" col="'.$col.'"><name>key</name>
                                                              <args>
                                                                <compact>true</compact>
                                                                <link>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString($this->mSummaries[$type]['domainpanel'], array(array($this->mSummaries[$type]['showdispatcher'], $this->mSummaries[$type]['showevent'], array('id' => $result['id']))))).'</link>
                                                                <label type="encoded">'.WuiXml::cdata(strlen($value) > 35 ? urlencode(substr($value, 0, 32)).'...' : urlencode($value)).'</label>
                                                                <title type="encoded">'.WuiXml::cdata(urlencode(str_replace('"', '', $value))).'</title>
                                                              </args>
                                                            </link>';
                        }
                        else {
                            $key_type = explode(':', $this->mSummaries[$type]['keys'][$key]);
                            switch ($key_type[0]) {
                                case 'text' :
                                    $value = strlen($value) > 35 ? substr($value, 0, 32).'...' : $value;
                                    break;

                                case 'timestamp' :
                                    $value = $locale_country->FormatShortArrayDate(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetDateArrayFromTimestamp($value));

                                    break;

                                case 'boolean' :
                                    if ($value == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue) {
                                        $value = 'true';
                                    } else {
                                        $value = 'false';
                                    }
                                    break;

                                case 'table' :
                                    if (strlen($value)) {
                                        $tmp_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT '.$key_type[2].' FROM '.$key_type[1].' WHERE id='.$value);
                                        if ($tmp_query->getNumberRows()) {
                                            $value = $tmp_query->getFields($key_type[2]);
                                        } else {
                                            $value = '';
                                        }

                                        $tmp_query->Free();
                                    } else {
                                        $value = '';
                                    }
                                    break;

                                case 'userid' :
                                    if (strlen($value)) {
                                        $tmp_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT id,username,fname,lname FROM domain_users WHERE id='.$value);
                                        if ($tmp_query->getNumberRows()) {
                                            $value = $tmp_query->getFields('fname').' '.$tmp_query->getFields('lname');
                                        } else {
                                            $value = '';
                                        }

                                        $tmp_query->Free();
                                    } else {
                                        $value = '';
                                    }
                                    break;

                                default :
                                    break;
                            }

                            $this->mDefinition.= '<label row="'.$row.'" col="'.$col.'"><name>key</name>
                                                              <args>
                                                                <compact>true</compact>
                            									<nowrap>false</nowrap>
                                                                <label type="encoded">'.WuiXml::cdata(urlencode($value)).'</label>
                                                              </args>
                                                            </label>';
                        }
                        $col ++;
                    }

                    if ($this->mTrashcan == 'true') {
                        $locale = new LocaleCatalog('innowork-core::misc', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

                        $this->mDefinition.= '<button row="'.$row.'" col="'.$col.'"><name>restore</name>
                                                      <args>
                                                        <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('restore.button'))).'</label>
                                                        <horiz>true</horiz>
                                                        <frame>false</frame>
                                                        <themeimagetype>mini</themeimagetype>
                                                        <themeimage>undo</themeimage>
                                                                 <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'trashcan'), array('action', 'restore_item', array('itemtype' => $type, 'itemid' => $result['id']))))).'</action>
                                                      </args>
                                                    </button>';
                    }

                    if ($this->mClipping == 'true' and $this->mClippingId) {
                        $locale = new LocaleCatalog('innowork-core::misc', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());

                        $this->mDefinition.= '<button row="'.$row.'" col="'.$col.'"><name>remove</name>
                                                      <args>
                                                        <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('remove_from_clipping.button'))).'</label>
                                                        <horiz>true</horiz>
                                                        <frame>false</frame>
                                                        <themeimagetype>mini</themeimagetype>
                                                        <themeimage>editdelete</themeimage>
                                                                 <action>'.WuiXml::cdata(WuiEventsCall::buildEventsCallString('innoworkclippings', array(array('view', 'showclipping', array('id' => $this->mClippingId)), array('action', 'remove_item', array('id' => $this->mClippingId, 'itemtype' => $type, 'itemid' => $result['id']))))).'</action>
                                                      </args>
                                                    </button>';
                    }

                    $row ++;
                }

                $this->mDefinition.= '</children></table>';
            }
        }

        $this->mDefinition.= '</children></vertgroup>';

        return $result;
    }
}
