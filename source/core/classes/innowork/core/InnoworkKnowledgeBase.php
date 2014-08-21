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

require_once('innowork/core/InnoworkItem.php');

class InnoworkKnowledgeBase {
    /**
     * Innomatic container
     *
     * @var \Innomatic\Core\InnomaticContainer
     * @access protected
     */
    protected $container;
    protected $rootDA;
    protected $domainDA;
    protected $log;
    protected $summary;

    public function __construct(
        \Innomatic\Dataaccess\DataAccess $rootDA,
        \Innomatic\Dataaccess\DataAccess $domainDA,
        $summaries = ''
    ) {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');
        $this->log = $this->container->getLogger();
        $this->rootDA = $rootDA;
        $this->domainDA = $domainDA;

        if (is_array($summaries))
            $this->summary = $summaries;
        else {
            $tmpInnoworkcore = InnoworkCore::instance(
                '\Innowork\Core\InnoworkCore',
                $this->rootDA,
                $this->domainDA
            );
            $this->summary = $tmpInnoworkcore->GetSummaries();
        }
    }

    /* public &globalSearch($searchKeys, $type = '', $trashcan = false, $limit = 0, $restrictToPermission = InnoworkItem::SEARCH_RESTRICT_NONE) {{{ */
    /**
     * Executes a global search in multiple types.
     *
     * @param mixed $searchKeys
     * @param string|array $type A type or array of types to be searched for.
     * @param bool $trashcan
     * @param int $limit
     * @param bool $restrictToPermission
     * @access public
     * @return void
     */
    public function &globalSearch(
        $searchKeys,
        $type = '',
        $trashcan = false,
        $limit = 0,
        $restrictToPermission = InnoworkItem::SEARCH_RESTRICT_NONE
    ) {
        $result = array();

        if ($this->container->getState() == \Innomatic\Core\InnomaticContainer::STATE_DEBUG) {
            $this->container->getLoadTimer()->mark('InnoworkCore: start global search');
        }

        $result['result'] = array();
        $result['founditems'] = 0;

        while (list ($key, $value) = each($this->summary)) {
            if ($value['searchable'] and ($type == '' or ($type != '' and ((!is_array($type) and $type == $key) or (is_array($type) and in_array($key, $type)))))) {
                $class_name = $this->summary[$key]['classname'];
                if (!class_exists($class_name)) {
                    continue;
                }
                $tmp_class = new $class_name($this->rootDA, $this->domainDA);

                if (!$trashcan or ($trashcan and $tmp_class->mNoTrash == false)) {
                    $result['result'][$key] = $tmp_class->search($searchKeys, '', false, $trashcan, (int) $limit, 0, $restrictToPermission);
                    $result['founditems'] += count($result['result'][$key]);
                    //$tmp_locale = new LocaleCatalog( $itemtype_query->getFields( 'catalog' ), $this->container->getCurrentUser()->getLanguage() );
                }
            }
        }

        reset($this->summary);

        if ($this->container->getState() == \Innomatic\Core\InnomaticContainer::STATE_DEBUG) {
            $this->container->getLoadTimer()->mark('InnoworkCore: end global search');
        }

        return $result;
    }
    /* }}} */
}
