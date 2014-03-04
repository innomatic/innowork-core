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
 *   Alex Pagnoni <alex.pagnoni@innoteam.it>
 *
 * ***** END LICENSE BLOCK ***** */

/*!
 @class InnoworkItem
 @abstract Base item class.
 */
abstract class InnoworkItem
{
    // InnoworkItem defined vars

    /*! @var mrRootDb DataAccess class - Innomatic database handler. */
    public $mrRootDb;
    /*! @var mrDomainDA DataAccess class - Domain database handler. */
    public $mrDomainDA;
    /*! @var mNoLog boolean - Flag to specify if item changed should be logged. */
    public $mNoLog = false;
    /*! @var mNoAcl boolean - Flag to specify if the item is ACL based or not. */
    public $mNoAcl = false;
    /*! @var mAcl InnoworkAcl class - Access list handler. */
    public $mAcl;
    /*! @var mLastError integer - Last error id number. */
    public $mLastError;
    /*! @var mOwnerId integer - User id number of the item owner. */
    public $mOwnerId;
    /*! @var mCreated timestamp - Item creation date. */
    public $mCreated;
    /*! @var mParentType - Item type name of the item parent, if any. */
    public $mParentType;
    /*! @var mParentId - Item id number of item parent, if any. */
    public $mParentId;

	const SEARCH_RESTRICT_NONE = 0;
	const SEARCH_RESTRICT_TO_OWNER = 1;
	const SEARCH_RESTRICT_TO_RESPONSIBLE = 2;
	const SEARCH_RESTRICT_TO_PARTICIPANT = 3;
    
    // Extension class defined vars

    /*! @var mItemType string - Item type name. */
    public $mItemType;
    /*! @var mItemId integer - Item id number. */
    public $mItemId;

    // To be explicitly defined by the extension class

    /*! @var mTable string - Item type database table name. */
    public $mTable;
    /*! @var mKeys array - Array of the searchable keys. */
    public $mKeys;
    /*! @var mSearchable boolean - True if the item type is searchable. */
    public $mSearchable = true;
    /*! @var mSearchResultKeys array - Array of the search result keys. */
    public $mSearchResultKeys;
    /*! @var mSearchResultKeys array - Array of the viewable search result keys. */
    public $mViewableSearchResultKeys;
    /*! @var mSearchOrderBy string - Key names on which the search results should be sorted, separated by a comma. */
    public $mSearchOrderBy = 'id';
    /*! @var mSimpleSearch boolean - Set to true if the search can be performed by the internal generic search engine. */
    public $mSimpleSearch = true;
    public $mShowDispatcher = 'view';
    public $mShowEvent = 'show';
    public $mNewDispatcher = 'view';
    public $mNewEvent = 'new';
    public $mRelatedItemsFields = array();
    /*! @var mNoTrash boolean - Set to true if the item is not trashable. */
    public $mNoTrash = true;

    /*! @var mGenericFields array - Array for mapping item's own fields to generic ones. */
    public $mGenericFields = array();
    /*! @var mConvertible boolean - True if the item accepts to be converted from or to another item. */
    public $mConvertible = false;
    
    /**
     * Array of tags supported by this item type, eg. task, invoice, project, etc.
     * @var array
     */
    public $mTypeTags = array();
    
    public $mFsBasePath;
    

    /*!
     @function InnoworkItem
     @abstract Class constructor.
     @param rrootDb DataAccess class - Innomatic database handler.
     @param rdomainDA DataAccess class - Domain database handler.
     @param itemType string - Item type name.
     @param itemId integer - Item id number.
     */
    public function __construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA, $itemType, $itemId = 0)
    {
    	require_once('innowork/core/InnoworkAcl.php');
    	// Item identification (type + id)
        $this->mItemType = $itemType;
        $this->mItemId = $itemId;
        // DataAccess
        $this->mrRootDb = $rrootDb;
        $this->mrDomainDA = $rdomainDA;
        // Item keys
        $this->mKeys['id'] = 'integer';
        // Trash support
        if ($this->mNoTrash == false) {
            $this->mKeys['trashed'] = 'boolean';
        }

        // Redundant to the default value but safe
        //
        if (!strlen($this->mSearchOrderBy)) {
            $this->mSearchOrderBy = 'id';
        }

        $this->mSearchResultKeys[] = 'id';
        $this->mSearchResultKeys[] = 'ownerid';
        if ($this->mNoTrash == false) {
            $this->mSearchResultKeys[] = 'trashed';
        }
        if ($this->mNoAcl and !isset($this->_mCreationAcl)) {
            $this->_mCreationAcl = InnoworkAcl::TYPE_PUBLIC;
        }

        // Check if the item id is valid
        //
        if ($this->mItemId) {
            $check_query = $this->mrDomainDA->execute('SELECT ownerid FROM '.$this->mTable.' WHERE id='.$this->mItemId);
            if ($check_query->getNumberRows()) {
                // Get owner id
                //
                $this->mOwnerId = $check_query->getFields('ownerid');
            } else {
                $log = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getLogger();
                $log->logEvent('innoworkcore.innoworkcore.innoworkitem.innoworkitem', 'Invalid item id '.$this->mItemId.' from '.$this->mItemType.' item type handler', \Innomatic\Logging\Logger::WARNING);
                $this->mItemId = 0;
            }
            $check_query->Free();
        }

        // Item ACL
        if (strlen($this->mParentType) and $this->mParentId) {
        	// Gets the ACL from the parent object
            require_once('innowork/core/InnoworkCore.php');
        	$core = InnoworkCore::instance('innoworkcore', $this->mrRootDb, $this->mrDomainDA);
            $summaries = $core->getSummaries();
            unset($core);
            $class_name = $summaries[$this->mParentType]['classname'];
			if (class_exists($class_name)) {
	            $tmp_class = new $class_name($this->mrRootDb, $this->mrDomainDA, $this->mParentId);
				$this->mAcl = &$tmp_class->mAcl;
			}
        } else {
        	// Gets its own ACL
            $this->mAcl = new InnoworkAcl($this->mrRootDb, $this->mrDomainDA, $this->mItemType, $this->mItemId, $this->mOwnerId);
        }

        // Generic fields
        $this->mGenericFields = array(
        	'projectid' => '',
        	'customerid' => '',
        	'title' => '',
        	'content' => '',
        	'binarycontent' => '',
        	'date' => '',
        	'spenttime' => '',
        	'cost' => ''
        );
        
        // Item folder in filesystem
        if ($itemId != 0) {
            $this->mFsBasePath = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getHome().'files/'.$this->getItemTypePlural().'/'.$this->mItemId.'/';
        }
    }

    /*!
     @function Create
     @abstract Creates a new item.
     @param params array - Array of the item parameters.
     @param userId integer - User id number of the owner, or none if the current user id should be used.
     */
    public function create($params, $userId = '')
    {
        $result = false;
        if ($this->mItemId == 0) {
            if (!strlen($userId)) {
                
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

	    $item_id = $this->doCreate($params, $userId);
            if ($item_id) {
                $this->mItemId = $item_id;
                $this->mOwnerId = $userId;
                $this->mCreated = time();

                // Item folder in filesystem
                $this->mFsBasePath = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getHome().'files/'.$this->getItemTypePlural().'/'.$this->mItemId.'/';
                
                if (!strlen($this->mParentType)) {
                    $this->mAcl->mItemId = $item_id;
                    if (!isset($this->_mSkipAclSet)) {
                        if (isset($this->_mCreationAcl))
                            $this->mAcl->SetType($this->_mCreationAcl);
                        else {
                            $check_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT * FROM innowork_core_acls_defaults WHERE ownerid='.$this->mOwnerId.' AND itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType));

                            if ($check_query->getNumberRows()) {
                                $this->mAcl->CopyAcl('defaultaclitem', $check_query->getFields('id'));
                            } else {
                                $this->mAcl->SetType(InnoworkAcl::TYPE_PRIVATE);
                            }
                        }
                    }
                } else {
                    require_once('innowork/core/InnoworkCore.php');
                	$core = InnoworkCore::instance('innoworkcore', $this->mrRootDb, $this->mrDomainDA);
                    $summaries = $core->GetSummaries();
                    unset($core);
                    $class_name = $summaries[$this->mParentType]['classname'];
					if (!class_exists($class_name)) {
						return false;
					}
					$tmp_class = new $class_name($this->mrRootDb, $this->mrDomainDA, $this->mParentId);
                    $this->mAcl = $tmp_class->mAcl;
                }
                if (!$this->mNoLog) {
                    require_once('innowork/core/InnoworkItemLog.php');
                	$log = new InnoworkItemLog($this->mItemType, $this->mItemId);
                    $log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
                }
                // Flush item type cache
                $this->cleanCache();
                $result = true;
            }
        }
        return $result;
    }

    /*!
     @function _Create
     */
    protected function doCreate($params, $userId)
    {
        return false;
    }

    /*!
     @function getItem
     @abstract Gets item data.
     @param userId integer - user id number of the owner, or none if the current user id should be used.
     */
    public function &getItem($userId = '')
    {
        $result = false;

        if ($this->mItemId) {
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_READ) {
                $result = $this->doGetItem($userId);
            } else {
                $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
            }
        }

        return $result;
    }

    protected function &doGetItem($userId)
    {
        $result = false;

        $item_query = $this->mrDomainDA->execute('SELECT * FROM '.$this->mTable.' WHERE id='.$this->mItemId);

        if (is_object($item_query) and $item_query->getNumberRows()) {
            $result = $item_query->getFields();

            $item_query->Free();
        }

        return $result;
    }

    /**
     * Returns item type identifier.
     * 
     * @return string
     */
    public function getItemType()
    {
        return $this->mItemType;
    }

    /**
     * Returns item type identifier in plural version.
     * Items with a non simple plural (eg. "companies") should overwrite this method.
     * 
     * @return string
     */
    public function getItemTypePlural()
    {
        return $this->mItemType.'s';
    }
    
    public function getItemId()
    {
        return $this->mItemId;
    }

    /*!
     @function Edit
     @abstract Edits item data.
     @param $params array - Array of the item parameters.
     @param userId integer - user id number of the owner, or none if the current user id should be used.
     */
    public function edit($params, $userId = '')
    {
        $result = false;

        if ($this->mItemId) {
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_EDIT) {
                $result = $this->doEdit($params, $userId);

                if ($result) {
                    if (!$this->mNoLog) {
                        require_once('innowork/core/InnoworkItemLog.php');
                    	$log = new InnoworkItemLog($this->mItemType, $this->mItemId);
                        $log->LogChange(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName());
                    }
                    // Flush item type cache
                    $this->cleanCache();
                }
            } else {
                $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
            }
        }
        return $result;
    }

    /*!
     @function _Edit
     */
    protected function doEdit($params, $userId)
    {
        $result = false;
        if ($this->mItemId) {
            if (count($params)) {
                $start = 1;
                $update_str = '';

                while (list ($field, $value) = each($params)) {
                    if ($field != 'id') {
                        if (!$start)
                            $update_str.= ',';
                        $update_str.= $field.'='.$this->mrDomainDA->formatText($value);
                        $start = 0;
                    }
                }
                $query = $this->mrDomainDA->execute(
                	'UPDATE '.$this->mTable.
                	' SET '.$update_str.
                	' WHERE id='.$this->mItemId);
                if ($query) {
                    $result = true;
                }
            }
        }
        return $result;
    }

    /*!
     @function Remove
     @abstract Removes the item.
     @param userId integer - user id number of the owner, or none if the current user id should be used.
     */
    public function remove($userId = '')
    {
        $result = false;

        if ($this->mItemId) {
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
                $result = $this->doRemove($userId);

                if ($result) {
                    // Remove ACL
                    $this->mAcl->erase();
                    // Remove item from clippings
                    \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('DELETE FROM innowork_core_clippings_items WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).' AND itemid='.$this->mItemId);
                    // Remove item log
                    if (!$this->mNoLog) {
                        require_once('innowork/core/InnoworkItemLog.php');
                    	$log = new InnoworkItemLog($this->mItemType, $this->mItemId);
                        $log->Erase();
                    }
                    // Remove item files
                    $this->removeFile($this->getBaseFolder());
                    // Remove lock
                    $this->unlock();
                    // Flush item type cache
                    $this->cleanCache();
                    // Clean object id
                    $this->mItemId = 0;
                }
            } else {
                $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
            }
        }
        return $result;
    }

    /*!
     @function _Remove
     */
    protected function doRemove($userId)
    {
        return false;
    }

    /*!
     @function Trash
     @abstract Trash the item.
     @param userId integer - user id number of the owner, or none if the current user id should be used.
     */
    public function trash($userId = '')
    {
        $result = false;

        if ($this->mItemId and $this->mNoTrash == false) {
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
                $result = $this->doTrash($userId);

                if ($result) {
                    $result = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('UPDATE '.$this->mTable.' SET trashed='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmttrue).' WHERE id='.$this->mItemId);
                    // Flush item type cache
                    $this->CleanCache();
                }
            } else {
                $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
            }
        }
        return $result;
    }

    /*!
     @function _Trash
     */
    protected function doTrash($userId)
    {
        return false;
    }

    public function restore($userId = '')
    {
        $result = false;

        if ($this->mItemId and $this->mNoTrash == false) {
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
                $result = $this->doRestore($userId);

                if ($result) {
                    $result = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('UPDATE '.$this->mTable.' SET trashed='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->fmtfalse).' WHERE id='.$this->mItemId);
                    $this->CleanCache();
                }
            } else {
                $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
            }
        }
        return $result;
    }

    /*!
     @function _Restore
     */
    protected function doRestore($userId)
    {
        return true;
    }

    /*!
     @function getSummary
     @abstract Returns the summary for this item type.
     */
    public function &getSummary()
    {
        return $this->doGetSummary();
    }

    /*!
     @function _GetSummary
     */
    protected function &doGetSummary()
    {
        return false;
    }

    /*!
     @function Search
     @abstract Searches in the items. If $globalSearch is set to true, an OR search is performed,
     an AND search in the other case. If $searchKeys is not an array but a string, the search is
     performed in all the keys with OR.
     */
    public function &search($searchKeys, $userId = '', $globalSearch = false, $trashcan = false, $limit = 0, $offset = 0, $restrictToPermission = InnoworkItem::SEARCH_RESTRICT_NONE)
    {
        $result = array();
        $goon = true;
        $to_be_cached = false;

        if (!is_array($searchKeys) and !strlen($searchKeys) and !$trashcan and !$limit and !$offset and $restrictToPermission == InnoworkItem::SEARCH_RESTRICT_NONE) {
            $cached_item = new \Innomatic\Datatransfer\Cache\CachedItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), 'innowork-core', 'itemtypesearch-'.$this->mItemType.strtolower(str_replace(' ', '', $this->mSearchOrderBy)), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'], \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId());
            $cache_content = $cached_item->Retrieve();
            if ($cache_content != false) {
                $goon = false;
                $to_be_cached = false;
                $result = unserialize($cache_content);
            } else {
                $to_be_cached = true;
            }
        }

        // Check if the search keys to be returned are valid keys
        //
        if (is_array($searchKeys)) {
            while (list ($key,) = each($searchKeys)) {
                if (!isset($this->mKeys[$key])) {
                    unset($searchKeys[$key]);
                }
            }

            reset($searchKeys);
            if (!count($searchKeys)) {
                $goon = false;
            }
        }

        if ($goon) {
            // Check if we should use the current user id
            //
            if (!strlen($userId)) {
                $userId = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId();
            }
            $result = array();

            // Call the search method
            //
            $search_result = $this->doSearch($searchKeys, $userId, $globalSearch, $trashcan, $limit, $offset);

            // Check if the user has enough permissions for each row in the result set,
            // and add the ones with enough permissions
            //
            if (is_array($search_result) and count($search_result)) {
                while (list ($id, $val) = each($search_result)) {
                    $tmp_acl = new InnoworkAcl($this->mrRootDb, $this->mrDomainDA, $this->mItemType, $id);
                    if ($this->mNoAcl == true or $val['ownerid'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) >= InnoworkAcl::PERMS_SEARCH) {
                        $restrict = false;

                        switch ($restrictToPermission) {
                            case InnoworkItem::SEARCH_RESTRICT_TO_OWNER :
                                if ($val['ownerid'] != \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId())
                                    $restrict = true;
                                break;

                            case InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE :
                                $restrict = true;
                                if ($val['ownerid'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) == InnoworkAcl::PERMS_RESPONSIBLE)
                                    $restrict = false;
                                break;

                            case InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT :
                                if ($val['ownerid'] == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) >= InnoworkAcl::PERMS_ALL)
                                    $restrict = true;
                                break;

                            case InnoworkItem::SEARCH_RESTRICT_NONE :
                            default :
                                break;
                        }

                        if (!$restrict) {
                            $result[$id] = $val;
                            $result[$id]['_acl']['type'] = $tmp_acl->GetType();
                        }
                    }
                }
            }
        }
        if ($to_be_cached) {
            $cached_item->store(serialize($result));
        }
        return $result;
    }

    /*!
     @function _Search
     */
    protected function &doSearch($searchKeys, $userId, $globalSearch = false, $trashcan = false, $limit = 0, $offset = 0)
    {
        $result = false;

        // This should be always true if this method is available and not
        // overwritten by the extension class one
        //
        if ($this->mSimpleSearch) {
            $keys_str = $result_keys_str = '';

            // Convert $searchKeys to array if it is a string

            if (!is_array($searchKeys) and strlen($searchKeys)) {
                //$searchWord = $searchKeys;
                //$searchKeys = array();
                $globalSearch = true;

                /*
                while ( list( $key ) = each( $this->mKeys ) )
                {
                    $searchKeys[$key] = $searchWord;
                }
                
                unset( $searchWord );
                */
            }

            if ($globalSearch) {
                $vals_array = explode(' ', $searchKeys);
                $start_values = true;

                foreach ($vals_array as $val) {
                    reset($this->mKeys);
                    $all_ok = false;
                    $start_keys = true;
                    $tmp_keys_str = '';
                    $not = false;
                    $strict = false;

                    if (substr($val, 0, 1) == '-') {
                        $not = true;
                        $val = substr($val, 1);
                    }

                    if ($val {
                        0 }
                    == '"' and $val {
                        strlen($val) - 1 }
                    == '"') {
                        $val = substr($val, 1, strlen($val) - 2);
                        $strict = true;
                    }

                    while (list ($key) = each($this->mKeys)) {
                        $ok = false;
                        $ok_str = '';

                        // Check if the key is a foreign key
                        $tmp_key_args = explode(':', $this->mKeys[$key]);
                        if (isset($tmp_key_args[3])) {
                            $tmp_key_type = $tmp_key_args[3];
                        } else {
                            $tmp_key_type = $tmp_key_args[0];
                        }

                        // Check key type

                        switch ($tmp_key_type) {
                            case 'table' :
                                $ok = false;
                                break;

                            case 'integer' :
                                if (is_numeric($val)) {
                                    $ok = true;
                                    $ok_str.= ' '.$key.' '. ($not ? '<>' : '=').' '.$val;
                                }
                                break;

                            default :
                                $ok = true;
                                if ($strict) {
                                    $ok_str.= ' ( ';
                                    $ok_str.= ' upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('% '.strtoupper($val).' %');
                                    $ok_str.= ' OR upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText(strtoupper($val).' %');
                                    $ok_str.= ' OR upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('% '.strtoupper($val));
                                    $ok_str.= ' OR upper('.$key.') '. ($not ? '<>' : '=').' '.$this->mrDomainDA->formatText(strtoupper($val));
                                    $ok_str.= ' ) ';
                                } else {
                                    $ok_str.= ' upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('%'.strtoupper($val).'%');
                                }
                        }

                        if ($ok) {
                            $all_ok = true;
                            if ($start_keys == true)
                                $tmp_keys_str.= ' (';
                            else
                                $tmp_keys_str.= $not ? ' AND' : ' OR';
                            $tmp_keys_str.= $ok_str;
                            $start_keys = false;
                        }
                    }

                    if (!$start_values and $all_ok) {
                        $keys_str.= ' AND';
                    }
                    $keys_str.= $tmp_keys_str;

                    if (!$start_keys)
                        $keys_str.= ')';

                    // First key passed
                    if ($all_ok)
                        $start_values = false;
                }
            } else {
                if (is_array($searchKeys)) {
                    // Search for each key
                    $start_a = true;

                    while (list ($key, $vals) = each($searchKeys)) {
                        $vals_array = explode(' ', $vals);
                        $start_b = true;
                        $all_ok = false;
                        $tmp_keys_str = '';

                        // Search key for each value
                        foreach ($vals_array as $val) {
                            $ok = false;
                            $ok_str = '';
                            $not = false;
                            $strict = false;

                            if (substr($val, 0, 1) == '-') {
                                $not = true;
                                $val = substr($val, 1);
                            }

                            if ($val {
                                0 }
                            == '"' and $val {
                                strlen($val) - 1 }
                            == '"') {
                                $val = substr($val, 1, strlen($val) - 2);
                                $strict = true;
                            }

                            // Check if the key is a foreign key

                            $tmp_key_args = explode(':', $this->mKeys[$key]);
                            if (isset($tmp_key_args[3]))
                                $tmp_key_type = $tmp_key_args[3];
                            else
                                $tmp_key_type = $tmp_key_args[0];

                            // Check key type

                            switch ($tmp_key_type) {
                                case 'table' :
                                    $ok = false;
                                    break;

                                case 'integer' :
                                    if (is_numeric($val)) {
                                        $ok = true;
                                        $ok_str.= ' '.$key.' = '.$val;
                                    }
                                    break;

                                default :
                                    $ok = true;
                                    //$ok_str .= ' upper('.$key.') LIKE '.$this->mrDomainDA->formatText( '%'.strtoupper( $val ).'%' );

                                    if ($strict) {
                                        $ok_str.= ' ( ';
                                        $ok_str.= ' upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('% '.strtoupper($val).' %');
                                        $ok_str.= ' OR upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText(strtoupper($val).' %');
                                        $ok_str.= ' OR upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('% '.strtoupper($val));
                                        $ok_str.= ' OR upper('.$key.') '. ($not ? '<>' : '=').' '.$this->mrDomainDA->formatText(strtoupper($val));
                                        $ok_str.= ' ) ';
                                    } else {
                                        $ok_str.= ' upper('.$key.') '. ($not ? 'NOT ' : '').'LIKE '.$this->mrDomainDA->formatText('%'.strtoupper($val).'%');
                                    }
                            }

                            if ($ok) {
                                $all_ok = true;

                                //$tmp_keys_str .= ' (';
                                if ($start_b == true)
                                    $tmp_keys_str.= ' (';
                                else
                                    $tmp_keys_str.= ' OR';

                                $tmp_keys_str.= $ok_str;
                                $start_b = false;
                            }
                        }
                        if (!$start_a and $all_ok) {
                            $keys_str.= ' AND';
                        }
                        $keys_str.= $tmp_keys_str;
                        if (!$start_b)
                            $keys_str.= ')';
                        // First key passed
                        if ($all_ok)
                            $start_a = false;
                    }
                }
            }

            if (is_array($this->mSearchResultKeys) and count($this->mSearchResultKeys)) {
                $start = true;
                while (list (, $key) = each($this->mSearchResultKeys)) {
                    if (!$start)
                        $result_keys_str.= ',';
                    $result_keys_str.= $key;

                    $start = false;
                }
                reset($this->mSearchResultKeys);
            } else
                $result_keys_str = '*';
            $search_query = $this->mrDomainDA->execute('SELECT '.$result_keys_str.' FROM '.$this->mTable.' '. ($this->mNoTrash ? (strlen($keys_str) ? 'WHERE'.$keys_str.' ' : '') : 'WHERE '. (strlen($keys_str) ? '('.$keys_str.') AND (' : '').' trashed IS '. ($trashcan ? 'NOT' : '').' NULL '. ($trashcan ? 'and' : 'or').' trashed='.$this->mrDomainDA->formatText($trashcan ? $this->mrDomainDA->fmttrue : $this->mrDomainDA->fmtfalse). (strlen($keys_str) ? ')' : '').' '.$this->getExtraSearchConditions($searchKeys).' ').'ORDER BY '.$this->mSearchOrderBy. ((int) $limit ? ' LIMIT '. (int) $limit : ''). (((int) $limit and (int) $offset) ? ' OFFSET '. (int) $offset : ''));
            if (is_object($search_query)) {
                while (!$search_query->eof) {
                    while (list (, $key) = each($this->mSearchResultKeys)) {
                        $result[$search_query->getFields('id')][$key] = $search_query->getFields($key);
                    }
                    reset($this->mSearchResultKeys);
                    $search_query->MoveNext();
                }
                $search_query->Free();
            }
        }
        return $result;
    }

    protected function getExtraSearchConditions($searchKeys)
    {
        return '';
    }

    public function getRelatedItems()
    {
        $result = array('result' => array(), 'founditems' => 0);
        if ($this->mItemId) {
            $search_keys = array();
            while (list (, $field) = each($this->mRelatedItemsFields)) {
                $search_keys[$field] = $this->mItemId;
            }
            reset($this->mRelatedItemsFields);
            require_once('innowork/core/InnoworkKnowledgeBase.php');
            $innowork_kb = new InnoworkKnowledgeBase(
            	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
            );
            $result = $innowork_kb->globalSearch($search_keys, '');
        }
        return $result;
    }

    public function hasTypeTag($tag)
    {
    	return in_array($tag, $this->mTypeTags);
    }
    
    public function getExternalItemWidgetXmlData($item)
    {
    	return '';
    }
    
    /**
     * Creates a new item of another type from the current item.
     *
     * @param string $type Destination type.
     * @return bool
     */
    public function convertTo($type)
    {
        if ($this->mItemId and $this->mConvertible) {
            require_once('innowork/core/InnoworkCore.php');
        	$tmp_innoworkcore = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
            $summaries = $tmp_innoworkcore->getSummaries();
            $class_name = $summaries[$type]['classname'];
			if (!class_exists($class_name)) {
				return false;
			}
            $tmp_class = new $class_name(
            	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(),
            	\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()
            );

            if ($tmp_class->mConvertible) {
                $real_data = $this->getItem();
                $generic_data['companyid'] = $real_data[$this->mGenericFields['companyid']];
                $generic_data['projectid'] = $real_data[$this->mGenericFields['projectid']];
                $generic_data['title'] = $real_data[$this->mGenericFields['title']];
                $generic_data['content'] = $real_data[$this->mGenericFields['content']];
                $generic_data['binarycontent'] = $real_data[$this->mGenericFields['binarycontent']];
                return $tmp_class->convertFrom($generic_data);
            }
        }
		return false;
    }

    /**
     * Creates a new item from another item of different type.
     *
     * @param array $genericData
     * @return bool
     */
    public function convertFrom($genericData)
    {
        $result = false;
        if ($this->mConvertible) {
            if (strlen($this->mGenericFields['companyid']))
                $real_data[$this->mGenericFields['companyid']] = $genericData['companyid'];
            if (strlen($this->mGenericFields['projectid']))
                $real_data[$this->mGenericFields['projectid']] = $genericData['projectid'];
            if (strlen($this->mGenericFields['title']))
                $real_data[$this->mGenericFields['title']] = $genericData['title'];
            if (strlen($this->mGenericFields['content']))
                $real_data[$this->mGenericFields['content']] = $genericData['content'];
            if (strlen($this->mGenericFields['binarycontent']))
                $real_data[$this->mGenericFields['binarycontent']] = $genericData['binarycontent'];
            $result = $this->create($real_data);
        }
        return $result;
    }

    /**
     * Clears the cache for the current item.
     *
     * @return boolean
     */
    public function cleanCache()
    {
        $cache_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->execute(
        	'SELECT itemid
        	FROM
        		cache_items
        	WHERE
        		application='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText('innowork-core').' AND
        		itemid LIKE '.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText('itemtypesearch-'.$this->mItemType.'%'));

        while (!$cache_query->eof) {
            $cached_item = new \Innomatic\Datatransfer\Cache\CachedItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), 'innowork-core', $cache_query->getFields('itemid'));
            $cached_item->destroy();
            $cache_query->moveNext();
        }
        $cache_query->free();
        return true;
    }

    /**
     * Locks the current item.
     *
     */
    public function lock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->setRed();
    }

    /**
     * Unlocks the current item.
     *
     */
    public function unlock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->setGreen();
    }

    /**
     * Tells if the current item is locked.
     *
     * @return bool
     */
    public function isLocked()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        return $sem->checkStatus() == Semaphore::STATUS_RED ? true : false;
    }

    /**
     * Waits until the item is unlocked by another instance.
     *
     */
    public function waitLock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->waitGreen();
    }
    
    public function getBaseFolder() {
        return InnoworkJurisDossier::$this->mFsBasePath;
    }
    
    public function checkBaseFolder() {
        if (!is_dir($this->getBaseFolder())) {
            require_once('innomatic/io/filesystem/DirectoryUtils.php');
            DirectoryUtils::mktree($this->getBaseFolder(), 0755);
        }
    }
    
    public function getFilesList($path) {
        $this->checkBaseFolder();
         
        $files = array();
         
        $dir = $this->getBaseFolder().$path.'/';
         
        if (is_dir($dir)) {
            if ($dh = opendir($dir)) {
                while (($file = readdir($dh)) !== false) {
                    if ($file != '.' and $file != '..') {
                        $files[] = array('name' => $file, 'type' => filetype($dir . $file));
                    }
                }
                closedir($dh);
            }
        }
    
        usort($files, 'InnoworkProject::filesListSort');
         
        return $files;
    }
    
    public function mkdir($dirname) {
        $dirname = $this->getBaseFolder().$dirname.'/';
         
        require_once('innomatic/io/filesystem/DirectoryUtils.php');
        DirectoryUtils::mktree($dirname, 0755);
    }
    
    public function addFile($path, $tmp_file, $name) {
        $dest_name = $this->getBaseFolder().$path.'/'.$name;
         
        $result = copy(
            $tmp_file,
            $dest_name
        );
    }
    
    /*
     public function checkDuplicateProtocol($path, $name)
     {
    $this->checkBaseFolder();
    $check_parts = explode(' ', $name);
     
    $dir = $this->getBaseFolder().$path.'/';
    
    if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
    while (($file = readdir($dh)) !== false) {
    if ($file != '.' and $file != '..') {
    $parts = explode(' ', $file);
    if ($parts[0] == $check_parts[0]) {
    closedir($dh);
    return true;
    }
    }
    }
    closedir($dh);
    }
    }
     
    return false;
    }
    */
    
    public function renameFile($path, $oldName, $newName) {
        return rename($this->getBaseFolder().$path.'/'.$oldName, $this->getBaseFolder().$path.'/'.$newName);
    }
    
    public function removeFile($file) {
        $filePath = $this->getBaseFolder().$file;
         
        if (is_dir($filePath)) {
            require_once('innomatic/io/filesystem/DirectoryUtils.php');
            DirectoryUtils::unlinkTree($filePath);
            return true;
        } else {
            if (file_exists($filePath)) {
                unlink($filePath);
                return true;
            }
    
            // File doesn't exist
            return false;
        }
    }
    
    public function getFileSize($file)
    {
        $stat = stat($this->getBaseFolder().$file);
        return $stat['size'];
    }
    
    public function downloadFile($file) {
        $file = $this->getBaseFolder().$file;
         
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Content-Transfer-Encoding: binary', true);
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();
            flush();
            readfile($file);
            InnomaticContainer::instance('innomaticcontainer')->halt();
        } else {
            return false;
        }
    }
    
    public static function filesListSort($a, $b)
    {
        if ($a['type'] == $b['type']) {
            // Natural sorting
            return strnatcmp($a['name'], $b['name']);
            /*
             // Not natural sorting
            if ($a['name'] == $b['name']) {
            return 0;
            }
            return ($a['name'] < $b['name']) ? -1 : 1;
            */
        }
         
        if ($a['type'] == 'dir' and $b['type'] == 'file') {
            return -1;
        }
    
        if ($a['type'] == 'file' and $b['type'] == 'dir') {
            return 1;
        }
    }
}
