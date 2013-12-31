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

require_once('innowork/core/InnoworkCore.php');
require_once('innomatic/domain/user/User.php');

/*!
 @class InnoworkAcl

 @abstract Access list handler.
 */
class InnoworkAcl {
	public $mrRootDb;
	public $mrDomainDA;
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

	/*!
	 @function InnoworkAcl
	 */
	public function __construct(\Innomatic\Dataaccess\DataAccess $rrootDb, \Innomatic\Dataaccess\DataAccess $rdomainDA, $itemType, $itemId,$ownerid=0) {
		$this->mItemType = $itemType;
		$this->mItemId = $itemId;
		$this->mrRootDb = $rrootDb;
		$this->mrDomainDA = $rdomainDA;
		$this->ownerid=$ownerid;
	}

	/*!
	 @function getOwner
	 @abstract Gets item owner id
	 */
	public function getOwner() {
		$result = 0;

		if (!$this->ownerid) {
			if ($this->mItemType and $this->mItemId) {
				$tmp_innoworkcore = InnoworkCore::instance('innoworkcore', \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess());
				$summaries = $tmp_innoworkcore->getSummaries();

				$class_name = $summaries[$this->mItemType]['classname'];
				// Checks if the class exists.
				if (!class_exists($class_name)) {
					return false;
				}
				$tmp_class = new $class_name(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess(), $this->mItemId);
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

		if ($this->mItemType and $this->mItemId) {
			$owner = $this->getOwner();

			if ($owner == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() or User::isAdminUser(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId())) {
				switch ($accessType) {
					case InnoworkAcl::TYPE_PRIVATE :
					case InnoworkAcl::TYPE_PUBLIC :
					case InnoworkAcl::TYPE_ACL :
						$acl_check = $this->mrDomainDA->execute('SELECT rights FROM innowork_core_acls WHERE itemid='.$this->mItemId.' AND itemtype='.$this->mrDomainDA->formatText($this->mItemType).' AND userid=0 AND groupid=0');

						if ($acl_check->getNumberRows()) {
							if ($acl_check->getFields('rights') != $accessType) {
								// Update the type
								//
								if ($this->mrDomainDA->execute('UPDATE innowork_core_acls SET rights='.$accessType.' WHERE itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType).' AND userid=0 AND groupid=0')) {
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
							if ($this->mrDomainDA->execute('INSERT INTO innowork_core_acls '.'VALUES ('.$this->mItemId.','.$this->mrDomainDA->formatText($this->mItemType).','."0,0,".$accessType.')')) {
								$result = true;
							}
						}
						$acl_check->Free();
						break;
				}

				if ($result) {
					$this->mAclType = $accessType;
					// Flush item type cache
					$this->CleanCache();
				}
			}
		}

		if ($result) $this->typechanged = true;
		return $result;
	}

	/*!
	 @function getType
	 @abstract Gets the ACL type.
	 */
	public function getType() {
		$result = false;

		if (!strlen($this->mAclType)) {
			$acl_check = $this->mrDomainDA->execute('SELECT rights FROM innowork_core_acls WHERE itemid='.$this->mItemId.' AND itemtype='.$this->mrDomainDA->formatText($this->mItemType).' AND userid=0 AND groupid=0');

			if ($acl_check->getNumberRows()) {
				$result = $this->mAclType = $acl_check->getFields('rights');
			}
			$acl_check->Free();
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

		switch ($type) {
			case InnoworkAcl::TYPE_PUBLIC :
				$result = InnoworkAcl::PERMS_ALL;
				break;

			case InnoworkAcl::TYPE_PRIVATE :
				if (\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName() == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId())
				$result = InnoworkAcl::PERMS_ALL;
				else
				$result = InnoworkAcl::PERMS_NONE; // Always NONE because the file owner should not issue the checkPermission() method call.
				break;

			case InnoworkAcl::TYPE_ACL :
				if (User::isAdminUser(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserName(), \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDomainId()))
				$result = InnoworkAcl::PERMS_ALL;
				else
				if (strlen($groupId) xor strlen($userId)) {
					$result = InnoworkAcl::PERMS_NONE;
					$goon = true;

					if ($userId) {
						
						if (!isset($GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'])) {
							$tmp_user = new User(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->domaindata['id'], $userId);
							$groupId = $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'] = $tmp_user->GetGroup();
						}
						else $groupId = $GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'];

						if (isset($GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'])) {
							$tmp_num_rows = $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights_rows'];
							$tmp_rights = $GLOBALS['innowork-core']['acl-checkperm'][$userId][$this->mItemType][$this->mItemId]['rights'];
						} else {
							$user_query = $this->mrDomainDA->execute('SELECT rights FROM innowork_core_acls WHERE userid='.$userId.' AND itemid='.$this->mItemId.' AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));
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
								$group_query = $this->mrDomainDA->execute('SELECT groupid FROM domain_users WHERE id='.$userId);
								$groupId = $group_query->getFields('groupid');
								$GLOBALS['innowork-core']['acl-checkperm'][$userId]['groupid'] = $groupId;
								$group_query->Free();
							}
						}

						if (isset($user_query))
						$user_query->Free();
					}

					if ($goon) {
						if ($groupId != '0') {
							$group_query = $this->mrDomainDA->execute('SELECT rights FROM innowork_core_acls WHERE groupid='.$groupId.' AND itemid='.$this->mItemId.' AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));

							if ($group_query->getNumberRows()) {
								$result = $group_query->getFields('rights');
							}

							$group_query->Free();
						} else
						$result = InnoworkAcl::PERMS_NONE;
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

		//if ( $userId == \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentUser()->getUserId() ) return true; //$permissions = InnoworkAcl::PERMS_ALL;

		if ($this->getType() == InnoworkAcl::TYPE_ACL) {
			if (strlen($groupId) xor strlen($userId)) {
				if ($userId) {
					$user_query = $this->mrDomainDA->execute('SELECT rights '.'FROM innowork_core_acls '.'WHERE userid='.$userId.' '.'AND itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));

					if ($user_query->getNumberRows() == 0) {
						$result = $this->mrDomainDA->execute('INSERT INTO innowork_core_acls '.'VALUES ('.$this->mItemId.','.$this->mrDomainDA->formatText($this->mItemType).','.'0,'.$userId.','.$permissions.')');
					} else {
						$result = $this->mrDomainDA->execute('UPDATE innowork_core_acls '.'SET rights='.$permissions.' '.'WHERE userid='.$userId.' '.'AND itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));
					}

					$user_query->Free();
				} else
				if ($groupId) {
					$group_query = $this->mrDomainDA->execute('SELECT rights '.'FROM innowork_core_acls '.'WHERE groupid='.$groupId.' '.'AND itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));

					if ($group_query->getNumberRows() == 0) {
						$result = $this->mrDomainDA->execute('INSERT INTO innowork_core_acls '.'VALUES ('.$this->mItemId.','.$this->mrDomainDA->formatText($this->mItemType).','.$groupId.','.'0,'.$permissions.')');
					} else {
						$result = $this->mrDomainDA->execute('UPDATE innowork_core_acls '.'SET rights='.$permissions.' '.'WHERE groupid='.$groupId.' '.'AND itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType));
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

			$this->CleanCache();
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
			if ($this->mrDomainDA->execute('DELETE FROM innowork_core_acls '.'WHERE itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType).' '.'AND '. ($groupId ? 'groupid='.$groupId : 'userid='.$userId)))
			$result = true;
		}

		if ($result) {
			$this->CleanCache();
		}

		return $result;
	}

	/*!
	 @function RemoveAllPerms

	 @abstract Removes all permissions.
	 */
	public function removeAllPermissions() {
		$result = false;

		if ($this->mrDomainDA->execute('DELETE FROM innowork_core_acls '.'WHERE itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType).' '.'AND groupid<>0 AND userid<>0'))
		$result = true;

		if ($result) {
			$this->CleanCache();
		}

		return $result;
	}

	/*!
	 @function Erase

	 @abstract Erase the access list for the current item.

	 @discussion It should be issued only when removing the associated item.
	 */
	public function erase() {
		$result = false;

		if ($this->mrDomainDA->execute('DELETE FROM innowork_core_acls '.'WHERE itemid='.$this->mItemId.' '.'AND itemtype='.$this->mrDomainDA->formatText($this->mItemType)))
		$result = true;

		if ($result) {
			$this->CleanCache();
		}
		return $result;
	}

	public function copyAcl($aclItemType, $aclItemId) {
		$acl_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('SELECT groupid,userid,rights '.'FROM innowork_core_acls '.'WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($aclItemType).' '.'AND itemid='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($aclItemId));
		if ($acl_query->getNumberRows()) {
			\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('DELETE FROM innowork_core_acls '.'WHERE itemtype='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).' '.'AND itemid='.$this->mItemId);

			while (!$acl_query->eof) {
				\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->execute('INSERT INTO innowork_core_acls VALUES ('.$this->mItemId.','.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain()->getDataAccess()->formatText($this->mItemType).','.$acl_query->getFields('groupid').','.$acl_query->getFields('userid').','.$acl_query->getFields('rights').')');
				$acl_query->MoveNext();
			}
		}
		$acl_query->free();
		// Flush item type cache
		$this->cleanCache();
		return true;
	}

	public function cleanCache() {
		$result = true;
		require_once('innomatic/datatransfer/cache/CachedItem.php');
		$cache_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->execute('SELECT itemid '.'FROM cache_items '.'WHERE application='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText('innowork-core').' '.'AND itemid LIKE '.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText('itemtypesearch-'.$this->mItemType.'%'));

		while (!$cache_query->eof) {
			$cached_item = new CachedItem(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), 'innowork-core', $cache_query->getFields('itemid'));
			$cached_item->destroy();
			$cache_query->moveNext();
		}
		$cache_query->Free();
		return $result;
	}
}

?>