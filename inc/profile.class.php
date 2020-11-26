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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Adds plugin related rights tab to Profiles.
 * @since 1.0.0
 */
class PluginAssetauditProfile extends Profile
{

   public static $rightname = "config";

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      return self::createTabEntry(__('Asset Audit', 'assetaudit'));
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      $profile = new self();
      $profile->showForm($item->getID());
      return true;
   }

   /**
    * Print the Jamf plugin right form for the current profile
    *
    * @param int $profiles_id Current profile ID
    * @param bool $openform Open the form (true by default)
    * @param bool $closeform Close the form (true by default)
    *
    * @return bool|void
    */
   public function showForm($profiles_id = 0, $openform = true, $closeform = true)
   {
      if (!self::canView()) {
         return false;
      }

      $canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE, PURGE]);
      echo "<div class='spaced'>";
      $profile = new Profile();
      $profile->getFromDB($profiles_id);
      if ($canedit && $openform) {
         echo "<form method='post' action='" . $profile::getFormURL() . "'>";
      }

      $rights = [
         [
            'itemtype' => PluginAssetauditAudit::class,
            'label' => PluginAssetauditAudit::getTypeName(Session::getPluralNumber()),
            'field' => PluginAssetauditAudit::$rightname
         ],
      ];
      $matrix_options['title'] = __('Asset Audit', 'assetaudit');
      $profile->displayRightsChoiceMatrix($rights, $matrix_options);

      if ($canedit && $closeform) {
         echo "<div class='center'>";
         echo Html::hidden('id', ['value' => $profiles_id]);
         echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
         echo "</div>\n";
         Html::closeForm();
      }
      echo '</div>';
   }
}
