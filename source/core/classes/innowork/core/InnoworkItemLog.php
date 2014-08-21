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
 * Portions created by the Initial Developer are Copyright (C) 2002-2014
 * the Initial Developer. All Rights Reserved.
 *
 * Contributor(s):
 *   Alex Pagnoni <alex.pagnoni@innomatic.io>
 *
 * ***** END LICENSE BLOCK ***** */

/**
 * Class for handling Innowork items log.
 *
 * @copyright Copyright (c) 2002-2014 the Initial Developer. All rights reserved.
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 */
class InnoworkItemLog {
    /**
     * Innomatic container.
     *
     * @var \Innomatic\Core\InnomaticContainer
     * @access protected
     */
    protected $container;
    /**
     * Innowork item type.
     *
     * @var string
     * @access protected
     */
    protected $itemType;
    /**
     * Innowork item id.
     *
     * @var integer
     * @access protected
     */
    protected $itemId;

    /* public __construct($itemType, $itemId) {{{ */
    /**
     * Class constructor.
     *
     * @param string $itemType Innowork item type.
     * @param integer $itemId Innowork item id.
     * @access public
     * @return void
     */
    public function __construct($itemType, $itemId) {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain();
        $this->itemType  = $itemType;
        $this->itemId    = $itemId;
    }
    /* }}} */

    /* public logChange($user) {{{ */
    /**
     * Logs an item change.
     *
     * @todo Add an optional change log string.
     *
     * @param integer $user User id of the user who changed the item.
     * @access public
     * @return boolean
     */
    public function logChange($user) {
        $dataAccess = $this->container->getDataAccess();
        $date_array['year'] = date('Y');
        $date_array['mon'] = date('m');
        $date_array['mday'] = date('d');
        $date_array['hours'] = date('H');
        $date_array['minutes'] = date('i');
        $date_array['seconds'] = date('s');

        return $dataAccess->execute(
            'INSERT INTO innowork_core_itemslog'
            .' VALUES ('.$dataAccess->formatText($this->itemType)
            .','.$this->itemId.','
            .$dataAccess->formatText($user).','
            .$dataAccess->formatText($dataAccess->getTimestampFromDateArray($date_array)).')'
        );
    }
    /* }}} */

    /* public getLog() {{{ */
    /**
     * Gets the whole log of the current Innowork item.
     *
     * @access public
     * @return array
     */
    public function getLog() {
        $result = array();
        $dataAccess = $this->container->getDataAccess();

        $log_query = $dataAccess->execute(
            'SELECT username,eventtime'
            .' FROM innowork_core_itemslog'
            .' WHERE itemtype='.$dataAccess->formatText($this->itemType)
            .' AND itemid='.$this->itemId
        );

        while (!$log_query->eof) {
            $country = new \Innomatic\Locale\LocaleCountry(
                $this->container->getCurrentUser()->getCountry()
            );

            $date_array = $country->getDateArrayFromSafeTimestamp($log_query->getFields('eventtime'));

            $result[] = $country->formatShortArrayDate($date_array)
                .' '.$country->FormatArrayTime($date_array)
                .' '.$log_query->getFields('username');
            $log_query->moveNext();
        }

        $log_query->free();
        return $result;
    }
    /* }}} */

    /* public erase() {{{ */
    /**
     * Erases the whole Innowork item log.
     *
     * @access public
     * @return boolean
     */
    public function erase() {
        $dataAccess = $this->container->getDataAccess();
        return $dataAccess->execute(
            'DELETE FROM innowork_core_itemslog'
            .' WHERE itemtype='.$dataAccess->formatText($this->itemType)
            .' AND itemid='.$this->itemId
        );
    }
    /* }}} */

    /* public getItemType() {{{ */
    /**
     * Gets the Innowork item type.
     *
     * @access public
     * @return string
     */
    public function getItemType() {
        return $this->itemType;
    }
    /* }}} */

    /* public getItemId() {{{ */
    /**
     * Gets the Innowork item id.
     *
     * @access public
     * @return integer
     */
    public function getItemId() {
        return $this->itemId;
    }
    /* }}} */
}
