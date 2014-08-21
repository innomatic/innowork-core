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

class InnoworkAcl {
    protected $container;
    protected $rootDA;
    protected $domainDA;
    public $mItemType;
    public $mItemId;
    public $mAclType = '';
    private $ownerid;

    const TYPE_PRIVATE = 1; // Only the owner can access it.
    const TYPE_PUBLIC = 2; // Everybody can access it.
    const TYPE_ACL = 3; // Access is defined by an access list.

    const PERMS_NONE = 0; // No access rights.
    const PERMS_SEARCH = 10; // Search rights.
    const PERMS_READ = 20; // Read only rights.
    const PERMS_EDIT = 30; // Read and write rights.
    const PERMS_DELETE = 40; // Read, write and delete rights.
    const PERMS_RESPONSIBLE = 100; // Responsible, all permissions.
    const PERMS_ALL = 100; // Alias for responsible.

    const ERROR_NOT_ENOUGH_PERMS = 'innoworkacl_noperms';

    public function __construct(
        \Innomatic\Dataaccess\DataAccess $rootDA,
        \Innomatic\Dataaccess\DataAccess $domainDA,
        $itemType,
        $itemId,
        $ownerid=0
    ) {
        $this->container = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer');

        $this->mItemType = $itemType;
        $this->mItemId   = $itemId;
        $this->rootDA    = $rootDA;
        $this->domainDA  = $domainDA;
        $this->ownerid   = $ownerid;
    }

    public function getOwner() {
        $result = 0;

        if (!$this->ownerid) {
            if ($this->mItemType and $this->mItemId) {
                $tmp_innoworkcore = InnoworkCore::instance(
                    '\Innowork\Core\InnoworkCore',
                    $this->rootDA,
                    $this->domainDA
                );

                $summaries = $tmp_innoworkcore->getSummaries();

                $class_name = $summaries[$this->mItemType]['classname'];
                // Checks if the class exists.
                if (!class_exists($class_name)) {
                    return false;
                }
                $tmp_class = new $class_name($this->rootDA, $this->domainDA, $this->mItemId);
                $result = $this->ownerid = $tmp_class->mOwnerId;
            }
        }

        return $result;
    }

    /*!
     @function setType

     @abstract Sets the ACL type.
     */
    public function setType($accessType) {
        $result = false;

        if (!($this->mItemType and $this->mItemId)) {
            return false;
        }

        $owner = $this->getOwner();

        if ($owner == $this->container->getCurrentUser()->getUserId()
            or User::isAdminUser($this->container->getCurrentUser()->getUserName(), $this->container->getCurrentDomain()->getDomainId())
        ) {
            switch ($accessType) {
            case InnoworkAcl::TYPE_PRIVATE :
            case InnoworkAcl::TYPE_PUBLIC :
            case InnoworkAcl::TYPE_ACL :
                $acl_check = $this->domainDA->execute(
                    'SELECT rights'
                    .' FROM innowork_core_acls'
                    .' WHERE itemid='.$this->mItemId
                    .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                    .' AND userid=0 AND groupid=0'
                );

                if ($acl_check->getNumberRows()) {
                    if ($acl_check->getFields('rights') != $accessType) {
                        // Update the type
                        //
                        if ($this->domainDA->execute(
                            'UPDATE innowork_core_acls'
                            .' SET rights='.$accessType
                            .' WHERE itemid='.$this->mItemId
                            .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                            .' AND userid=0 AND groupid=0'
                        )) {
                            // Remove the ACLs if the type is not ACL
                            if ($accessType != InnoworkAcl::TYPE_ACL) {
                                $this->removeAllPermissions();
                            }
                            $result = true;
                        }
                    }
                } else {
                    // Set the type
                    //
                    if ($this->domainDA->execute(
                        'INSERT INTO innowork_core_acls'
                        .' VALUES ('.$this->mItemId
                        .','.$this->domainDA->formatText($this->mItemType)
                        .','."0,0,"
                        .$accessType.')')
                    ) {
                        $result = true;
                    }
                }
                $acl_check->Free();
                break;
            }

            if ($result) {
                $this->mAclType = $accessType;
                // Flush item type cache
                $this->cleanCache();
            }
        }

        if ($result) {
            $this->typechanged = true;
        }

        return $result;
    }

    /*!
     @function getType
     @abstract Gets the ACL type.
     */
    public function getType() {
        $result = false;

        if (!strlen($this->mAclType)) {
            $acl_check = $this->domainDA->execute(
                'SELECT rights'
                .' FROM innowork_core_acls'
                .' WHERE itemid='.$this->mItemId
                .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                .' AND userid=0'
                .' AND groupid=0'
            );

            if ($acl_check->getNumberRows()) {
                $result = $this->mAclType = $acl_check->getFields('rights');
            }

            $acl_check->free();
        } else {
            $result = $this->mAclType;
        }

        return $result;
    }

    /*!
     @function CheckPerm
     @abstract Checks if the given group or user can access the item
     */
    public function checkPermission($groupId = '', $userId = '') {
        $result = false;
        $type = $this->getType();

        $tmp_innoworkcore = InnoworkCore::instance(
            '\Innowork\Core\InnoworkCore',
            $this->rootDA,
            $this->domainDA
        );
        $summaries = $tmp_innoworkcore->getSummaries();

        switch ($type) {
        case InnoworkAcl::TYPE_PUBLIC :
            $result = InnoworkAcl::PERMS_ALL;
            break;

        case InnoworkAcl::TYPE_PRIVATE :
            if (!$userId) {
                return InnoworkAcl::PERMS_NONE;
            }

            $tmp_user = new \Innomatic\Domain\User\User(
                $this->container->getCurrentDomain()->domaindata['id'],
                $userId
            );

            if (\Innomatic\Domain\User\User::isAdminUser(
                $tmp_user->getUserName(),
                $this->container->getCurrentDomain()->getDomainId()
            )
            or $tmp_user->hasPermission('view_all_'.$summaries[$this->mItemType]['typeplural'])) {
                $result = InnoworkAcl::PERMS_ALL;
            } else {
                // Always NONE because the file owner should not issue the
                // checkPermission() method call.
                $result = InnoworkAcl::PERMS_NONE;
            }
            break;

        case InnoworkAcl::TYPE_ACL :
            if (strlen($groupId) xor strlen($userId)) {
                $result = InnoworkAcl::PERMS_NONE;
                $goon = true;

                if ($userId) {
                    $tmp_user = new \Innomatic\Domain\User\User(
                        $this->container->getCurrentDomain()->domaindata['id'],
                        $userId
                    );

                    if (\Innomatic\Domain\User\User::isAdminUser($tmp_user->getUserName(),
                        $this->container->getCurrentDomain()->getDomainId())
                        or $tmp_user->hasPermission('view_all_'.$summaries[$this->mItemType]['typeplural'])) {
                            return InnoworkAcl::PERMS_ALL;
                        }

                    if (!isset($GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'])) {
                        $groupId = $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'] = $tmp_user->GetGroup();
                    } else {
                        $groupId = $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'];
                    }

                    if (isset($GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'])) {
                        $tmp_num_rows = $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'];
                        $tmp_rights = $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights'];
                    } else {
                        $user_query = $this->domainDA->execute(
                            'SELECT rights'
                            .' FROM innowork_core_acls'
                            .' WHERE userid='.$userId
                            .' AND itemid='.$this->mItemId
                            .' AND itemtype='
                            .$this->domainDA->formatText($this->mItemType)
                        );

                        $tmp_num_rows = $user_query->getNumberRows();
                        $tmp_rights = $user_query->getFields('rights');

                        $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'] = $tmp_num_rows;
                        $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights'] = $tmp_rights;
                    }

                    if ($tmp_num_rows) {
                        $goon = false;
                        $result = $tmp_rights;
                    } else {
                        if (isset($GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'])) {
                            $groupId = $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'];
                        } else {
                            // Check the user group rights
                            //
                            $group_query = $this->domainDA->execute(
                                'SELECT groupid'
                                .' FROM domain_users'
                                .' WHERE id='.$userId
                            );
                            $groupId = $group_query->getFields('groupid');
                            $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'] = $groupId;
                            $group_query->Free();
                        }
                    }

                    if (isset($user_query)) {
                        $user_query->free();
                    }
                }

                if ($goon) {
                    if ($groupId != '0') {
                        $group_query = $this->domainDA->execute(
                            'SELECT rights'
                            .' FROM innowork_core_acls'
                            .' WHERE groupid='.$groupId
                            .' AND itemid='.$this->mItemId
                            .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                        );

                        if ($group_query->getNumberRows()) {
                            $result = $group_query->getFields('rights');
                        }

                        $group_query->Free();
                    } else {
                        $result = InnoworkAcl::PERMS_NONE;
                    }
                }

                //$result = true;
            }
        }

        return $result;
    }

    /*!
     @function setPerm

     @abstract Sets an access list permission entry.
     */
    public function setPermission($groupId = '', $userId = '', $permissions = '') {
        $result = false;

        //if ( $userId == $this->container->getCurrentUser()->getUserId() ) return true; //$permissions = InnoworkAcl::PERMS_ALL;

        if ($this->getType() == InnoworkAcl::TYPE_ACL) {
            if (strlen($groupId) xor strlen($userId)) {
                if ($userId) {
                    $user_query = $this->domainDA->execute(
                        'SELECT rights'
                        .' FROM innowork_core_acls'
                        .' WHERE userid='.$userId
                        .' AND itemid='.$this->mItemId
                        .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                    );

                    if ($user_query->getNumberRows() == 0) {
                        $result = $this->domainDA->execute(
                            'INSERT INTO innowork_core_acls'
                            .' VALUES ('.$this->mItemId
                            .','.$this->domainDA->formatText($this->mItemType)
                            .','.'0,'.$userId
                            .','.$permissions.')'
                        );
                    } else {
                        $result = $this->domainDA->execute(
                            'UPDATE innowork_core_acls'
                            .' SET rights='.$permissions
                            .' WHERE userid='.$userId
                            .' AND itemid='.$this->mItemId
                            .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                        );
                    }

                    $user_query->Free();
                } elseif ($groupId) {
                    $group_query = $this->domainDA->execute(
                        'SELECT rights'
                        .' FROM innowork_core_acls'
                        .' WHERE groupid='.$groupId
                        .' AND itemid='.$this->mItemId
                        .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                    );

                    if ($group_query->getNumberRows() == 0) {
                        $result = $this->domainDA->execute(
                            'INSERT INTO innowork_core_acls'
                            .' VALUES ('.$this->mItemId
                            .','.$this->domainDA->formatText($this->mItemType)
                            .','.$groupId.','.'0,'
                            .$permissions.')'
                        );
                    } else {
                        $result = $this->domainDA->execute(
                            'UPDATE innowork_core_acls'
                            .' SET rights='.$permissions
                            .' WHERE groupid='.$groupId
                            .' AND itemid='.$this->mItemId
                            .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                        );
                    }

                    $group_query->Free();
                }

                $result = true;
            }
        }

        if ($result) {
            if (isset($GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'])) {
                unset($GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows']);
            }

            $this->cleanCache();
        }

        return $result;
        }

    /*!
     @function RemovePermission
     */
        public function removePermission($groupId = '', $userId = '') {
            $result = false;

            // No need to check if ACL type is set to ACL, it is safe the same

            if (strlen($groupId) xor strlen($userId)) {
                if ($this->domainDA->execute(
                    'DELETE FROM innowork_core_acls'
                    .' WHERE itemid='.$this->mItemId
                    .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                    .' AND '. ($groupId ? 'groupid='.$groupId : 'userid='.$userId))
                ) {
                    $result = true;
        }
        }

        if ($result) {
            $this->cleanCache();
        }

        return $result;
        }

    /*!
     @function RemoveAllPerms

     @abstract Removes all permissions.
     */
        public function removeAllPermissions() {
            $result = false;

            if ($this->domainDA->execute(
                'DELETE FROM innowork_core_acls'
                .' WHERE itemid='.$this->mItemId
                .' AND itemtype='.$this->domainDA->formatText($this->mItemType)
                .' AND groupid<>0 AND userid<>0')
            ) {
                $result = true;
        }

        if ($result) {
            $this->cleanCache();
        }

        return $result;
        }

    /*!
     @function Erase

     @abstract Erase the access list for the current item.

     @discussion It should be issued only when removing the associated item.
     */
        public function erase() {
            if ($this->domainDA->execute(
                'DELETE FROM innowork_core_acls'
                .' WHERE itemid='.$this->mItemId
                .' AND itemtype='.$this->domainDA->formatText($this->mItemType))
            ) {
                // Clean the cache
                $this->cleanCache();
                return true;
        } else {
            return false;
        }
        }

        public function copyAcl($aclItemType, $aclItemId) {
            $acl_query = $this->domainDA->execute(
                'SELECT groupid,userid,rights'
                .' FROM innowork_core_acls'
                .' WHERE itemtype='.$this->domainDA->formatText($aclItemType)
                .' AND itemid='.$this->domainDA->formatText($aclItemId)
            );

            if ($acl_query->getNumberRows()) {
                $this->domainDA->execute(
                    'DELETE FROM innowork_core_acls'
                    .' WHERE itemtype='.$this->domainDA->formatText($this->mItemType)
                    .' AND itemid='.$this->mItemId
                );

                while (!$acl_query->eof) {
                    $this->domainDA->execute(
                        'INSERT INTO innowork_core_acls'
                        .' VALUES ('.$this->mItemId
                        .','.$this->domainDA->formatText($this->mItemType)
                        .','.$acl_query->getFields('groupid')
                        .','.$acl_query->getFields('userid')
                        .','.$acl_query->getFields('rights').')'
                    );
                    $acl_query->moveNext();
        }
        }
        $acl_query->free();
        // Flush item type cache
        $this->cleanCache();
        return true;
        }

        public function cleanCache() {
            $cache_query = $this->rootDA->execute(
                'SELECT itemid'
                .' FROM cache_items'
                .' WHERE application='.$this->rootDA->formatText('innowork-core')
                .' AND itemid LIKE '.$this->rootDA->formatText('itemtypesearch-'.$this->mItemType.'%')
            );

            while (!$cache_query->eof) {
                $cached_item = new \Innomatic\Datatransfer\Cache\CachedItem(
                    $this->rootDA,
                    'innowork-core',
                    $cache_query->getFields('itemid')
                );
                $cached_item->destroy();
                $cache_query->moveNext();
        }
        $cache_query->free();
        return true;
        }
        }
