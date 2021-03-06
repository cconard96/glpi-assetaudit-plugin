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

function plugin_assetaudit_install()
{
   $migration = new PluginAssetauditMigration(PLUGIN_ASSETAUDIT_VERSION);
   $migration->applyMigrations();
	return true;
}

function plugin_assetaudit_uninstall()
{
   PluginAssetauditDBUtil::dropTableOrDie('glpi_plugin_assetaudit_audits');
   PluginAssetauditDBUtil::dropTableOrDie('glpi_plugin_assetaudit_audits_items');
	return true;
}