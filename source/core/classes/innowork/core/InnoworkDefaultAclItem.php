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

require_once('innowork/core/InnoworkItem.php');

class InnoworkDefaultAclItem extends InnoworkItem {
    var $mTable = 'innowork_core_acls_defaults';
    var $mNoTrash = true;
    var $mConvertible = false;
    var $mSearchable = false;
	public $mNewDispatcher = '';
    var $mNoAcl = false;
    var $mNoLog = true;

    public function innoworkDefaultAclItem($rrootDb, $rdomainDA, $itemId = 0) {
        parent::__construct($rrootDb, $rdomainDA, 'defaultaclitem', $itemId);
        $this->mKeys['itemtype'] = 'text';
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
                    case 'itemtype' :
                        $keys.= $key_pre.$key;
                        $values.= $value_pre.$this->mrDomainDA->formatText($val);
                        break;

                    default :
                        break;
                }
            }

            if (strlen($values)) {
                if ($this->mrDomainDA->execute('INSERT INTO '.$this->mTable.' (id,ownerid'.$keys.') VALUES ('.$item_id.','.$userId.$values.')')) {
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
                            case 'itemtype' :
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
                $query = $this->mrDomainDA->execute('UPDATE '.$this->mTable.' SET '.$update_str.' WHERE id='.$this->mItemId);
                if ($query)
                    $result = true;
            }
        }
        return $result;
    }

    protected function doRemove($userId) {
        $result = false;
        $result = $this->mrDomainDA->execute('DELETE FROM '.$this->mTable.' WHERE id='.$this->mItemId);
        if ($result) {
            $this->mrDomainDA->execute('DELETE FROM innowork_core_acls_defaults WHERE ticketid='.$this->mItemId);
        }
        return $result;
    }

    protected function doGetSummary() {
        return false;
    }
}