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

require_once('innomatic/wui/Wui.php');
if ((isset(Wui::instance('wui')->parameters['wui']['wui']['evn']) and (Wui::instance('wui')->parameters['wui']['wui']['evn'] == 'innoworkitemopen' or Wui::instance('wui')->parameters['wui']['wui']['evn'] == 'innoworkitemclose'))) {
    switch (Wui::instance('wui')->parameters['wui']['wui']['evn']) {
        case 'innoworkitemopen' :
            require_once('shared/wui/WuiSessionkey.php');
            $innowork_item_sk = new WuiSessionKey('innowork_itemtypesummary_'.Wui::instance('wui')->parameters['wui']['wui']['evd']['innoworkitemtype'].'_closed', array('value' => '0'));
            break;

        case 'innoworkitemclose' :
            require_once('shared/wui/WuiSessionkey.php');
            $innowork_item_sk = new WuiSessionKey('innowork_itemtypesummary_'.Wui::instance('wui')->parameters['wui']['wui']['evd']['innoworkitemtype'].'_closed', array('value' => '1'));
            break;
    }
}

?>
