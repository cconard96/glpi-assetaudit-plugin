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
      global $DB;

      $itemtype = $item::getType();
      $id = $item->getID();

      $out = "<form method='POST' action=\"".self::getQuickAuditUrl(true)."\">";
      $out .= "<table class='tab_cadre_fixe'>";

      $out .= "<tr>";
      $out .= "<th colspan='4'>";
      $out .= $item->getTypeName().": ".$item->getName();
      $out .= "</th>";
      $out .= "</tr>";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__('Name')."</td>";
      $out .= "<td>";
      $objectName = autoName($item->fields["name"], "name", false, $itemtype, $item->fields["entities_id"]);
      $out .= Html::autocompletionTextField($item, 'name', [
         'value'     => $objectName,
         'display'   => false
      ]);
      $out .= "</td>";
      $out .= "<td>".__('Status')."</td>";
      $out .= "<td>";
      $out .= State::dropdown([
         'value'     => $item->fields["states_id"],
         'entity'    => $item->fields["entities_id"],
         'condition' => ['`is_visible_computer`', 1],
         'display'   => false
      ]);
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__('Location')."</td>";
      $out .= "<td>";
      $out .= Location::dropdown([
         'value'     => $item->fields["locations_id"],
         'entity'    => $item->fields["entities_id"],
         'display'   => false
      ]);
      $out .= "</td>";
      $out .= "<td>".__('Type')."</td>";
      $out .= "<td>";
      /** @var CommonDeviceType $type */
      $type = $itemtype.'Type';
      if ($DB->tableExists($type::getTable())) {
         $out .= ComputerType::dropdown([
            'value' => $item->fields[$type::getForeignKeyField()],
            'display' => false
         ]);
      }
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__('User')."</td>";
      $out .= "<td>";
      $out .= User::dropdown([
         'value'     => $item->fields["users_id"],
         'entity'    => $item->fields["entities_id"],
         'right'     => 'all',
         'display'   => false
      ]);
      $out .= "</td>";
      $out .= "<td>".__('Manufacturer')."</td>";
      $out .= "<td>";
      $out .= Manufacturer::dropdown([
         'value'     => $item->fields["manufacturers_id"],
         'display'   => false
      ]);
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td rowspan='3'>".__('Comments')."</td>";
      $out .= "<td rowspan='3' class='middle'>";

      $out .= "<textarea cols='45' rows='3' name='comment' >".$item->fields["comment"];
      $out .= "</textarea></td>";
      /** @var CommonDeviceModel $model */
      $model = $itemtype.'Model';
      if ($DB->tableExists($model::getTable())) {
         $out .= "<td>";
         $out .= __('Model');
         $out .= "</td>";
         $out .= "<td>";
         $out .= $model::dropdown([
            'value'     => $item->fields[$model::getForeignKeyField()],
            'display'   => false
         ]);
         $out .= "</td>";
      } else {
         $out .= "<td colspan='2'></td>";
      }
      $out .= "</tr>";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__('Serial number')."</td>";
      $out .= "<td >";
      $out .= Html::autocompletionTextField($item,'serial', [
         'display'   => false
      ]);
      $out .= "</td></tr>\n";

      $out .= "<tr class='tab_bg_1'>";
      $out .= "<td>".__('Inventory number')."</td>";
      $out .= "<td>";
      $objectName = autoName($item->fields["otherserial"], "otherserial", false, $item->getType(), $item->fields["entities_id"]);
      $out .= Html::autocompletionTextField($item, 'otherserial', [
         'value'  => $objectName,
         'display'   => false
      ]);
      $out .= "</td></tr>\n";

      $out .= "<tr>";
      $out .= "<td class='center assetaudit-btngroup' style='height: 60px;' colspan='4'>";
      $out .= "<input type='submit' class='submit assetaudit-success' name='audit_success' value=\"".__('Complete Audit', 'assetaudit')."\">";
      $out .= "<input type='submit' class='submit assetaudit-failure' name='create_ticket' value=\"".__('Create Ticket', 'assetaudit')."\">";
      $out .= Html::getSimpleForm(Ticket::getFormURL(),
         '_add_fromitem', __('New ticket for this item...'),
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