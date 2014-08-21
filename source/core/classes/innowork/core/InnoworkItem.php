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

/**
 * InnoworkItem abstract class.
 *
 * This is the base class for all Innowork item types.
 *
 * @abstract
 * @copyright Copyright (c) 2002-2014 the Initial Developer. All rights reserved.
 * @author Alex Pagnoni <alex.pagnoni@innomatic.io>
 * @license MPL 1.1 {@link http://www.mozilla.org/MPL/}
 */
abstract class InnoworkItem
{
    // InnoworkItem defined vars

    /**
     * Innomatic Container instance
     *
     * @var \Innomatic\Core\InnomaticContainer
     * @access protected
     */
    protected $container;

    /**
     * Innomatic root data access.
     *
     * @var \Innomatic\Dataaccess\DataAccess
     * @access public
     */
    public $mrRootDb;

    /**
     * Domain data access.
     *
     * Domain data access must be explicitly given since Innowork supports
     * accessing object from other tenants.
     *
     * @var \Innomatic\Dataaccess\DataAccess
     * @access public
     */
    public $mrDomainDA;

    /**
     * Flag to specify if item changes should not be logged.
     *
     * Defaults to false, item changes are logged.
     *
     * @var bool
     * @access public
     */
    public $mNoLog = false;

    /**
     * Flag to specify if the item should not use ACLs.
     *
     * Defaults to false, ACL is supported.
     *
     * @var bool
     * @access public
     */
    public $mNoAcl = false;

    /**
     * Item ACL object, if supported.
     *
     * @var InnoworkACL
     * @access public
     */
    public $mAcl;

    /**
     * Last error id number.
     *
     * @var mixed
     * @access public
     */
    public $mLastError;

    /**
     * User id number of the item owner.
     *
     * @var integer
     * @access public
     */
    public $mOwnerId;

    /**
     * Item creation date.
     *
     * @var string timestamp
     * @access public
     */
    public $mCreated;

    const SEARCH_RESTRICT_NONE = 0;
    const SEARCH_RESTRICT_TO_OWNER = 1;
    const SEARCH_RESTRICT_TO_RESPONSIBLE = 2;
    const SEARCH_RESTRICT_TO_PARTICIPANT = 3;

    // Extension class defined vars

    /**
     * Item type name of the item parent, if supported.
     *
     * @var string
     * @access public
     */
    public $mParentType = '';

    /**
     * Name of the table field containing the parent id, if supported and defined.
     *
     * The field must be declared in $mViewableSearchResultKeys array.
     * The parent item type must support ACLs ($mNoAcl must be false).
     *
     * @var string
     * @access public
     */
    public $mParentIdField = '';

    /**
     * Item id number of item parent, if supported and defined
     *
     * @var integer
     * @access public
     */
    public $mParentId = 0;

    /**
     * Item type name.
     *
     * @var string
     * @access public
     */
    public $mItemType;

    /**
     * Item id number.
     *
     * @var integer
     * @access public
     */
    public $mItemId;

    // To be explicitly defined by the extension class

    /**
     * Item type database table name.
     *
     * @var string
     * @access public
     */
    public $mTable;

    /**
     * Array of searchable item keys.
     *
     * Supported types:
     * - integer
     * - text
     * - boolean
     * - timestamp
     * - table (relation to an external table)
     * - userid (the search result is translated to an Innomatic user in the search widget)
     *
     * @var array
     * @access public
     */
    public $mKeys;

    /**
     * True if the item type is searchable.
     *
     * Defaults to true, the item type is searchable.
     * @var bool
     * @access public
     */
    public $mSearchable = true;

    /**
     * Array of the search result keys.
     *
     * @var array
     * @access public
     */
    public $mSearchResultKeys;

    /**
     * Array of the viewable search result keys.
     *
     * @var array
     * @access public
     */
    public $mViewableSearchResultKeys;

    /**
     * Key names on which the search results should be sorted, separated by a comma.
     *
     * Defaults to id field.
     *
     * @var string
     * @access public
     */
    public $mSearchOrderBy = 'id';

    /**
     * Set to true if the search can be performed by the internal generic search engine.
     *
     * Defaults to true, the internal search engine is used.
     * @var bool
     * @access public
     */
    public $mSimpleSearch = true;

    /**
     * WUI event dispatcher name for show item action.
     *
     * @var string
     * @access public
     */
    public $mShowDispatcher = 'view';

    /**
     * WUI event dispatcher action name for show item action.
     *
     * @var string
     * @access public
     */
    public $mShowEvent = 'show';

    /**
     * WUI event dispatcher name for new item action.
     *
     * @var string
     * @access public
     */
    public $mNewDispatcher = 'view';

    /**
     * WUI event dispatcher action for new item action.
     *
     * @var bool
     * @access public
     */
    public $mNewEvent = 'new';

    /**
     * Array of fields to look for when searching for related items.
     *
     * @var array
     * @access public
     */
    public $mRelatedItemsFields = array();

    /**
     * Set to true if the item is not trashable.
     *
     * Default to true, item is trashable.
     *
     * @var bool
     * @access public
     */
    public $mNoTrash = true;

    /**
     * Array for mapping item's own fields to generic ones.
     *
     * mConvertible property must be set to true in order to work.
     *
     * @var array
     * @access public
     */
    public $mGenericFields = array();

    /**
     * True if the item accepts to be converted from or to another item.
     *
     * Defaults to false, item is not convertible.
     *
     * @var bool
     * @access public
     */
    public $mConvertible = false;

    /**
     * Array of tags supported by this item type, eg. task, invoice, project, etc.
     * @var array
     */
    public $mTypeTags = array();

    /**
     * File system base path for item files repository.
     *
     * @var string
     * @access public
     */
    public $mFsBasePath;

    /* public __construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA, $itemType, $itemId = 0) {{{ */
    /**
     * Item constructor.
     *
     * @param \Innomatic\Dataaccess\DataAccess $rrootDb Innomatic root data
     * access.
     * @param \Innomatic\Dataaccess\DataAccess $rdomainDA Domain data access.
     * @param string $itemType Item type name
     * @param int $itemId Item identifier number
     * @access public
     * @return void
     */
    public function __construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA, $itemType, $itemId = 0)
    {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');

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
        if (!strlen($this->mSearchOrderBy)) {
            $this->mSearchOrderBy = 'id';
        }

        // Search result keys
        $this->mSearchResultKeys[] = 'id';
        $this->mSearchResultKeys[] = 'ownerid';
        if ($this->mNoTrash == false) {
            // If the item type supports trash action, add trashed field to the
            // search result keys
            $this->mSearchResultKeys[] = 'trashed';
        }

        // If this item doesn't support ACLs and there is no defined default
        // creation ACL, set the item as public.
        if ($this->mNoAcl and !isset($this->_mCreationAcl)) {
            $this->_mCreationAcl = InnoworkAcl::TYPE_PUBLIC;
        }

        // Check if the item id is valid
        if ($this->mItemId) {
            // Extract parent id field, if supported
            $parentField = '';
            if (strlen($this->mParentType) > 0 && strlen($this->mParentIdField) > 0) {
                $parentField = ", {$this->mParentIdField} ";
            }

            $check_query = $this->mrDomainDA->execute("SELECT ownerid $parentField FROM ".$this->mTable.' WHERE id='.$this->mItemId);
            if ($check_query->getNumberRows()) {
                // Get owner id
                //
                $this->mOwnerId = $check_query->getFields('ownerid');

                // Extract parent id, if supported
                if (strlen($this->mParentType) > 0 && strlen($this->mParentIdField) > 0) {
                    $this->mParentId = $check_query->getFields($this->mParentIdField);
                }

            } else {
                $log = $this->container->getLogger();
                $log->logEvent('innoworkcore.innoworkcore.innoworkitem.innoworkitem', 'Invalid item id '.$this->mItemId.' from '.$this->mItemType.' item type handler', \Innomatic\Logging\Logger::WARNING);
                $this->mItemId = 0;
            }
            $check_query->Free();
        }

        // Item ACL
        if (strlen($this->mParentType) and $this->mParentId > 0) {
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
            $this->mFsBasePath = $this->container->getCurrentDomain()->getHome().'files/'.$this->getItemTypePlural().'/'.$this->mItemId.'/';
        }
    }
    /* }}} */

    /* public create($params, $userId = '') {{{ */
    /**
     * Creates a new item.
     *
     * This method supports a hook called "innowork.item.create" with two
     * events:
     * - startcall: called before creating the item
     * - endcall:   called after the item has been created
     *
     * @param array $params Array of item properties.
     * @param string $userId User identifier number of the owner, or null if
     * the current user id shoul be used.
     * @access public
     * @return boolean
     */
    public function create($params, $userId = '')
    {
        $result = false;
        $hook = new \Innomatic\Process\Hook($this->mrRootDb, 'innowork-core', 'innowork.item.create');

        // Call startcall hook
        if (!($this->mItemId == 0 && $hook->callHooks('startcall', $this, array('params' => $params, 'userid' => $userId)) == \Innomatic\Process\Hook::RESULT_OK)) {
            return false;
        }

        if (!strlen($userId)) {
            $userId = $this->container->getCurrentUser()->getUserId();
        }

        // Execute the create action
        $item_id = $this->doCreate($params, $userId);

        if ($item_id) {
            // Assign the item id
            $this->mItemId = $item_id;
            // Assign the owner id
            $this->mOwnerId = $userId;
            // Add the creation time
            $this->mCreated = time();

            // Item folder in filesystem
            $this->mFsBasePath = $this->container->getCurrentDomain()->getHome().'files/'.$this->getItemTypePlural().'/'.$this->mItemId.'/';

            if (!strlen($this->mParentType)) {
                // This item has no parent item type

                // Assign the item id to the ACL
                $this->mAcl->mItemId = $item_id;

                if (!isset($this->_mSkipAclSet)) {
                    if (isset($this->_mCreationAcl)) {
                        // If this item type has a preset creation ACL, set
                        // it in place of the custom defined one
                        $this->mAcl->SetType($this->_mCreationAcl);
                    } else {
                        // Check if this item type has an user defined
                        // preset ACL at item type level
                        $check_query = $this->mrDomainDA->execute(
                            'SELECT * FROM innowork_core_acls_defaults'
                            .' WHERE ownerid='.$this->mOwnerId
                            .' AND itemtype='.$this->mrDomainDA->formatText($this->mItemType)
                        );

                        if ($check_query->getNumberRows()) {
                            // Copy the user defined preset item type ACL
                            $this->mAcl->copyAcl('defaultaclitem', $check_query->getFields('id'));
                        } else {
                            // Set the item as private
                            $this->mAcl->SetType(InnoworkAcl::TYPE_PRIVATE);
                        }
                    }
                }
            } else {
                // This item has a parent item type
                require_once('innowork/core/InnoworkCore.php');
                $core = InnoworkCore::instance('innoworkcore', $this->mrRootDb, $this->mrDomainDA);
                $summaries = $core->getSummaries();
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
                $log->logChange($this->container->getCurrentUser()->getUserName());
            }

            // Flush item type cache
            $this->cleanCache();
            $result = true;
        }

        if ($hook->callHooks('endcall', $this, array('params' => $params, 'userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            $result = false;
        }

        return $result;
    }
    /* }}} */

    /* protected doCreate($params, $userId) {{{ */
    /**
     * Add item to the database.
     *
     * This method must be extended, if not returns false and the item is not
     * added to the database.
     *
     * @param mixed $params Array of item properties.
     * @param mixed $userId Creator user identifier number.
     * @access protected
     * @return boolean
     */
    protected function doCreate($params, $userId)
    {
        return false;
    }
    /* }}} */

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
                $userId = $this->container->getCurrentUser()->getUserId();
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

        // Retrieve the item data from the database
        $item_query = $this->mrDomainDA->execute(
            'SELECT * FROM '.$this->mTable.
            ' WHERE id='.$this->mItemId
        );

        // Build the data array
        if (is_object($item_query) && $item_query->getNumberRows()) {
            $result = $item_query->getFields();

            $item_query->free();
        }

        return $result;
    }

    /**
     * Gets item type identifier.
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

    /**
     * Gets current item identifier number.
     *
     * @return integer
     */
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
        $hook = new \Innomatic\Process\Hook($this->mrRootDb, 'innowork-core', 'innowork.item.edit');

        if (!($this->mItemId && $hook->callHooks('startcall', $this, array('params' => $params, 'userid' => $userId)) == \Innomatic\Process\Hook::RESULT_OK)) {
            return false;
        }

        if (!strlen($userId)) {
            $userId = $this->container->getCurrentUser()->getUserId();
        }

        if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_EDIT) {
            $result = $this->doEdit($params, $userId);

            if ($result) {
                if (!$this->mNoLog) {
                    require_once('innowork/core/InnoworkItemLog.php');
                	$log = new InnoworkItemLog($this->mItemType, $this->mItemId);
                    $log->LogChange($this->container->getCurrentUser()->getUserName());
                }

                // Flush item type cache
                $this->cleanCache();
            }
        } else {
            $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
        }

        if ($hook->callHooks('endcall', $this, array('params' => $params, 'userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            $result = false;
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
        $hook = new \Innomatic\Process\Hook($this->mrRootDb, 'innowork-core', 'innowork.item.remove');

        if ($this->mItemId && $hook->callHooks('startcall', $this, array('userid' => $userId)) == \Innomatic\Process\Hook::RESULT_OK) {
            if (!strlen($userId)) {
                $userId = $this->container->getCurrentUser()->getUserId();
            }

            if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
                $result = $this->doRemove($userId);

                if ($result) {
                    // Remove ACL
                    $this->mAcl->erase();

                    // Remove item from clippings
                    $this->mrDomainDA->execute(
                        'DELETE FROM innowork_core_clippings_items'
                        .' WHERE itemtype='.$this->mrDomainDA->formatText($this->mItemType)
                        .' AND itemid='.$this->mItemId
                    );

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

            if ($hook->callHooks('endcall', $this, array('userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
                $result = false;
            }
        }

        return $result;
    }

    protected function doRemove($userId)
    {
        return false;
    }

    /* public trash($userId = '') {{{ */
    /**
     * Trashes the current item.
     *
     * A trashed item can be restored with the restore() method.
     *
     * This method supports a hook called "innowork.item.trash" with two
     * events:
     * - startcall: called before trashing the item
     * - endcall:   called after the item has been trashed
     *
     * @param integer $userId User identifier number of the owner, or null
     * if the current user id should be used.
     * @return boolean
     */
    public function trash($userId = '')
    {
        $result = false;
        $hook = new \Innomatic\Process\Hook($this->mrRootDb, 'innowork-core', 'innowork.item.trash');

        // Call startcall hooks
        if ($this->mItemId and $this->mNoTrash == false && $hook->callHooks('startcall', $this, array('userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            return false;
        }

        // If no user id has been given, use the current user one
        if (!strlen($userId)) {
            $userId = $this->container->getCurrentUser()->getUserId();
        }

        // If the current user has enough ACL permissions, trash the item
        if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
            $result = $this->doTrash($userId);

            if ($result) {
                // Update the item table and set the item as trashed
                $result = $this->mrDomainDA->execute(
                    'UPDATE '.$this->mTable.
                    ' SET trashed='.$this->mrDomainDA->formatText($this->mrDomainDA->fmttrue).
                    ' WHERE id='.$this->mItemId
                );

                // Flush item type cache
                $this->cleanCache();
            }
        } else {
            $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
        }

        // Call endcall hooks
        if ($hook->callHooks('endcall', $this, array('userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            return false;
        }

        return $result;
    }
    /* }}} */

    /* protected doTrash($userId) {{{ */
    /**
     * Executes the trash action.
     *
     * This method must be extended, if not returns false and the item is not
     * set as trashed in the database.
     *
     * @param integer $userId User identifier number.
     * @access protected
     * @return boolean
     */
    protected function doTrash($userId)
    {
        return false;
    }
    /* }}} */

    public function restore($userId = '')
    {
        $result = false;
        $hook = new \Innomatic\Process\Hook($this->mrRootDb, 'innowork-core', 'innowork.item.restore');

        // Call startcall hooks
        if ($this->mItemId and $this->mNoTrash == false && $hook->callHooks('startcall', $this, array('userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            return false;
        }

        // If no user id has been give, user the current user one
        if (!strlen($userId)) {
            $userId = $this->container->getCurrentUser()->getUserId();
        }

        // If the user has enough ACL permissions, restore the item
        if ($this->mNoAcl == true or $userId == $this->mOwnerId or $this->mAcl->checkPermission('', $userId) >= InnoworkAcl::PERMS_DELETE) {
            $result = $this->doRestore($userId);

            if ($result) {
                // Set the item as not trashed
                $result = $this->mrDomainDA->execute(
                    'UPDATE '.$this->mTable.
                    ' SET trashed='.$this->mrDomainDA->formatText($this->mrDomainDA->fmtfalse).
                    ' WHERE id='.$this->mItemId
                );

                // Clean item cache
                $this->cleanCache();
            }
        } else {
            $this->mLastError = InnoworkAcl::ERROR_NOT_ENOUGH_PERMS;
        }

        // Call endcall hooks
        if ($hook->callHooks('endcall', $this, array('userid' => $userId)) != \Innomatic\Process\Hook::RESULT_OK) {
            return false;
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
            $cached_item = new \Innomatic\Datatransfer\Cache\CachedItem($this->mrRootDb, 'innowork-core', 'itemtypesearch-'.$this->mItemType.strtolower(str_replace(' ', '', $this->mSearchOrderBy)), $this->container->getCurrentDomain()->domaindata['id'], $this->container->getCurrentUser()->getUserId());
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
                $userId = $this->container->getCurrentUser()->getUserId();
            }
            $result = array();

            // Call the search method
            //
            $search_result = $this->doSearch($searchKeys, $userId, $globalSearch, $trashcan, $limit, $offset);


            if (strlen($this->mParentType) > 0 && strlen($this->mParentIdField) > 0) {
                require_once('innowork/core/InnoworkCore.php');
                $tmp_innoworkcore = InnoworkCore::instance(
                    'innoworkcore',
                    $this->mrRootDb,
                    $this->mrDomainDA
                );

                $summaries = $tmp_innoworkcore->getSummaries();
                $parentTable = $summaries[$this->mParentType]['table'];
            }

            // Check if the user has enough permissions for each row in the result set,
            // and add the ones with enough permissions
            //
            if (is_array($search_result) and count($search_result)) {
                while (list ($id, $val) = each($search_result)) {
                    // Get the item ACL or the item parent ACL if supported
                    if (strlen($this->mParentType) > 0 && strlen($this->mParentIdField) > 0) {
                        $aclItemId = $val[$this->mParentIdField];
                        $aclItemType = $this->mParentType;

                        $aclItemOwnerId = '';
                        if (strlen($parentTable)) {
                            $parentOwnerQuery = $this->mrDomainDA->execute(
                                "SELECT ownerid FROM $parentTable WHERE id=$aclItemId"
                            );
                            if ($parentOwnerQuery->getNumberRows() > 0) {
                                $aclItemOwnerId = $parentOwnerQuery->getFields('ownerid');
                            }
                        }
                        $aclNoAcl = false;
                    } else {
                        $aclItemId = $id;
                        $aclItemType = $this->mItemType;
                        $aclItemOwnerId = $val['ownerid'];
                        $aclNoAcl = $this->mNoAcl;
                    }

                    $tmp_acl = new InnoworkAcl($this->mrRootDb, $this->mrDomainDA, $aclItemType, $aclItemId);

                    if ($aclNoAcl == true or $aclItemOwnerId == $this->container->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) >= InnoworkAcl::PERMS_SEARCH) {
                        $restrict = false;

                        switch ($restrictToPermission) {
                            case InnoworkItem::SEARCH_RESTRICT_TO_OWNER :
                                if ($aclItemOwnerId != $this->container->getCurrentUser()->getUserId())
                                    $restrict = true;
                                break;

                            case InnoworkItem::SEARCH_RESTRICT_TO_RESPONSIBLE :
                                $restrict = true;
                                if ($aclItemOwnerId == $this->container->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) == InnoworkAcl::PERMS_RESPONSIBLE)
                                    $restrict = false;
                                break;

                            case InnoworkItem::SEARCH_RESTRICT_TO_PARTICIPANT :
                                if ($aclItemOwnerId == $this->container->getCurrentUser()->getUserId() or $tmp_acl->checkPermission('', $userId) >= InnoworkAcl::PERMS_ALL)
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
                            case 'userid':
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
                                case 'userid':
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
                    $search_query->moveNext();
                }
                $search_query->free();
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
                $this->mrRootDb,
                $this->mrDomainDA
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
            $tmp_innoworkcore = InnoworkCore::instance('innoworkcore', $this->mrRootDb, $this->mrDomainDA);
            $summaries = $tmp_innoworkcore->getSummaries();
            $class_name = $summaries[$type]['classname'];
            if (!class_exists($class_name)) {
                return false;
            }
            $tmp_class = new $class_name(
            	$this->mrRootDb,
            	$this->mrDomainDA
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
            if (strlen($this->mGenericFields['companyid'])) {
                $real_data[$this->mGenericFields['companyid']] = $genericData['companyid'];
            }

            if (strlen($this->mGenericFields['projectid'])) {
                $real_data[$this->mGenericFields['projectid']] = $genericData['projectid'];
            }

            if (strlen($this->mGenericFields['title'])) {
                $real_data[$this->mGenericFields['title']] = $genericData['title'];
            }

            if (strlen($this->mGenericFields['content'])) {
                $real_data[$this->mGenericFields['content']] = $genericData['content'];
            }

            if (strlen($this->mGenericFields['binarycontent'])) {
                $real_data[$this->mGenericFields['binarycontent']] = $genericData['binarycontent'];
            }

            $result = $this->create($real_data);
        }
        return $result;
    }

    /* public cleanCache() {{{ */
    /**
     * Clears the current item cache.
     *
     * @access public
     * @return boolean
     */
    public function cleanCache()
    {
        $cache_query = $this->mrRootDb->execute(
            'SELECT itemid'.
            ' FROM cache_items'.
            ' WHERE application='.$this->mrRootDb->formatText('innowork-core').
            ' AND itemid LIKE '.$this->mrRootDb->formatText('itemtypesearch-'.$this->mItemType.'%')
        );

        while (!$cache_query->eof) {
            $cached_item = new \Innomatic\Datatransfer\Cache\CachedItem(
                $this->mrRootDb,
                'innowork-core',
                $cache_query->getFields('itemid')
            );
            $cached_item->destroy();
            $cache_query->moveNext();
        }

        $cache_query->free();
        return true;
    }
    /* }}} */

    /* public lock() {{{ */
    /**
     * Locks the current item.
     *
     * Locks is only meant for write actions like change, while an item should
     * be anyway accessible in read only mode when locked.
     *
     * @todo The system should support automatic expiry of locks after n
     * seconds, and an optional alert to the user (eg. a javascript alert).
     *
     * @todo This method should optionally accept a max lock time in seconds.
     *
     * @access public
     * @return void
     */
    public function lock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->setRed();
    }
    /* }}} */

    /* public unlock() {{{ */
    /**
     * Unlocks the current item.
     *
     * @access public
     * @return void
     */
    public function unlock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->setGreen();
    }
    /* }}} */

    /* public isLocked() {{{ */
    /**
     * Checks if the current item is locked.
     *
     * @access public
     * @return boolean
     */
    public function isLocked()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        return $sem->checkStatus() == Semaphore::STATUS_RED ? true : false;
    }
    /* }}} */

    /* public waitLock() {{{ */
    /**
     * Waits until the item has been unlocked by another instance.
     *
     * This action is potentially dangerous since it may block user interface
     * until the lock has been released.
     *
     * @todo Should accept an optional argument to override the lock and return
     * false if the lock is not returned by x seconds.
     *
     * @access public
     * @return void
     */
    public function waitLock()
    {
        $sem = new \Innomatic\Process\Semaphore('innoworkitem_'.$this->mItemType, $this->mItemId);
        $sem->waitGreen();
    }
    /* }}} */

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
            $this->container->halt();
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
