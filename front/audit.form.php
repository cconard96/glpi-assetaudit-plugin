<?php
/*
 -------------------------------------------------------------------------
 Asset Audit Plugin for GLPI
 Copyright (C) 2020-2021 by Curtis Conard
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

use Glpi\Event;

include('../../../inc/includes.php');

Session::checkRight(PluginAssetauditAudit::$rightname, READ);

if (empty($_GET["id"])) {
   $_GET["id"] = '';
}
if (!isset($_GET["withtemplate"])) {
   $_GET["withtemplate"] = '';
}

$audit = new PluginAssetauditAudit();
if (isset($_POST["add"])) {
   $audit->check(-1, CREATE, $_POST);

   if ($newID = $audit->add($_POST)) {
      if ($_SESSION['glpibackcreated']) {
         Html::redirect($audit->getLinkURL());
      }
   }
   Html::back();

} else if (isset($_POST["delete"])) {
   $audit->check($_POST["id"], DELETE);

   $audit->delete($_POST);
   $audit->redirectToList();

} else if (isset($_POST["restore"])) {
   $audit->check($_POST["id"], DELETE);

   $audit->restore($_POST);
   $audit->redirectToList();

} else if (isset($_POST["purge"])) {
   $audit->check($_POST["id"], PURGE);

   $audit->delete($_POST, 1);
   $audit->redirectToList();

} else if (isset($_POST["update"])) {
   $audit->check($_POST["id"], UPDATE);

   $audit->update($_POST);
   Html::back();

} else if (isset($_GET['_in_modal'])) {
   Html::popHeader(PluginAssetauditAudit::getTypeName(1), $_SERVER['PHP_SELF']);
   $audit->showForm($_GET["id"], ['withtemplate' => $_GET["withtemplate"]]);
   Html::popFooter();

} else {
   Html::header(PluginAssetauditAudit::getTypeName(1), $_SERVER['PHP_SELF'], 'plugins', PluginAssetauditAudit::class);
   $audit->display([
      'id'           => $_GET["id"],
      'withtemplate' => $_GET["withtemplate"],
      'formoptions'  => "data-track-changes=true"
   ]);

   Html::footer();
}
