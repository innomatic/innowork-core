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

class InnoworkCoreHookHandler extends \Innomatic\Process\HookHandler {
	public static function domain_user_remove_userremoved($obj, $args) {
		$domain_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->execute('SELECT domainid FROM domains WHERE id='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText($args['domainserial']));
		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->startDomain($domain_query->getFields('domainid'));
		$tmp_domain = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain();
		//$tmp_site = new Domain(\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess(), $domain_query->getFields('domainid'), null);
		$tmp_domain->getDataAccess()->execute('DELETE FROM innowork_core_acls WHERE userid='.$args['userid']);
		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->stopDomain();
		return Hook::RESULT_OK;
	}

	public static function domain_group_remove_groupremoved($obj, $args) {
		$domain_query = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->execute('SELECT domainid FROM domains WHERE id='.\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getDataAccess()->formatText($args['domainserial']));
		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->startDomain($domain_query->getFields('domainid'));
		$tmp_domain = \Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->getCurrentDomain();
		$tmp_domain->getDataAccess()->execute('DELETE FROM innowork_core_acls WHERE groupid='.$args['groupid']);
		\Innomatic\Core\InnomaticContainer::instance('\Innomatic\Core\InnomaticContainer')->stopDomain();
		return Hook::RESULT_OK;
	}
}
