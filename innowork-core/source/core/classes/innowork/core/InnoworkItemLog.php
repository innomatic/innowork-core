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

class InnoworkItemLog {
    protected $mItemType;
    protected $mItemId;

    public function __construct($itemType, $itemId) {
        $this->mItemType = $itemType;
        $this->mItemId = $itemId;
    }

    public function logChange($user) {
        $date_array['year'] = date('Y');
        $date_array['mon'] = date('m');
        $date_array['mday'] = date('d');
        $date_array['hours'] = date('H');
        $date_array['minutes'] = date('i');
        $date_array['seconds'] = date('s');
        return \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('INSERT INTO innowork_core_itemslog '.'VALUES ('.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).','.$this->mItemId.','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($user).','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->GetTimestampFromDateArray($date_array)).')');
    }

    public function getLog() {
        $result = array();
        $log_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT username,eventtime '.'FROM innowork_core_itemslog '.'WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).' '.'AND itemid='.$this->mItemId);
        require_once('innomatic/locale/LocaleCountry.php');
        while (!$log_query->eof) {
            $country = new LocaleCountry(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getCountry());
            $date_array = $country->getDateArrayFromSafeTimestamp($log_query->getFields('eventtime'));
            $result[] = $country->FormatShortArrayDate($date_array).' '.$country->FormatArrayTime($date_array).' '.$log_query->getFields('username');
            $log_query->MoveNext();
        }
        $log_query->Free();
        return $result;
    }

    public function erase() {
        $result = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('DELETE FROM innowork_core_itemslog '.'WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).' '.'AND itemid='.$this->mItemId);
        return $result;
    }
    
    public function getItemType() {
        return $this->mItemType;
    }
    
    public function getItemId() {
        return $this->mItemId;
    }
}

?>