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

/*!
 @class InnoworkItemTypeComponent
 @abstract InnoworkItemType element handler.
 */
class InnoworkItemTypeComponent extends ApplicationComponent
{
	var $type = 'innoworkitemtype';
	var $domain = true;

	/*!
	 @function InnoworkItemTypeComponent
	 */
	public function __construct($rootda, $domainda, $appname, $name, $basedir)
	{
		parent::__construct($rootda, $domainda, $appname, $name, $basedir);
	}

	public static function getType()
	{
		return 'innoworkitemtype';
	}

	public static function getPriority()
	{
		return 0;
	}

	public static function getIsDomain()
	{
		return true;
	}

	public static function getIsOverridable()
	{
		return false;
	}

	/*!
	 @function DoInstallAction
	 */
	function DoInstallAction($params)
	{
		$result = false;
		if (strlen($this->name)) {
			if (!isset($params['adminevent']) or !strlen($params['adminevent'])) {
				$params['adminevent'] = 'default';
			}

			$result = $this->rootda->Execute('INSERT INTO innowork_core_itemtypes VALUES ('.$this->rootda->getNextSequenceValue('innowork_core_itemtypes_id_seq').','.$this->rootda->formatText($this->name).','.$this->rootda->formatText($params['catalog']).','.$this->rootda->formatText($params['classfile']).','.$this->rootda->formatText($params['classname']).','.$this->rootda->formatText($params['icon']).','.$this->rootda->formatText($params['domainpanel']).','.$this->rootda->formatText($params['miniicon']).','.$this->rootda->formatText($params['summaryname']).','.$this->rootda->formatText($params['icontype']).','.$this->rootda->formatText($params['showmode']).','.$this->rootda->formatText($params['adminevent']).')');
		} else {
			$this->mLog->logEvent('innoworkcore.innoworkitemtypeelement.innoworkitemtypeelement.doinstallaction', 'In application '.$this->appname.', element '.$this->name.': Empty innowork type name', Logger::ERROR);
		}

		return $result;
	}

	/*!
	 @function DoUnInstallAction
	 */
	public function DoUnInstallAction($params)
	{
		$result = FALSE;

		if (strlen($this->name)) {
			$result = $this->rootda->Execute('DELETE FROM innowork_core_itemtypes WHERE itemtype='.$this->rootda->formatText($this->name));
		} else
		$this->mLog->logEvent('innoworkcore.innoworkitemtypeelement.innoworkitemtypeelement.douninstallaction', 'In application '.$this->appname.', element '.$this->name.': Empty innowork type name', Logger::ERROR);

		return $result;
	}

	/*!
	 @function DoUpdateAction
	 */
	public function DoUpdateAction($params)
	{
		$result = FALSE;

		if (strlen($this->name)) {
			if (!isset($params['adminevent']) or !strlen($params['adminevent']))
			$params['adminevent'] = 'default';

			$result = $this->rootda->Execute('UPDATE innowork_core_itemtypes SET catalog='.$this->rootda->formatText($params['catalog']).
            	',classfile='.$this->rootda->formatText($params['classfile']).
            	',icon='.$this->rootda->formatText($params['icon']).
            	',icontype='.$this->rootda->formatText($params['icontype']).
            	',miniicon='.$this->rootda->formatText($params['miniicon']).
            	',classname='.$this->rootda->formatText($params['classname']).
            	',domainpanel='.$this->rootda->formatText($params['domainpanel']).
            	',showmode='.$this->rootda->formatText($params['showmode']).
            	',adminevent='.$this->rootda->formatText($params['adminevent']).
            	',summaryname='.$this->rootda->formatText($params['summaryname']).
            	' WHERE itemtype='.$this->rootda->formatText($this->name));
		} else
		$this->mLog->logEvent('innoworkcore.innoworkitemtypeelement.innoworkitemtypeelement.doupdateaction', 'In application '.$this->appname.', element '.$this->name.': Empty innowork type name', Logger::ERROR);

		return $result;
	}

	/*!
	 @function DoEnableDomainAction
	 */
	public function doEnableDomainAction($params)
	{
		if (!strlen($this->name)) {
			$this->mLog->logEvent('innoworkcore.innoworkitemtypeelement.innoworkitemtypeelement.enable', 'In application '.$this->appname.', element '.$this->name.': Empty innowork type name', Logger::ERROR);
			return false;
		}
		return $this->domainda->Execute('INSERT INTO innowork_core_itemtypes_enabled VALUES ('.$this->domainda->formatText($this->name).')');;
	}

	/*!
	 @function DoDisableDomainAction
	 */
	public function doDisableDomainAction($params)
	{
		if (!strlen($this->name)) {
			$this->mLog->logEvent('innoworkcore.innoworkitemtypeelement.innoworkitemtypeelement.disable', 'In application '.$this->appname.', element '.$this->name.': Empty innowork type name', Logger::ERROR);
			return false;
		}

		$this->domainda->Execute('DELETE FROM innowork_core_itemtypes_enabled WHERE itemtype='.$this->domainda->formatText($this->name));
		$this->domainda->Execute('DELETE FROM innowork_core_acls WHERE itemtype='.$this->domainda->formatText($this->name));
		$this->domainda->Execute('DELETE FROM innowork_core_itemslog WHERE itemtype='.$this->domainda->formatText($this->name));

		return true;
	}

	/*!
	 @function DoUpdateDomainAction
	 */
	public function doUpdateDomainAction($params)
	{
		return TRUE;
	}
}
