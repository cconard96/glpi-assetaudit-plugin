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

class PluginAssetauditAudit_Item extends CommonDBTM {

   public static $rightname = 'plugin_assetaudit_audit';

   public const AUDIT_ITEM_STATUS_NOTSTARTED = 0;

   public const AUDIT_ITEM_STATUS_FAILED = 1;

   public const AUDIT_ITEM_STATUS_PASSED = 2;

   public const AUDIT_ITEM_STATUS_REMEDIATED = 3;

   public static function getTypeName($nb = 0)
   {
      return _n('Audit item', 'Audit items', $nb, 'assetaudit');
   }

   public static function getIcon()
   {
      return 'fas fa-clipboard-check';
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item instanceof PluginAssetauditAudit) {
         return self::getTypeName(Session::getPluralNumber());
      }
      return false;
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item instanceof PluginAssetauditAudit) {
         self::showForAudit($item);
      }
      return false;
   }

   public static function showForAudit(PluginAssetauditAudit $audit)
   {
      global $DB, $CFG_GLPI;

      echo '<form action="'.self::getFormURL().'" method="POST">';
      echo Html::hidden(PluginAssetauditAudit::getForeignKeyField(), ['value' => $audit->getID()]);
      Dropdown::showSelectItemFromItemtypes([
         'itemtypes'       => $CFG_GLPI['asset_types'],
         'entity_restrict' => $audit->fields['entities_id']
      ]);
      echo Html::submit(__('Add'), ['name' => 'add']);
      echo Html::closeForm(false);

      $iterator = $DB->request([
         'SELECT' => ['itemtype', 'items_id', 'audit_status'],
         'FROM'   => self::getTable(),
         'WHERE'  => [PluginAssetauditAudit::getForeignKeyField() => $audit->getID()]
      ]);
      $items = [];
      while ($data = $iterator->next()) {
         $items[$data['itemtype']][$data['items_id']] = $data;
      }
      $audited_itemtypes = array_keys($items);
      foreach ($audited_itemtypes as $audited_itemtype) {
         $iterator2 = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => $audited_itemtype::getTable(),
            'WHERE'  => ['id' => array_keys($items[$audited_itemtype])]
         ]);
         while ($data = $iterator2->next()) {
            $items[$audited_itemtype][$data['id']]['name'] = $data['name'];
         }
      }

      echo '<table class="tab_cadre_fixe"><thead>';
      echo '<tr><th>'.__('Item type').'</th><th>'.__('Item ID').'</th><th>'.__('Name').'</th><th>'.__('Status').'</th></tr></thead>';

      foreach ($items as $itemtype => $iitem) {
         foreach ($iitem as $item) {
            echo "<tr><td>{$itemtype}</td><td>{$item['items_id']}</td><td>{$item['name']}</td><td>";
            echo self::getStatusName($item['audit_status']);
            if ($item['audit_status'] === self::AUDIT_ITEM_STATUS_NOTSTARTED) {
               echo '<button class="vsubmit" name="audit">'.__('Audit', 'assetaudit').'</button>';
            } else {
               echo '<button class="vsubmit" name="reaudit">'.__('Re-audit', 'assetaudit').'</button>';
            }
            echo '</td></tr>';
         }
      }

      echo '</table>';

      return true;
   }

   public static function getStatusName(int $status): string
   {
      switch ($status) {
         case self::AUDIT_ITEM_STATUS_FAILED:
            return __('Failed', 'assetaudit');
         case self::AUDIT_ITEM_STATUS_PASSED:
            return __('Passed', 'assetaudit');
         case self::AUDIT_ITEM_STATUS_REMEDIATED:
            return __('Remediated', 'assetaudit');
         case self::AUDIT_ITEM_STATUS_NOTSTARTED:
         default:
            return __('Not started', 'assetaudit');
      }
   }
}