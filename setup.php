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

define('PLUGIN_ASSETAUDIT_VERSION', '1.0.0');
define('PLUGIN_ASSETAUDIT_MIN_GLPI', '9.5.0');
define('PLUGIN_ASSETAUDIT_MAX_GLPI', '9.6.0');

function plugin_init_assetaudit()
{
	global $PLUGIN_HOOKS, $CFG_GLPI;
	$PLUGIN_HOOKS['csrf_compliant']['assetaudit'] = true;
	$common_audit_fields = [
	   'name' => [
         'label'     => __('Name'),
         'type'      => 'text'
      ],
      'states_id' => [
         'label'     => Location::getTypeName(1),
         'type'      => 'item',
         'itemtype'  => State::class
      ],
      'manufacturers_id' => [
         'label'     => Manufacturer::getTypeName(1),
         'type'      => 'item',
         'itemtype'  => Manufacturer::class
      ],
      'serial' => [
         'label'     => __('Serial number'),
         'type'      => 'text'
      ],
      'otherserial' => [
         'label'     => __('Inventory number'),
         'type'      => 'text'
      ],
      'locations_id' => [
         'label'     => Location::getTypeName(1),
         'type'      => 'item',
         'itemtype'  => Location::class
      ],
      'users_id' => [
         'label'     => User::getTypeName(1),
         'type'      => 'item',
         'itemtype'  => User::class
      ],
      'groups_id' => [
         'label'     => Group::getTypeName(1),
         'type'      => 'item',
         'itemtype'  => Group::class
      ],
      'comment' => [
         'label'  => __('Comment'),
         'type'   => 'textarea'
      ]
   ];
	$CFG_GLPI['plugin_assetaudit_itemtypes'] = [
      'Computer' => $common_audit_fields,
      'Monitor' => $common_audit_fields,
      'NetworkEquipment' => $common_audit_fields,
      'Peripheral' => $common_audit_fields,
      'Printer' => $common_audit_fields,
      'CartridgeItem' => $common_audit_fields,
      'ConsumableItem' => $common_audit_fields,
      'Phone' => $common_audit_fields,
      'Enclosure' => $common_audit_fields,
      'PDU' => $common_audit_fields,
      'PassiveDCEquipment' => $common_audit_fields,
      'Appliance' => $common_audit_fields,
      'Cluster' => $common_audit_fields
   ];
   if (Session::haveRight('plugin_assetaudit_audit', READ)) {
      $PLUGIN_HOOKS['menu_toadd']['assetaudit'] = ['plugins' => PluginAssetauditAudit::class];
   }
   Plugin::registerClass(PluginAssetauditProfile::class, ['addtabon' => ['Profile']]);
   $PLUGIN_HOOKS['add_css']['assetaudit'][] = 'css/assetaudit.css';
}

function plugin_version_assetaudit()
{
	return [
	      'name'         => __('Asset Audit', 'assetaudit'),
	      'version'      => PLUGIN_ASSETAUDIT_VERSION,
	      'author'       => 'Curtis Conard',
	      'license'      => 'GPLv2',
	      'homepage'     =>'https://github.com/cconard96/glpi-assetaudit-plugin',
	      'requirements' => [
	         'glpi'   => [
	            'min' => PLUGIN_ASSETAUDIT_MIN_GLPI,
	            'max' => PLUGIN_ASSETAUDIT_MAX_GLPI
	         ]
	      ]
	   ];
}

function plugin_assetaudit_check_prerequisites()
{
	if (!method_exists('Plugin', 'checkGlpiVersion')) {
	      $version = preg_replace('/^((\d+\.?)+).*$/', '$1', GLPI_VERSION);
	      $matchMinGlpiReq = version_compare($version, PLUGIN_ASSETAUDIT_MIN_GLPI, '>=');
	      $matchMaxGlpiReq = version_compare($version, PLUGIN_ASSETAUDIT_MAX_GLPI, '<');
	      if (!$matchMinGlpiReq || !$matchMaxGlpiReq) {
	         echo vsprintf(
	            'This plugin requires GLPI >= %1$s and < %2$s.',
	            [
                  PLUGIN_ASSETAUDIT_MIN_GLPI,
                  PLUGIN_ASSETAUDIT_MAX_GLPI,
	            ]
	         );
	         return false;
	      }
	   }
	   return true;
}

function plugin_assetaudit_check_config()
{
	return true;
}

