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
 * Portions created by the Initial Developer are Copyright (C) 2002-2013
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Alex Pagnoni <alex.pagnoni@innoteam.it>
 *
 * ***** END LICENSE BLOCK ***** */

namespace Innowork\Core;

use \Innomatic\Util\Singleton;
use \Innomatic\Locale\LocaleCatalog;
use \Innomatic\Dataaccess\DataAccess;

/*!
 @class InnoworkCore

 @abstract Innowork core infrastructure functions.
 */
class InnoworkCore extends Singleton {
    var $mrRootDb;
    var $mrDomainDA;
    var $mLog;
    var $mLocale;
    var $mSummaries = array();
	protected $wholeSummaries = array();

    /*!
     @function InnoworkCore
    
     @abstract Class constructor.
     */
    public function ___construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA) {
        $this->mLog = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLogger();
        $this->mLocale = new LocaleCatalog('innowork-core::misc', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
        $this->mrRootDb = $rrootDb;
        $this->mrDomainDA = $rdomainDA;
    }

    /*!
     @function getSummaries
     */
    public function getSummaries($showMode = '', $complete = false, $tags = array()) {
        $result = false;

        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == InnomaticContainer::STATE_DEBUG) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLoadTimer()->Mark('start - InnoworkCore::GetSummaries()');
        }

        if ($complete) {
            $env_section = 'complete_summaries'.$showMode;
        } else {
            $env_section = 'short_summaries'.$showMode;
        }

        if (isset($this->wholeSummaries[$env_section])) {
            $this->mSummaries = &$this->wholeSummaries[$env_section];
            $result = &$this->wholeSummaries[$env_section];
        } else {
            $enabledtypes_query = $this->mrDomainDA->Execute('SELECT itemtype FROM innowork_core_itemtypes_enabled ORDER BY itemtype');

            if (is_object($enabledtypes_query)) {
                $result = array();
                
                $tmp_perm = new \Innomatic\Domain\User\Permissions($this->mrDomainDA, \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getGroup());
                while (!$enabledtypes_query->eof) {
                    switch ($showMode) {
                        case 'app' :
                        case 'list' :
                            $extra_conditions = ' AND showmode='.$this->mrRootDb->formatText($showMode).' ';
                            break;

                        default :
                            $extra_conditions = '';
                    }

                    $itemtype_query = $this->mrRootDb->execute('SELECT classfile,classname,catalog,icon,icontype,domainpanel,adminevent,miniicon,summaryname,showmode FROM innowork_core_itemtypes WHERE itemtype='.$this->mrRootDb->formatText($enabledtypes_query->getFields('itemtype')).' '.$extra_conditions.'ORDER BY itemtype');

                    if (is_object($itemtype_query) and $itemtype_query->getNumberRows()) {
                        $node_id = $tmp_perm->getNodeIdFromFileName($itemtype_query->getFields('domainpanel'));

                        if ($node_id or !strlen($itemtype_query->getFields('domainpanel'))) {
                            if (!strlen($itemtype_query->getFields('domainpanel')) or $tmp_perm->check($node_id, Permissions::NODETYPE_PAGE) != Permissions::NODE_NOTENABLED) {
                                require_once($itemtype_query->getFields('classfile'));

                                $class_name = $itemtype_query->getFields('classname');
								if (class_exists($class_name)) {
									$tmp_class = new $class_name($this->mrRootDb, $this->mrDomainDA);

									// Check if there is a filter by tags
									if (count($tags) == 0 or count(array_intersect($tmp_class->mTypeTags, $tags)) > 0) {
										$tmp_locale = new LocaleCatalog($itemtype_query->getFields('catalog'), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage());
	
										$item_type = $enabledtypes_query->getFields('itemtype');
	
										$result[$item_type]['type'] = $item_type;
										$result[$item_type]['classname'] = $itemtype_query->getFields('classname');
										$result[$item_type]['catalog'] = $itemtype_query->getFields('catalog');
										$result[$item_type]['label'] = $tmp_locale->getStr($itemtype_query->getFields('summaryname'));
										$result[$item_type]['icon'] = $itemtype_query->getFields('icon');
										$result[$item_type]['icontype'] = $itemtype_query->getFields('icontype');
	
										if ($complete) {
											$result[$item_type]['widget'] = $tmp_class->getSummary();
										}
	
										$result[$item_type]['domainpanel'] = $itemtype_query->getFields('domainpanel');
										// @todo adminevent is old - change to panelevent
										$result[$item_type]['adminevent'] = $itemtype_query->getFields('adminevent');
										$result[$item_type]['miniicon'] = $itemtype_query->getFields('miniicon');
										$result[$item_type]['showmode'] = $itemtype_query->getFields('showmode');
										$result[$item_type]['table'] = $tmp_class->mTable;
										$result[$item_type]['keys'] = $tmp_class->mKeys;
										$result[$item_type]['searchresultkeys'] = $tmp_class->mSearchResultKeys;
										$result[$item_type]['viewablesearchresultkeys'] = $tmp_class->mViewableSearchResultKeys;
										$result[$item_type]['searchorderby'] = $tmp_class->mSearchOrderBy;
										$result[$item_type]['tags'] = $tmp_class->mTypeTags;
										$result[$item_type]['showdispatcher'] = $tmp_class->mShowDispatcher;
										$result[$item_type]['showevent'] = $tmp_class->mShowEvent;
										$result[$item_type]['newdispatcher'] = $tmp_class->mNewDispatcher;
										$result[$item_type]['newevent'] = $tmp_class->mNewEvent;
										$result[$item_type]['searchable'] = $tmp_class->mSearchable;
										$result[$item_type]['convertible'] = $tmp_class->mConvertible;
										$result[$item_type]['loggable'] = !$tmp_class->mNoLog;
										$result[$item_type]['trashable'] = !$tmp_class->mNoTrash;

                                //if ( !is_object( $result[$item_type]['widget'] ) ) unset( $result[$item_type] );
									}
								}
                            }
                        }

                        $itemtype_query->free();
                    }

                    $enabledtypes_query->moveNext();
                }

                $this->mSummaries = $result;
                $this->wholeSummaries[$env_section] = $this->mSummaries;
            }

            $enabledtypes_query->free();
        }

        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == InnomaticContainer::STATE_DEBUG) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLoadTimer()->Mark('end - InnoworkCore::GetSummaries()');
        }

        return $result;
    }

    /*!
     @function getMainToolBar
    
     @abstract Gets the Wui main toolbar array.
     */
    public function getMainToolBar($type = 'app', $itemType = '', $itemId = '') {    	
        $summaries = $this->getSummaries();
        $result['main'] = array(
        		'search' => array('label' => $this->mLocale->getStr('search.button'), 'themeimage' => 'zoom', 'horiz' => 'true', 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'search', ''))))
        		);
        $result['tools'] = array(
        		'todayactivities' => array('label' => $this->mLocale->getStr('today_activities.button'), 'themeimage' => 'calendarempty', 'horiz' => 'true', 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'today_activities', '')))),
        		'trashcan' => array('label' => $this->mLocale->getStr('trashcan.button'), 'themeimage' => 'trash', 'horiz' => 'true', 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'trashcan', ''))))
        		);
        if (is_array($summaries)) {
            while (list ($type, $summary) = each($summaries)) {
                if ($summary['showmode'] == $type)
                    $result['itemtypes'][$type] = array('label' => $summary['label'], 'themeimage' => $summary['miniicon'], 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString($summary['domainpanel'], array(array('view', $summary['adminevent'], ''))));
            }
            reset($summaries);
        }
        if (strlen($itemType) and strlen($itemId)) {
            $result['itemtools'] = array('relateditems' => array('label' => $this->mLocale->getStr('relateditems.button'), 'themeimage' => 'chart2', 'horiz' => 'true', 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('1innoworkcore', array(array('view', 'relateditems', array('itemtype' => $itemType, 'itemid' => $itemId))))));
        }
        //$result['settings'] = array('innowork' => array('label' => $this->mLocale->getStr('innoworksettings.button'), 'themeimage' => 'settings1', 'horiz' => 'true', 'action' => \Innomatic\Wui\Dispatch\WuiEventsCall::buildEventsCallString('innoworkcoreprefs', array(array('view', 'default', '')))));
        return $result;
    }

    /**
     * Permanently removes trashed items.
     */
    public function emptyTrashcan() {
        require_once('innowork/core/InnoworkKnowledgeBase.php');
        $innowork_kb = new InnoworkKnowledgeBase(
        	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
        	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
        );

        $global_search = $innowork_kb->globalSearch('', '', true);

        if ($global_search['founditems']) {
            $summaries = $this->getSummaries();

            foreach ($global_search['result'] as $type => $search_items) {
                $class_name = $summaries[$type]['classname'];

                foreach ($search_items as $item) {
                	// Checks if the class exists.
                	if (!class_exists($class_name)) {
                		continue;
                	}
                    $tmp_class = new $class_name(
                    	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
                    	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(),
                    	$item['id']
            		);
                    if (is_object($tmp_class)) {
                    	// Removes the trashed item.
                        $tmp_class->remove();
                        unset($tmp_class);
                    }
                }
            }
        }
    }

	/**
	 * Returns a list of the item created/changed today (or at the given date).
	 * 
	 * @param string $date
	 * @param integer $userId
	 * @return array
	 */
    public function getTodayActivities($date = '', $userId = '') {
        $result = array('result' => array(), 'founditems' => 0);

        if (!is_array($date)) {
            $date = array();

            $date['year'] = date('Y');
            $date['mon'] = date('m');
            $date['mday'] = date('d');
        }

        $act_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->Execute(
        	'SELECT
        		*
        	FROM
        		innowork_core_itemslog
        	WHERE
        		eventtime LIKE '.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($date['year'].'-'.$date['mon'].'-'.$date['mday'].' %'));

        if ($act_query->getNumberRows()) {
            $summaries = $this->getSummaries();
            $found = 0;

            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            while (!$act_query->eof) {
                $class_name = $summaries[$act_query->getFields('itemtype')]['classname'];

                // Checks if the class exists.
                if (!class_exists($class_name)) {
                	continue;
                }
                
                $tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $act_query->getFields('itemid'));

                if (is_object($tmp_class)) {
                    if ($tmp_class->mOwnerId == $userId or $tmp_class->mAcl->checkPermission('', $userId)) {
                        $item = $tmp_class->getItem();
                        $size = count($item);

                        for ($i = 0; $i < $size / 2; $i ++) {
                            unset($item[$i]);
                        }

                        if (!isset($result['result'][$act_query->getFields('itemtype')][$act_query->getFields('itemid')])) {
                            $found++;
                        }
                        $result['result'][$act_query->getFields('itemtype')][$act_query->getFields('itemid')] = $item;
                        $result['result'][$act_query->getFields('itemtype')][$act_query->getFields('itemid')]['_acl']['type'] = $tmp_class->mAcl->getType();
                    }
                }

                $act_query->moveNext();
            }
            $result['founditems'] = $found;
            $act_query->free();
        }
        return $result;
    }
}