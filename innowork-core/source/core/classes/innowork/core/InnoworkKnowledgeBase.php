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

require_once('innomatic/logging/Logger.php');
require_once('innomatic/dataaccess/DataAccess.php');
require_once('innowork/core/InnoworkCore.php');
require_once('innowork/core/InnoworkItem.php');

class InnoworkKnowledgeBase {
    protected $mrRootDb;
    protected $mrDomainDA;
    protected $mLog;
    protected $mSummaries;

    /*!
     @function InnoworkKnowledgeBase
     */
    public function __construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA, $summaries = '') {
        $this->mLog = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLogger();
        $this->mrRootDb = $rrootDb;
        $this->mrDomainDA = $rdomainDA;
        if (is_array($summaries))
            $this->mSummaries = $summaries;
        else {
            $tmp_innoworkcore = InnoworkCore::instance('innoworkcore', $this->mrRootDb, $this->mrDomainDA);
            $this->mSummaries = $tmp_innoworkcore->GetSummaries();
        }
    }

    /*!
     @function GlobalSearch
     */
    public function &globalSearch($searchKeys, $type = '', $trashcan = false, $limit = 0, $restrictToPermission = InnoworkItem::SEARCH_RESTRICT_NONE) {
        $result = array();
        
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == InnomaticContainer::STATE_DEBUG) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLoadTimer()->Mark('InnoworkCore: start global search');
        }
        $result['result'] = array();
        $result['founditems'] = 0;

        while (list ($key, $value) = each($this->mSummaries)) {
            if ($value['searchable'] and ($type == '' or ($type != '' and $type == $key))) {
                $class_name = $this->mSummaries[$key]['classname'];
				if (!class_exists($class_name)) {
					continue;
				}
                $tmp_class = new $class_name($this->mrRootDb, $this->mrDomainDA);

                if (!$trashcan or ($trashcan and $tmp_class->mNoTrash == false)) {
                    $result['result'][$key] = $tmp_class->Search($searchKeys, '', false, $trashcan, (int) $limit, 0, $restrictToPermission);
                    $result['founditems'] += count($result['result'][$key]);
                    //$tmp_locale = new LocaleCatalog( $itemtype_query->getFields( 'catalog' ), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getLanguage() );
                }
            }
        }
        reset($this->mSummaries);
        if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getState() == InnomaticContainer::STATE_DEBUG) {
            \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLoadTimer()->Mark('InnoworkCore: end global search');
        }
        return $result;
    }
}

?>