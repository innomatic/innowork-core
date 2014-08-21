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
 *   Alex Pagnoni <alex.pagnoni@innomatic.io>
 *
 * ***** END LICENSE BLOCK ***** */

require_once('innowork/core/InnoworkItem.php');

class InnoworkClipping extends InnoworkItem {
    var $mTable = 'innowork_core_clippings';
    var $mNewDispatcher = 'view';
    var $mNewEvent = 'newclipping';
    var $mNoTrash = false;
    const ITEM_TYPE = 'clipping';

    public function __construct($rrootDb, $rdomainDA, $clippingId = 0) {
        parent::__construct($rrootDb, $rdomainDA, InnoworkClipping::ITEM_TYPE, $clippingId);
        $this->mKeys['name'] = 'text';
        $this->mKeys['description'] = 'text';
        $this->mSearchResultKeys[] = 'name';
        $this->mSearchResultKeys[] = 'description';
        $this->mViewableSearchResultKeys[] = 'name';
        $this->mViewableSearchResultKeys[] = 'description';
        $this->mSearchOrderBy = 'name';
        $this->mShowDispatcher = 'view';
        $this->mShowEvent = 'showclipping';
    }

    protected function doCreate($params, $userId) {
        $result = false;
        if (count($params)) {
            
            $item_id = $this->mrDomainDA->getNextSequenceValue($this->mTable.'_id_seq');
            $key_pre = $value_pre = $keys = $values = '';

            while (list ($key, $val) = each($params)) {
                $key_pre = ',';
                $value_pre = ',';

                switch ($key) {
                    case 'name' :
                    case 'description' :
                    case 'trashed' :
                        $keys.= $key_pre.$key;
                        $values.= $value_pre.$this->mrDomainDA->formatText($val);
                        break;

                    default :
                        break;
                }
            }

            if (strlen($values)) {
                if ($this->mrDomainDA->Execute('INSERT INTO '.$this->mTable.' '.'(id,ownerid'.$keys.') VALUES ('.$item_id.','.$userId.$values.')')) {
                    $result = $item_id;
                }
            }
        }
        return $result;
    }

    protected function doEdit($params) {
        $result = false;
        if ($this->mItemId) {
            if (count($params)) {
                $start = 1;
                $update_str = '';

                while (list ($field, $value) = each($params)) {
                    if ($field != 'id') {
                        switch ($field) {
                            case 'name' :
                            case 'description' :
                            case 'trashed' :
                                if (!$start)
                                    $update_str.= ',';
                                $update_str.= $field.'='.$this->mrDomainDA->formatText($value);
                                $start = 0;
                                break;

                            default :
                                break;
                        }
                    }
                }
                $query = $this->mrDomainDA->execute(
                	'UPDATE '.$this->mTable.
                	' SET '.$update_str.
                	' WHERE id='.$this->mItemId);
                if ($query)
                    $result = true;
            }
        }
        return $result;
    }

    protected function doTrash() {
        return true;
    }

    protected function doRemove($userId) {
        $result = false;
        $result = $this->mrDomainDA->execute('DELETE FROM '.$this->mTable.' WHERE id='.$this->mItemId);
        if ($result) {
            $this->mrDomainDA->execute('DELETE FROM innowork_core_clippings_items WHERE clippingid='.$this->mItemId);
        }
        return $result;
    }

    public function addItem($itemType, $itemId) {
        $result = false;
        $itemId = (int) $itemId;
        if ($this->mItemId and $itemId) {
            $check = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT itemid FROM innowork_core_clippings_items WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($itemType).' AND itemid='.$itemId);

            if (!$check->getNumberRows()) {
                $result = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('INSERT INTO innowork_core_clippings_items VALUES('.$this->mItemId.','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($itemType).','.$itemId.')');
            } else
                $result = true;

            if ($result) {
                require_once('innowork/core/InnoworkItemLog.php');
                $log = new InnoworkItemLog($this->mItemType, $this->mItemId);
                $log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
            }
            $check->Free();
        }
        return $result;
    }

    public function removeItem($itemType, $itemId) {
        $result = false;
        $itemId = (int) $itemId;
        if ($itemId)
            $result = $this->mrDomainDA->execute('DELETE FROM innowork_core_clippings_items WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($itemType).' AND itemid='.$itemId);

        return $result;
    }

    public function getItems() {
        $result = array('result' => array(), 'founditems' => 0);
        if ($this->mItemId) {
            $items_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT itemtype,itemid FROM innowork_core_clippings_items WHERE clippingid='.$this->mItemId.' ORDER BY itemtype,itemid');
            $result['founditems'] = 0;
            $innowork_core = \Innowork\Core\InnoworkCore::instance('\Innowork\Core\InnoworkCore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
            $summaries = $innowork_core->GetSummaries();

            while (!$items_query->eof) {
                $class_name = $summaries[$items_query->getFields('itemtype')]['classname'];
				if (!class_exists($class_name)) {
	                $items_query->MoveNext();
					continue;
				}

				$tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $items_query->getFields('itemid'));

                if (!is_object($tmp_class)) {
					$items_query->MoveNext();
					continue;
				}

				if ($tmp_class->mOwnerId == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() or $tmp_class->mAcl->checkPermission('', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())) {
					$item = $tmp_class->GetItem();

					$size = count($item);

					for ($i = 0; $i < $size / 2; $i ++) {
						unset($item[$i]);
					}

					if (!isset($result['result'][$items_query->getFields('itemtype')][$items_query->getFields('itemid')])) {
						$result['founditems']++;
					}
					$result['result'][$items_query->getFields('itemtype')][$items_query->getFields('itemid')] = $item;
					$result['result'][$items_query->getFields('itemtype')][$items_query->getFields('itemid')]['_acl']['type'] = $tmp_class->mAcl->GetType();
                }

                $items_query->MoveNext();
            }

            $items_query->Free();
        }
        return $result;
    }
}

?>