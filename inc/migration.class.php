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

/**
 * Handles migrating between plugin versions.
 *
 * @since 1.0.0
 */
final class PluginAssetauditMigration {

   private const BASE_VERSION = '1.0.0';

   /**
    * @var Migration
    */
   protected $glpiMigration;

   /**
    * @var DBmysql
    */
   protected $db;

   /**
    * @param string $version
    */
   public function __construct(string $version)
   {
      global $DB;
      $this->glpiMigration = new Migration($version);
      $this->db = $DB;
   }

   public function applyMigrations()
   {
      $rc = new ReflectionClass($this);
      $otherMigrationFunctions = array_map(static function ($rm) use ($rc) {
         return $rm->getShortName();
      }, array_filter($rc->getMethods(), static function ($m) {
         return preg_match('/(?<=^apply_)(.*)(?=_migration$)/', $m->getShortName());
      }));

      if (count($otherMigrationFunctions)) {
         // Map versions to functions
         $versionMap = [];
         foreach ($otherMigrationFunctions as $function) {
            $ver = str_replace(['apply_', '_migration', '_'], ['', '', '.'], $function);
            $versionMap[$ver] = $function;
         }

         // Sort semantically
         uksort($versionMap, 'version_compare');

         // Get last known recorded version. If none exists, assume this is 1.0.0 (start migration from beginning).
         // Migrations should be replayable so nothing should be lost on multiple runs.
         $lastKnownVersion = Config::getConfigurationValues('plugin:assetaudit')['plugin_version'] ?? self::BASE_VERSION;

         // Call each migration in order starting from the last known version
         foreach ($versionMap as $version => $func) {
            // Last known version is the same or greater than release version
            if (version_compare($lastKnownVersion, $version, '<=')) {
               $this->$func();
               $this->glpiMigration->executeMigration();
               if ($version !== self::BASE_VERSION) {
                  $this->setPluginVersionInDB($version);
                  $lastKnownVersion = $version;
               }
            }
         }
      }
   }

   private function setPluginVersionInDB($version)
   {
      $this->db->updateOrInsert(Config::getTable(), [
         'value'     => $version,
         'context'   => 'plugin:assetaudit',
         'name'      => 'plugin_version'
      ], [
         'context'   => 'plugin:assetaudit',
         'name'      => 'plugin_version'
      ]);
   }

   /**
    * Apply the migrations for the base plugin version (1.0.0).
    */
   private function apply_1_0_0_migration(): void
   {
      if (!$this->db->tableExists('glpi_plugin_assetaudit_audits')) {
         $query = "CREATE TABLE `glpi_plugin_assetaudit_audits` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  `comment` TEXT DEFAULT NULL,
                  `plugin_assetaudit_audittypes_id` int(11) NOT NULL DEFAULT 0,
                  `date_planned_start` timestamp NOT NULL,
                  `date_planned_end` timestamp NOT NULL,
                  `date_actual_start` timestamp NOT NULL,
                  `date_actual_end` timestamp NOT NULL,
                  `itemtype_auditor` varchar(100) DEFAULT NULL,
                  `items_id_auditor` int(11) DEFAULT NULL,
                  `audit_status` int(11) NOT NULL DEFAULT 0,
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
                  `locations_id` int(11) DEFAULT NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $this->db->queryOrDie($query, 'Error creating glpi_plugin_assetaudit_audits table' . $this->db->error());
      }
      if (!$this->db->tableExists('glpi_plugin_assetaudit_audits_items')) {
         $query = "CREATE TABLE `glpi_plugin_assetaudit_audits_items` (
                  `id` int(11) NOT NULL auto_increment,
                  `entities_id` int(11) NOT NULL DEFAULT 0,
                  `is_recursive` tinyint(1) NOT NULL DEFAULT 0,
                  `plugin_assetaudit_audits_id` int(11) NOT NULL,
                  `itemtype` varchar(100) NOT NULL,
                  `items_id` int(11) NOT NULL,
                  `audit_status` tinyint(1) DEFAULT 0,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unicity` (`plugin_assetaudit_audits_id`,`itemtype`,`items_id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $this->db->queryOrDie($query, 'Error creating glpi_plugin_assetaudit_audits_items table' . $this->db->error());
      }
      if (!$this->db->tableExists('glpi_plugin_assetaudit_audittypes')) {
         $query = "CREATE TABLE `glpi_plugin_assetaudit_audittypes` (
                  `id` int(11) NOT NULL auto_increment,
                  `name` varchar(255) NOT NULL,
                  `comment` varchar(255) DEFAULT NULL,
                PRIMARY KEY (`id`)
               ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $this->db->queryOrDie($query, 'Error creating glpi_plugin_assetaudit_audittypes table' . $this->db->error());
      }
   }
}
