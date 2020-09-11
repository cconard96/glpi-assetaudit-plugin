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
	$CFG_GLPI['plugin_assetaudit_itemtypes'] = [
      'Computer', 'Monitor', 'Software', 'NetworkEquipment',
      'Peripheral', 'Printer', 'CartridgeItem', 'ConsumableItem',
      'Phone', 'Enclosure', 'PDU', 'PassiveDCEquipment',' Appliance', 'Cluster'
   ];
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

