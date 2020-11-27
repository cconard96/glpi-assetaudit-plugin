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

/**
 * Asset audit class
 *
 * Contains logic for both quick audits and planned audits
 */
class PluginAssetauditAudit extends CommonDBTM {

   public static $rightname = 'plugin_assetaudit_audit';

   public static function getTypeName($nb = 0)
   {
      return _n('Audit', 'Audits', $nb, 'assetaudit');
   }

   public static function getIcon()
   {
      return 'fas fa-clipboard-check';
   }

   public static function getMenuName()
   {
      return self::getTypeName();
   }

   static function getAdditionalMenuLinks() {

      $links = [];
      if (static::canView()) {
         $title = __('Quick audit', 'assetaudit');
         $links["<i class='pointer fas fa-bolt' title='{$title}'></i>"] = '/'.self::getQuickAuditUrl(false);
      }
      if (count($links)) {
         return $links;
      }
      return false;
   }

   public static function getQuickAuditUrl(bool $full = true): string
   {
      return Plugin::getWebDir('assetaudit', $full) . '/front/quickaudit.php';
   }

   /**
    * @param ?CommonDBTM $item
    * @return void
    */
   public static function showQuickAuditForm($item = null): void
   {
      $out = '<div id="assetaudit-audit-container">';
      $out .= "<form method='GET' action=\"".self::getQuickAuditUrl(true)."\">";

      $out .= "<table class='tab_cadre_fixe'>";

      $out .= "<tr><th>";
      $out .= __('Quick Audit', 'assetaudit');
      $out .= "</th></tr>";

      $out .= "<tr><td class='center' style='height: 40px;'>";
      $out .= __('Search with the serial number or inventory number', 'assetaudit');
      $out .= "</td></tr>";

      $out .= "<tr><td class='center' style='height: 80px;'>";
      $out .= "<input type='text' name='searchnumber' value='' size='50' style='height: 40px;font-size: 20px;' autofocus />";
      $out .= "</td></tr>";

      $out .= "<tr><td class='center' style='height: 40px;'>";
      $out .= "<input type='submit' name='search_item' value=\"".__('Search')."\" class='submit' >";
      $out .= "</td></tr>";

      $out .= "<tr><td class='center'></td></tr>";

      $out .= "</table>";
      $out .= '</div>';

      $out .= Html::closeForm(false);

      // Item info container
      $out .= '<div class="audit-iteminfo">';
      if ($item) {
         $out .= self::getItemInformationHtml($item);
      }
      $out .= '</div>';

      echo $out;
   }

   /**
    * @param CommonDBTM $item
    * @return string
    */
   public static function getItemInformationHtml(CommonDBTM $item): string
   {
      global $DB, $CFG_GLPI;

      $itemtype = $item::getType();
      $id = $item->getID();

      $out = "<form method='POST' action=\"".self::getQuickAuditUrl(true)."\">";
      $out .= "<table class='tab_cadre_fixe'>";

      $out .= "<tr>";
      $out .= "<th colspan='4'>";
      $out .= $item->getTypeName().": ".$item->getName();
      $out .= "</th>";
      $out .= "</tr>";

      $fields = $CFG_GLPI['plugin_assetaudit_itemtypes'][$itemtype] ?? [];
      $odd = false;
      foreach ($fields as $field_name => $field_data) {
         if (!$odd) {
            $out .= '<tr>';
         }

         $out .= "<td>{$field_data['label']}</td>";
         $out .= '<td>';
         switch ($field_data['type']) {
            case 'text':
               $out .= Html::input($field_name, [
                  'value'  => $item->fields[$field_name]
               ]);
               break;
            case 'item':
               $p = [
                  'value'     => $item->fields[$field_name],
                  'entity'    => $item->fields["entities_id"],
                  'display'   => false
               ];
               if (isset($field_data['condition'])) {
                  $p_key = str_replace('{itemtype}', $itemtype, $field_data['condition'][0]);
                  $p_val = str_replace('{itemtype}', $itemtype, $field_data['condition'][1]);
                  $p['condition'] = [$p_key, $p_val];
               }
               if (isset($field_data['right'])) {
                  $p['right'] = $field_data['right'];
               }
               /** @var CommonDBTM $dropdown_type */
               $dropdown_type = $field_data['itemtype'];
               $out .= $dropdown_type::dropdown($p);
               break;
            case 'textarea':
               $out .= "<textarea cols='45' rows='3' name='comment'>".$item->fields[$field_name]."</textarea>";
         }
         $out .= '</td>';

         $odd = !$odd;
         if (!$odd) {
            $out .= '</tr>';
         }
      }

      $out .= "<tr>";
      $out .= "<td class='center assetaudit-btngroup' style='height: 60px;' colspan='4'>";
      $out .= "<input type='submit' class='submit assetaudit-success' name='audit_success' value=\"".__('Complete Audit', 'assetaudit')."\">";
      $out .= Html::getSimpleForm(Ticket::getFormURL(),
         '_add_fromitem', __('Fail Audit. Create Ticket.'),
         ['itemtype' => $item->getType(),
            'items_id' => $item->getID()], '', 'class="vsubmit assetaudit-failure"');
      $out .= Html::hidden('itemtype', ['value' => $itemtype]);
      $out .= Html::hidden('id', ['value' => $id]);
      $out .= "</td>";
      $out .= "</tr>";

      $out .= "</table>";
      $out .= Html::closeForm(false);

      return $out;
   }

   /**
    * @param array $items
    * @return string
    */
   public static function getAssetPickerHtml(array $items): string
   {
      $out = "<table class='tab_cadre_fixe'>";

      $out .= "<tr>";
      $out .= "<th colspan='5'>";
      $out .= __('Multiple devices found, choose the right', 'assetaudit');
      $out .= "</th>";
      $out .= "</tr>";

      foreach ($items as $itemtype => $ids) {
         $item = new $itemtype();
         foreach ($ids as $id) {
            $item->getFromDB($id);
            $out .= "<tr class='tab_bg_1'>";
            $out .= "<td>".$item->getTypeName()."</td>";
            $out .= "<td>".$item->getLink()."</td>";
            $out .= "<td>".$item->fields['serial']."</td>";
            $out .= "<td>".Dropdown::getDropdownName('glpi_manufacturers', $item->fields['manufacturers_id'])."</td>";
            $out .= "<td>";

            $out .= "<form method='POST' action=\"".self::getQuickAuditUrl(true)."\">";
            $out .= Html::hidden('itemtype', ['value' => $itemtype]);
            $out .= Html::hidden('id', ['value' => $id]);
            $out .= "<input type='submit' name='choose_device' value=\"".
               __('Choose it', 'assetaudit')."\" class='submit' >";
            $out .= Html::closeForm(false);
            $out .= "</td>";
            $out .= "</tr>";
         }
      }
      $out .= "</table>";
      return $out;
   }

   /**
    * @param string $criteria
    * @return array
    */
   public static function quickAssetSearch(string $criteria): array
   {
      global $DB, $CFG_GLPI;

      $found_items = [];

      /**
       * @var CommonDBTM $itemtype
       * @var array $fields
       */
      foreach($CFG_GLPI["plugin_assetaudit_itemtypes"] as $itemtype => $fields) {
         $dummy_item = new $itemtype();
         $where_fields = [];
         $table = getTableForItemType($itemtype);
         $item = new $itemtype();

         if ($DB->fieldExists($table, 'serial')) {
            $where_fields[] = 'serial';
         }
         if ($DB->fieldExists($table, 'otherserial')) {
            $where_fields[] = 'otherserial';
         }
         if (count($where_fields) === 0) {
            continue;
         }
         $query = [
            'SELECT' => ['id'],
            'FROM'   => $table,
            'WHERE'  => getEntitiesRestrictCriteria($table),
            'ORDER'  => ['name']
         ];

         if ($dummy_item->maybeTemplate()) {
            $query['WHERE']['is_template'] = 0;
         }
         if ($dummy_item->maybeDeleted()) {
            $query['WHERE']['is_deleted'] = 0;
         }

         $whereOr = [];
         foreach ($where_fields as $field) {
            $whereOr[$field] = $criteria;
         }
         if (count($whereOr) > 0) {
            $query['WHERE'][] = ['OR' => $whereOr];
         }

         $iterator = $DB->request($query);
         while ($data = $iterator->next()) {
            if ($item->canEdit($data['id'])) {
               $found_items[$itemtype][$data['id']] = $data['id'];
            }
         }
      }
      return $found_items;
   }

   public static function completeAudit($itemtype, $items_id, $update, $audit = null): void
   {
      $item = new $itemtype;
      if (!isset($update['id'])) {
         $update['id'] = $items_id;
      }
      $item->update($update);

      $infocom = new Infocom();

      if ($infocom->getFromDBforDevice($itemtype, $items_id)) {
         $input = [
            'id'             => $infocom->fields['id'],
            'inventory_date' => $_SESSION['glpi_currenttime']
         ];
         $infocom->update($input);
      } else {
         $input = [
            'items_id'       => $items_id,
            'itemtype'       => $itemtype,
            'inventory_date' => $_SESSION['glpi_currenttime']
         ];
         $infocom->add($input);
      }

      // Handle non-quick audit

      Session::addMessageAfterRedirect(__('Audit Completed', 'assetaudit'), false, INFO);
   }
}