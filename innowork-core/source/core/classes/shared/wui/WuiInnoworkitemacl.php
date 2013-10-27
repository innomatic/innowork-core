<?php
/*
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
 @class WuiInnoworkItemAcl
 */
class WuiInnoworkItemAcl extends WuiXml {
    var $mItemId;
    var $mItemType;
    var $mItemOwnerId;
    var $mDefaultAction;
    var $mAclType;

    /*!
     @function WuiInnoworkItemAcl
     */
    public function __construct($elemName, $elemArgs = '', $elemTheme = '', $dispEvents = '') {
        parent::__construct($elemName, $elemArgs, $elemTheme, $dispEvents);
        if (isset($this->mArgs['itemtype']))
            $this->mItemType = $this->mArgs['itemtype'];
        if (isset($this->mArgs['itemid']))
            $this->mItemId = $this->mArgs['itemid'];
        if (isset($this->mArgs['defaultaction']))
            $this->mDefaultAction = $this->mArgs['defaultaction'];
        if (isset($this->mArgs['acltype']))
            $this->mAclType = $this->mArgs['acltype'];
        if (isset($this->mArgs['itemownerid']))
            $this->mItemOwnerId = $this->mArgs['itemownerid'];
        $this->fillDefinition();
    }

    /*!
     @function fillDefinition
     */
    private function fillDefinition() {
        $result = false;

        if (strlen($this->mItemType) and $this->mItemId) {
            // Locale
            require_once('innomatic/locale/LocaleCatalog.php');
            $locale = new LocaleCatalog('innowork-core::misc', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());

            // Core
			require_once('innowork/core/InnoworkCore.php');
            $tmp_innoworkcore = InnoworkCore::instance('innoworkcore', InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
            $summaries = $tmp_innoworkcore->GetSummaries();

            // Access list
			require_once('innowork/core/InnoworkAcl.php');
            $acl = new InnoworkAcl(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess(), $this->mItemType, $this->mItemId);
            $tmp_acl_type = $acl->GetType();
            if (strlen($tmp_acl_type)) {
                $this->mAclType = $tmp_acl_type;
            }

            $acls_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('SELECT groupid, userid, rights '.'FROM innowork_core_acls '.'WHERE itemtype='.InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).' '.'AND itemid='.$this->mItemId);
            $owner = '';

            // Log

            if ($summaries[$this->mItemType]['loggable']) {
            	require_once('innowork/core/InnoworkItemLog.php');
                $item_log = new InnoworkItemLog($this->mItemType, $this->mItemId);
            }

            if ($this->mItemOwnerId) {
            	require_once('innomatic/domain/user/User.php');
                $owner_user = new User(InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->domaindata['id'], $this->mItemOwnerId);
                $owner_user_data = $owner_user->GetUserData();
                $owner = (strlen($owner_user_data['fname']) ? $owner_user_data['fname'].' ' : '').$owner_user_data['lname'];
            }

            require_once('shared/wui/WuiSessionkey.php');
            $acl_mode_sk = new WuiSessionKey('innowork_acl_mode', array('sessionobjectnopage' => 'true'));
            if ($acl_mode_sk->mValue == 'advanced') {
                $acl_mode = 'advanced';
            } else {
                $acl_mode = 'simple';
            }

            $row = 0;

            if ($acl_mode == 'advanced') {
                $groups_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('SELECT id,groupname FROM domain_users_groups ORDER BY groupname ');
                $users_query = InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess()->execute('SELECT id,groupid,username,fname,lname FROM domain_users ORDER BY username');
                $limited_acls = array();
                $users = array();

                while (!$users_query->eof) {
                	$fname = $users_query->getFields('fname');
                	$lname = $users_query->getFields('lname');
                	if (strlen($fname) and strlen($lname)) {
                		$tmp_username = $lname.' '.$fname;
                	} else {
						$tmp_username = $users_query->getFields('username');
                	    if (strpos($tmp_username, '@')) {
                        	$tmp_username = substr($tmp_username, 0, strpos($tmp_username, '@'));
                    	}
                	}
                    $users[$users_query->getFields('groupid')][$users_query->getFields('id')] = $tmp_username;
                    $users_query->MoveNext();
                }

                while (!$groups_query->eof) {
                    $group_perms = $acl->checkPermission($groups_query->getFields('id'));
                    switch ($group_perms) {
                        case InnoworkAcl::PERMS_NONE :
                            $limited_acls['g'.$groups_query->getFields('id')] = '[-] '.$groups_query->getFields('groupname');
                            break;
                        case InnoworkAcl::PERMS_SEARCH :
                            $limited_acls['g'.$groups_query->getFields('id')] = '['.$locale->getStr('perm_search_short.label').'] '.$groups_query->getFields('groupname');
                            break;
                        case InnoworkAcl::PERMS_READ :
                            $limited_acls['g'.$groups_query->getFields('id')] = '['.$locale->getStr('perm_read_short.label').'] '.$groups_query->getFields('groupname');
                            break;
                        case InnoworkAcl::PERMS_EDIT :
                            $limited_acls['g'.$groups_query->getFields('id')] = '['.$locale->getStr('perm_edit_short.label').'] '.$groups_query->getFields('groupname');
                            break;
                        case InnoworkAcl::PERMS_DELETE :
                            $limited_acls['g'.$groups_query->getFields('id')] = '['.$locale->getStr('perm_delete_short.label').'] '.$groups_query->getFields('groupname');
                            break;
                        case InnoworkAcl::PERMS_ALL :
                            $limited_acls['g'.$groups_query->getFields('id')] = '[+] '.$groups_query->getFields('groupname');
                            break;
                    }
                    /*
                    $limited_acls['g'.$groups_query->getFields( 'id' )] =
                        (  > InnoworkAcl::PERMS_NONE ?
                        '[+] ' :
                        '[-] ' ).
                        $groups_query->getFields( 'groupname' );
                        */

                    foreach ($users[$groups_query->getFields('id')] as $id => $username) {
                        $user_perms = $acl->checkPermission('', $id);

                        /*
                        $limited_acls['u'.$id] = '-> '.
                            ( $acl->checkPermission( '', $id ) > InnoworkAcl::PERMS_NONE ?
                            '[+] ' :
                            '[-] ' ).
                            $username;
                        */

                        if ($id == $this->mItemOwnerId) {
                            $limited_acls['u'.$id] = '- '.'[+] '.$username;
                        }
                        else {
                            switch ($user_perms) {
                                case InnoworkAcl::PERMS_NONE :
                                    $limited_acls['u'.$id] = '- '.'[-] '.$username;
                                    break;
                                case InnoworkAcl::PERMS_SEARCH :
                                    $limited_acls['u'.$id] = '- '.'['.$locale->getStr('perm_search_short.label').'] '.$username;
                                    break;
                                case InnoworkAcl::PERMS_READ :
                                    $limited_acls['u'.$id] = '- '.'['.$locale->getStr('perm_read_short.label').'] '.$username;
                                    break;
                                case InnoworkAcl::PERMS_EDIT :
                                    $limited_acls['u'.$id] = '- '.'['.$locale->getStr('perm_edit_short.label').'] '.$username;
                                    break;
                                case InnoworkAcl::PERMS_DELETE :
                                    $limited_acls['u'.$id] = '- '.'['.$locale->getStr('perm_delete_short.label').'] '.$username;
                                    break;
                                case InnoworkAcl::PERMS_ALL :
                                    $limited_acls['u'.$id] = '- '.'[+] '.$username;
                                    break;
                            }
                        }
                    }

                    $groups_query->MoveNext();
                }

                $limited_acls['g0'] = '[-] No group';
                foreach ($users[0] as $id => $username) {
                    $limited_acls['u'.$id] = '-> '.($acl->checkPermission('', $id) > InnoworkAcl::PERMS_NONE ? '[+] ' : '[-] ').$username;
                }
            }

            // Clippings

            require_once('innowork/core/clipping/InnoworkClipping.php');
            $innowork_clippings = new InnoworkClipping(InnomaticContainer::instance('innomaticcontainer')->getDataAccess(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDataAccess());
            $clippings_search = $innowork_clippings->search('');
            $clippings = array();
            if ($this->mItemType != 'defaultaclitem' and count($clippings_search)) {
                foreach ($clippings_search as $id => $data) {
                    $clippings[$id] = $data['name'];
                }
            }

            if ($this->mItemOwnerId == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() or User::isAdminUser(InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId()) or $acl->checkPermission('', InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId()) >= InnoworkAcl::PERMS_RESPONSIBLE) {
                $this->mDefinition = '
                                    <empty><name>innoworkitemacl</name>
                                      <children>
                                        <form><name>opts'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                          <args>
                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                          </args>
                                          <children>
                                            <formarg><name>aclmode</name>
                                              <args>
                                                <disp>wui</disp>
                                                <value>'. ($acl_mode == 'advanced' ? 'simple' : 'advanced').'</value>
                                              </args>
                                            </formarg>
                                          </children>
                                        </form>
                                            <table><name>acl</name>
                                              <args>
                                                <headers type="array">'.WuiXml::encode(array('0' => array('label' => $locale->getStr('item_properties.label')))).'</headers>
                                              </args>
                                              <children>
                                        <vertgroup row="'.$row ++.'" col="0" halign="" valign="" nowrap="true">
                                          <children>
                                        <form><name>itemacl'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                          <args>
                                            <method>post</method>
                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                          </args>
                                          <children>

                                            <vertgroup><name>vg</name><children>
                                      <horizgroup>
                                        <args>
                                          <align>middle</align>
                                        </args>
                                        <children>
                                          <button>
                                            <args>
                                              <themeimage>encrypted</themeimage>
                                              <themeimagetype>mini</themeimagetype>
                                              <compact>true</compact>
                                            </args>
                                          </button>
                                              <label><name>convert</name>
                                                <args>
                                                  <bold>true</bold>
                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('access_mode.label'))).'</label>
                                                  <compact>true</compact>
                                                </args>
                                              </label>
                                        </children>
                                      </horizgroup>
                                    ';

                if (strlen($owner))
                    $this->mDefinition.= '          <label><name>owner</name>
                                                        <args>
                                                          <bold>true</bold>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode(sprintf($locale->getStr('owner.label'), $owner))).'</label>
                                                        </args>
                                                      </label>';

                // Only the owner and the root user can change the acl type

                if ($this->mItemOwnerId == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() or User::isAdminUser( InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName(), InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId())) {
                    $this->mDefinition.= '          <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                      <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>
                                                      <radio><name>acltype</name>
                                                       <args>
                                                          <disp>wui</disp>
                                                          <value>'.InnoworkAcl::TYPE_PRIVATE.'</value>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_private.label'))).'</label>
                                                          <checked>'. ($this->mAclType == InnoworkAcl::TYPE_PRIVATE ? 'true' : 'false').'</checked>
                                                        </args>
                                                      </radio>
                                                      <radio><name>acltype</name>
                                                        <args>
                                                          <disp>wui</disp>
                                                          <value>'.InnoworkAcl::TYPE_PUBLIC.'</value>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_public.label'))).'</label>
                                                          <checked>'. ($this->mAclType == InnoworkAcl::TYPE_PUBLIC ? 'true' : 'false').'</checked>
                                                        </args>
                                                      </radio>
                                                      <radio><name>acltype</name>
                                                        <args>
                                                          <disp>wui</disp>
                                                          <value>'.InnoworkAcl::TYPE_ACL.'</value>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_acl.label'))).'</label>
                                                          <checked>'. ($this->mAclType == InnoworkAcl::TYPE_ACL ? 'true' : 'false').'</checked>
                                                        </args>
                                                      </radio>';
                }
                else {
                    $this->mDefinition.= '          <label><name>acltype</name>
                                                       <args>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_private.label'))).'</label>
                                                          <bold>'. ($this->mAclType == InnoworkAcl::TYPE_PRIVATE ? 'true' : 'false').'</bold>
                                                        </args>
                                                      </label>
                                                      <label><name>acltype</name>
                                                        <args>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_public.label'))).'</label>
                                                          <bold>'. ($this->mAclType == InnoworkAcl::TYPE_PUBLIC ? 'true' : 'false').'</bold>
                                                        </args>
                                                      </label>
                                                      <label><name>acltype</name>
                                                        <args>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_acl.label'))).'</label>
                                                          <bold>'. ($this->mAclType == InnoworkAcl::TYPE_ACL ? 'true' : 'false').'</bold>
                                                        </args>
                                                      </label>';
                }

                $this->mDefinition.= '        </children></vertgroup>
                                                  </children>
                                        </form>
                                    <horizgroup>
                                      <children>';
                if ($this->mItemOwnerId == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserId() or InnomaticContainer::instance('innomaticcontainer')->getCurrentDomain()->getDomainId() == InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getUserName()) {
                    $this->mDefinition.= '    <button><name>setactl</name>
                                                  <args>
                                                    <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                    <formsubmit>itemacl'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                    <horiz>true</horiz>
                                                    <frame>false</frame>
                                                    <themeimage>buttonok</themeimage>
                                                    <themeimagetype>mini</themeimagetype>
                                                    <compact>true</compact>
                                                    <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('apply.submit'))).'</label>
                                                  </args>
                                                </button>';
                }

                $this->mDefinition.= '    <button><name>setopts</name>
                                          <args>
                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                            <formsubmit>opts'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                            <horiz>true</horiz>
                                            <frame>false</frame>
                                            <themeimage>'. ($acl_mode == 'advanced' ? 'forward' : 'down').'</themeimage>
                                            <themeimagetype>mini</themeimagetype>
                                            <compact>true</compact>
                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr($acl_mode == 'advanced' ? 'acl_simple.submit' : 'acl_advanced.submit'))).'</label>
                                          </args>
                                        </button>
                                      </children>
                                    </horizgroup>
                                      </children>
                                    </vertgroup>';

                if ($acl_mode == 'advanced') {
                    if ($this->mAclType == InnoworkAcl::TYPE_ACL)
                        $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                      <children>
                                                    <form><name>limitedacl'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                      <args>
                                                        <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                      </args>
                                                      <children>

                                                      <horizgroup>
                                                        <args>
                                                          <align>top</align>
                                                        </args>
                                                        <children>
                                                        <listbox><name>limitedacl</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <elements type="array">'.WuiXml::encode($limited_acls).'</elements>
                                                            <multiselect>true</multiselect>
                                                            <size>10</size>
                                                          </args>
                                                        </listbox>

                                                        <vertgroup>
                                                          <args>
                                                            <align>left</align>
                                                          </args>
                                                          <children>
                                                        <radio><name>aclperms</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('perm_all.label'))).'</label>
                                                            <value>'.InnoworkAcl::PERMS_ALL.'</value>
                                                            <checked>true</checked>
                                                          </args>
                                                        </radio>
                                                        <radio><name>aclperms</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('perm_delete.label'))).'</label>
                                                            <value>'.InnoworkAcl::PERMS_DELETE.'</value>
                                                          </args>
                                                        </radio>
                                                        <radio><name>aclperms</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('perm_edit.label'))).'</label>
                                                            <value>'.InnoworkAcl::PERMS_EDIT.'</value>
                                                          </args>
                                                        </radio>
                                                        <radio><name>aclperms</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('perm_read.label'))).'</label>
                                                            <value>'.InnoworkAcl::PERMS_READ.'</value>
                                                          </args>
                                                        </radio>
                                                        <radio><name>aclperms</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('perm_search.label'))).'</label>
                                                            <value>'.InnoworkAcl::PERMS_SEARCH.'</value>
                                                          </args>
                                                        </radio>

                                                            </children>
                                                          </vertgroup>
                                                        </children>
                                                      </horizgroup>

                                                              <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                              <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                      </children>
                                                    </form>

                                                    <horizgroup>
                                                      <children>
                                                        <button><name>add</name>
                                                          <args>
                                                            <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkacladd').'</action>
                                                            <formsubmit>limitedacl'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                            <horiz>true</horiz>
                                                            <frame>false</frame>
                                                            <themeimage>buttonok</themeimage>
                                                            <themeimagetype>mini</themeimagetype>
                                                            <compact>true</compact>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('add_acl.submit'))).'</label>
                                                          </args>
                                                        </button>
                                                        <button><name>remove</name>
                                                          <args>
                                                            <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkaclremove').'</action>
                                                            <formsubmit>limitedacl'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                            <horiz>true</horiz>
                                                            <frame>false</frame>
                                                            <themeimage>button_cancel</themeimage>
                                                            <themeimagetype>mini</themeimagetype>
                                                            <compact>true</compact>
                                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('remove_acl.submit'))).'</label>
                                                          </args>
                                                        </button>
                                                      </children>
                                                    </horizgroup>
                                                      </children>
                                                    </vertgroup>';

                    /*
                                    $this->mDefinition .=
                    '<form row="'.$row++.'" col="0" halign="" valign="" nowrap="true"><name>responsibles'.md5( $this->mItemType.'-'.$this->mItemId ).'</name>
                      <args>
                      </args>
                      <children>

                        <listbox><name>responsibles</name>
                          <args>
                            <disp>wui</disp>
                            <size>2</size>
                          </args>
                        </listbox>

                      </children>
                    </form>

                    <form row="'.$row++.'" col="0" halign="" valign="" nowrap="true"><name>participants'.md5( $this->mItemType.'-'.$this->mItemId ).'</name>
                      <args>
                      </args>
                      <children>

                        <listbox><name>participants</name>
                          <args>
                            <disp>wui</disp>
                            <size>2</size>
                          </args>
                        </listbox>

                      </children>
                    </form>';
                    */

                    if ($summaries[$this->mItemType]['loggable'])
                        $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                      <children>
                                                      <horizgroup>
                                                        <args>
                                                          <align>middle</align>
                                                        </args>
                                                        <children>
                                                          <button>
                                                            <args>
                                                              <themeimage>history</themeimage>
                                                              <themeimagetype>mini</themeimagetype>
                                                              <compact>true</compact>
                                                            </args>
                                                          </button>
                                                              <label><name>itemlog</name>
                                                                <args>
                                                                  <bold>true</bold>
                                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('history.label'))).'</label>
                                                                  <compact>true</compact>
                                                                </args>
                                                              </label>
                                                        </children>
                                                      </horizgroup>
                                                        <listbox><name>itemlog</name>
                                                          <args>
                                                            <readonly>true</readonly>
                                                            <elements type="array">'.WuiXml::encode(array_reverse($item_log->GetLog())).'</elements>
                                                            <size>3</size>
                                                          </args>
                                                        </listbox>
                                                      </children>
                                                    </vertgroup>';

                    if ($summaries[$this->mItemType]['convertible']) {
                        $convert_types = array();
                        require_once('innomatic/locale/LocaleCatalog.php');

                        foreach ($summaries as $type => $item) {
                            if ($item['convertible'] and $type != $this->mItemType) {
                                $tmp_locale = new LocaleCatalog($item['catalog'], InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());
                                $convert_types[$type] = $tmp_locale->getStr($type);
                                unset($tmp_locale);
                            }
                        }

                        if (count($convert_types))
                            $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                              <children>
                                                              <horizgroup>
                                                                <args>
                                                                  <align>middle</align>
                                                                </args>
                                                                <children>
                                                                  <button>
                                                                    <args>
                                                                      <themeimage>fileshare</themeimage>
                                                                      <themeimagetype>mini</themeimagetype>
                                                                      <compact>true</compact>
                                                                    </args>
                                                                  </button>
                                                                      <label><name>convert</name>
                                                                        <args>
                                                                          <bold>true</bold>
                                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('convert.label'))).'</label>
                                                                          <compact>true</compact>
                                                                        </args>
                                                                      </label>
                                                                </children>
                                                              </horizgroup>

                                                                <form><name>convert'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                                  <args>
                                                                    <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                                  </args>
                                                                  <children>

                                                                <horizgroup>
                                                                  <args>
                                                                    <align>middle</align>
                                                                  </args>
                                                                  <children>

                                                                    <combobox><name>type</name>
                                                                      <args>
                                                                        <disp>wui</disp>
                                                                        <elements type="array">'.WuiXml::encode($convert_types).'</elements>
                                                                      </args>
                                                                    </combobox>

                                                                    <button><name>convert</name>
                                                                      <args>
                                                                        <horiz>true</horiz>
                                                                        <frame>false</frame>
                                                                        <themeimage>filenew</themeimage>
                                                                        <themeimagetype>mini</themeimagetype>
                                                                        <compact>true</compact>
                                                                        <formsubmit>convert'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                                        <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkconvert').'</action>
                                                                      </args>
                                                                    </button>

                                                                      <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                                      <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                                  </children>
                                                                </horizgroup>

                                                                  </children>
                                                                </form>
                                                              </children>
                                                            </vertgroup>';
                    }

                    // Clippings

                    if (count($clippings)) {
                        $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                      <children>
                                                      <horizgroup>
                                                        <args>
                                                          <align>middle</align>
                                                        </args>
                                                        <children>
                                                          <button>
                                                            <args>
                                                              <themeimage>doc</themeimage>
                                                              <themeimagetype>mini</themeimagetype>
                                                              <compact>true</compact>
                                                            </args>
                                                          </button>
                                                              <label><name>clipping</name>
                                                                <args>
                                                                  <bold>true</bold>
                                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('clipping.label'))).'</label>
                                                                  <compact>true</compact>
                                                                </args>
                                                              </label>
                                                        </children>
                                                      </horizgroup>

                                                        <form><name>clipping'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                          <args>
                                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                          </args>
                                                          <children>

                                                        <horizgroup>
                                                          <args>
                                                            <align>middle</align>
                                                          </args>
                                                          <children>

                                                            <combobox><name>clippingid</name>
                                                              <args>
                                                                <disp>wui</disp>
                                                                <elements type="array">'.WuiXml::encode($clippings).'</elements>
                                                              </args>
                                                            </combobox>

                                                            <button><name>clipping</name>
                                                              <args>
                                                                <horiz>true</horiz>
                                                                <frame>false</frame>
                                                                <themeimage>forward</themeimage>
                                                                <themeimagetype>mini</themeimagetype>
                                                                <compact>true</compact>
                                                                <formsubmit>clipping'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                                <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkaddtoclipping').'</action>
                                                              </args>
                                                            </button>

                                                              <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                              <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                          </children>
                                                        </horizgroup>

                                                          </children>
                                                        </form>
                                                      </children>
                                                    </vertgroup>';
                    }

                }

                if (isset($GLOBALS['innoworkcore']['itemacl'][$this->mItemType][$this->mItemId])) {
                    $this->mDefinition.= '<label row="'.$row ++.'" col="0">
                                              <args>
                                                <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_changed.label'))).'</label>
                                                <bold>true</bold>
                                              </args>
                                            </label>';
                }

                $this->mDefinition.= '          </children>
                                            </table>

                                      </children>
                                    </empty>';
            }
            else {
                $this->mDefinition = '
                                    <empty><name>innoworkitemacl</name>
                                      <children>
                                        <form><name>opts'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                          <args>
                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                          </args>
                                          <children>
                                            <formarg><name>aclmode</name>
                                              <args>
                                                <disp>wui</disp>
                                                <value>'. ($acl_mode == 'advanced' ? 'simple' : 'advanced').'</value>
                                              </args>
                                            </formarg>
                                          </children>
                                        </form>

                                            <table><name>acl</name>
                                              <args>
                                                <headers type="array">'.WuiXml::encode(array('0' => array('label' => $locale->getStr('item_properties.label')))).'</headers>
                                              </args>
                                              <children>
                                            <vertgroup row="'.$row ++.'" col="0" halign="" valign="" nowrap="true"><name>vg</name><children>
                                            <horizgroup>
                                        <args>
                                          <align>middle</align>
                                        </args>
                                        <children>
                                          <button>
                                            <args>
                                              <themeimage>encrypted</themeimage>
                                              <themeimagetype>mini</themeimagetype>
                                              <compact>true</compact>
                                            </args>
                                          </button>
                                              <label><name>convert</name>
                                                <args>
                                                  <bold>true</bold>
                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('access_mode.label'))).'</label>
                                                  <compact>true</compact>
                                                </args>
                                              </label>
                                        </children>
                                      </horizgroup>';

                if (strlen($owner))
                    $this->mDefinition.= '          <label><name>owner</name>
                                                        <args>
                                                          <bold>true</bold>
                                                          <label type="encoded">'.WuiXml::cdata(urlencode(sprintf($locale->getStr('owner.label'), $owner))).'</label>
                                                        </args>
                                                      </label>';

                $this->mDefinition.= '          <label><name>acltype</name>
                                               <args>
                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_private.label'))).'</label>
                                                  <bold>'. ($this->mAclType == InnoworkAcl::TYPE_PRIVATE ? 'true' : 'false').'</bold>
                                                </args>
                                              </label>
                                              <label><name>acltype</name>
                                                <args>
                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_public.label'))).'</label>
                                                  <bold>'. ($this->mAclType == InnoworkAcl::TYPE_PUBLIC ? 'true' : 'false').'</bold>
                                                </args>
                                              </label>
                                              <label><name>acltype</name>
                                                <args>
                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('acl_type_acl.label'))).'</label>
                                                  <bold>'. ($this->mAclType == InnoworkAcl::TYPE_ACL ? 'true' : 'false').'</bold>
                                                </args>
                                              </label>

                                    <horizgroup>
                                      <children>
                                        <button><name>setopts</name>
                                          <args>
                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                            <formsubmit>opts'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                            <horiz>true</horiz>
                                            <frame>false</frame>
                                            <themeimage>'. ($acl_mode == 'advanced' ? 'forward' : 'down').'</themeimage>
                                            <themeimagetype>mini</themeimagetype>
                                            <compact>true</compact>
                                            <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr($acl_mode == 'advanced' ? 'acl_simple.submit' : 'acl_advanced.submit'))).'</label>
                                          </args>
                                        </button>
                                      </children>
                                    </horizgroup>
                                      </children>
                                    </vertgroup>';

                if ($acl_mode == 'advanced') {
                    if ($this->mAclType == InnoworkAcl::TYPE_ACL)
                        $this->mDefinition.= '<form row="'.$row ++.'" col="0"><name>limitedacl'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                      <args>
                                                        <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                      </args>
                                                      <children>

                                                        <listbox><name>limitedacl</name>
                                                          <args>
                                                            <disp>wui</disp>
                                                            <elements type="array">'.WuiXml::encode($limited_acls).'</elements>
                                                            <multiselect>true</multiselect>
                                                            <size>10</size>
                                                          </args>
                                                        </listbox>

                                                              <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                              <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                      </children>
                                                    </form>';

                    if ($summaries[$this->mItemType]['loggable'])
                        $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                      <children>
                                                            <horizgroup>
                                                        <args>
                                                          <align>middle</align>
                                                        </args>
                                                        <children>
                                                          <button>
                                                            <args>
                                                              <themeimage>history</themeimage>
                                                              <themeimagetype>mini</themeimagetype>
                                                              <compact>true</compact>
                                                            </args>
                                                          </button>
                                                              <label><name>convert</name>
                                                                <args>
                                                                  <bold>true</bold>
                                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('history.label'))).'</label>
                                                                  <compact>true</compact>
                                                                </args>
                                                              </label>
                                                        </children>
                                                      </horizgroup>
                                                        <listbox><name>itemlog</name>
                                                          <args>
                                                            <readonly>true</readonly>
                                                            <elements type="array">'.WuiXml::encode(array_reverse($item_log->GetLog())).'</elements>
                                                            <size>3</size>
                                                          </args>
                                                        </listbox>
                                                      </children>
                                                    </vertgroup>';

                    if ($summaries[$this->mItemType]['convertible']) {
                        $convert_types = array();
                        require_once('innomatic/locale/LocaleCatalog.php');

                        foreach ($summaries as $type => $item) {
                            if ($item['convertible'] and $type != $this->mItemType) {
                                $tmp_locale = new LocaleCatalog($item['catalog'], InnomaticContainer::instance('innomaticcontainer')->getCurrentUser()->getLanguage());
                                $convert_types[$type] = $tmp_locale->getStr($type);
                                unset($tmp_locale);
                            }
                        }
                        if (count($convert_types))
                            $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                              <children>
                                                              <horizgroup>
                                                                <args>
                                                                  <align>middle</align>
                                                                </args>
                                                                <children>
                                                                  <button>
                                                                    <args>
                                                                      <themeimage>fileshare</themeimage>
                                                                      <themeimagetype>mini</themeimagetype>
                                                                      <compact>true</compact>
                                                                    </args>
                                                                  </button>
                                                                      <label><name>convert</name>
                                                                        <args>
                                                                          <bold>true</bold>
                                                                          <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('convert.label'))).'</label>
                                                                          <compact>true</compact>
                                                                        </args>
                                                                      </label>
                                                                </children>
                                                              </horizgroup>

                                                                <form><name>convert'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                                  <args>
                                                                    <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                                  </args>
                                                                  <children>

                                                                <horizgroup>
                                                                  <args>
                                                                    <align>middle</align>
                                                                  </args>
                                                                  <children>

                                                                    <combobox><name>type</name>
                                                                      <args>
                                                                        <disp>wui</disp>
                                                                        <elements type="array">'.WuiXml::encode($convert_types).'</elements>
                                                                      </args>
                                                                    </combobox>

                                                                    <button><name>convert</name>
                                                                      <args>
                                                                        <horiz>true</horiz>
                                                                        <frame>false</frame>
                                                                        <themeimage>filenew2</themeimage>
                                                                        <themeimagetype>mini</themeimagetype>
                                                                        <compact>true</compact>
                                                                        <formsubmit>convert'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                                        <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkconvert').'</action>
                                                                      </args>
                                                                    </button>

                                                                      <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                                      <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                                  </children>
                                                                </horizgroup>

                                                                  </children>
                                                                </form>
                                                              </children>
                                                            </vertgroup>';
                    }

                    // Clippings

                    if (count($clippings)) {
                        $this->mDefinition.= '<vertgroup row="'.$row ++.'" col="0">
                                                      <children>
                                                      <horizgroup>
                                                        <args>
                                                          <align>middle</align>
                                                        </args>
                                                        <children>
                                                          <button>
                                                            <args>
                                                              <themeimage>doc</themeimage>
                                                              <themeimagetype>mini</themeimagetype>
                                                              <compact>true</compact>
                                                            </args>
                                                          </button>
                                                              <label><name>clipping</name>
                                                                <args>
                                                                  <bold>true</bold>
                                                                  <label type="encoded">'.WuiXml::cdata(urlencode($locale->getStr('clipping.label'))).'</label>
                                                                  <compact>true</compact>
                                                                </args>
                                                              </label>
                                                        </children>
                                                      </horizgroup>

                                                        <form><name>clipping'.md5($this->mItemType.'-'.$this->mItemId).'</name>
                                                          <args>
                                                            <action>'.WuiXml::cdata($this->mDefaultAction).'</action>
                                                          </args>
                                                          <children>

                                                        <horizgroup>
                                                          <args>
                                                            <align>middle</align>
                                                          </args>
                                                          <children>

                                                            <combobox><name>clippingid</name>
                                                              <args>
                                                                <disp>wui</disp>
                                                                <elements type="array">'.WuiXml::encode($clippings).'</elements>
                                                              </args>
                                                            </combobox>

                                                            <button><name>clipping</name>
                                                              <args>
                                                                <horiz>true</horiz>
                                                                <frame>false</frame>
                                                                <themeimage>forward</themeimage>
                                                                <themeimagetype>mini</themeimagetype>
                                                                <compact>true</compact>
                                                                <formsubmit>clipping'.md5($this->mItemType.'-'.$this->mItemId).'</formsubmit>
                                                                <action>'.WuiXml::cdata($this->mDefaultAction.'&wui[wui][evn]=innoworkaddtoclipping').'</action>
                                                              </args>
                                                            </button>

                                                              <formarg><name>aclitemtype</name><args><disp>wui</disp><value>'.$this->mItemType.'</value></args></formarg>
                                                              <formarg><name>aclitemid</name><args><disp>wui</disp><value>'.$this->mItemId.'</value></args></formarg>

                                                          </children>
                                                        </horizgroup>

                                                          </children>
                                                        </form>
                                                      </children>
                                                    </vertgroup>';
                    }
                }

                $this->mDefinition.= '
                                              </children>
                                            </table>

                                      </children>
                                    </empty>';
            }
            $result = true;
        }
        return $result;
    }
}

?>