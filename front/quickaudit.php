<?php
/*
 -------------------------------------------------------------------------
 Asset Audit Plugin for GLPI
 Copyright (C) 2020 by Curtis Conard
 https://github.com/cconard96/glpi-assetaudit-plugin
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Asset Audit Plugin for GLPI.
 Asset Audit Plugin for GLPI is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Asset Audit Plugin for GLPI is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Asset Audit Plugin for GLPI. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

include('../../../inc/includes.php');

Session::checkRight(PluginAssetauditAudit::$rightname, READ);
Html::header('Asset Audit', '', 'plugins', PluginAssetauditAudit::class);

global $CFG_GLPI;

// Helper to check required parameters
$checkParams = static function($required) {
   foreach ($required as $param) {
      if (!isset($_REQUEST[$param])) {
         Toolbox::logError("Missing $param parameter");
         http_response_code(400);
         die();
      }
   }
};

if (isset($_REQUEST['choose_device'])) {
   $checkParams(['itemtype', 'id']);
   $item = new $_REQUEST['itemtype'];
   $item->getFromDB($_REQUEST['id']);
   PluginAssetauditAudit::showQuickAuditForm($item);
   //PluginAssetauditAudit::getItemInformationHtml($item);
} else if (isset($_REQUEST["search_item"]) && !empty($_REQUEST['searchnumber'])) {
   $found = PluginAssetauditAudit::quickAssetSearch($_REQUEST['searchnumber']);
   $found_count = 0;
   foreach ($found as $itemtype => $ids) {
      $found_count += count($ids);
   }
   if ($found_count === 0) {
      Session::addMessageAfterRedirect(__('No device found with the number:', 'assetaudit')." ".$_REQUEST['searchnumber'], false, WARNING);
      Html::back();
   } else if ($found_count > 1) {
      echo PluginAssetauditAudit::getAssetPickerHtml($found);
   } else {
      $itemtype = array_key_first($found);
      $item = new $itemtype;
      $item->getFromDB(array_key_first($found[$itemtype]));
      PluginAssetauditAudit::showQuickAuditForm($item);
   }
} else if (isset($_REQUEST['audit_success'])) {
   $checkParams(['itemtype', 'id']);
   PluginAssetauditAudit::completeAudit($_REQUEST['itemtype'], $_REQUEST['id'], $_REQUEST['data']);
   Html::redirect(PluginAssetauditAudit::getQuickAuditUrl(true));
} else if (isset($_REQUEST['create_ticket'])) {
   $checkParams(['itemtype', 'id']);
   Html::redirect(Ticket::getFormURL(true) . '?' . Toolbox::append_params([
      'itemtype'  => $_REQUEST['itemtype'],
      'items_id'  => $_REQUEST['id']
   ]));
} else {
   PluginAssetauditAudit::showQuickAuditForm();
}

Html::footer();