<?php

class PluginConventionsConfig extends PluginConventionsCommon{
   static private $_instance = NULL;
   static $rightname = "config";


   /**
    * Check if relation already exists.
    *
    * @param array $input
    *
    * @return boolean
    *
    * @since 9.5.0
    */
    public function alreadyExists(array $input): bool {
      $criteria = [
         'documents_id'      => $input['documents_id'],
         'itemtype'          => $input['itemtype'],
         'language'          => $input['language'] ?? null
      ];
      if (array_key_exists('timeline_position', $input) && !empty($input['timeline_position'])) {
         $criteria['timeline_position'] = $input['timeline_position'];
      }
      return countElementsInTable('glpi_plugin_conventions_config', $criteria) > 0;
   }

   static function install(Migration $mig){
      global $DB;

      $table = 'glpi_plugin_conventions_configs';
      if(!$DB->tableExists($table)){
         $query= "CREATE TABLE IF NOT EXISTS
               `glpi_plugin_conventions_configs` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL COMMENT 'display name',
                  `users_id` int(11) NOT NULL COMMENT 'RELATION to glpi_users (id)',
                  `itemtype` VARCHAR(100) NOT NULL COMMENT 'see define.php *_TYPE constant',
                  `language` VARCHAR(100) NOT NULL COMMENT 'what language the document is for',
                  `documents_id` varchar(255) NOT NULL COMMENT 'ref of tab to display, or plugname_#, or option name',
                  PRIMARY KEY (`id`)
               ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
         $DB->queryOrDie($query, $DB->error());
      }
   }

   static function uninstall(Migration $mig){
      global $DB;

      $mig->dropTable('glpi_plugin_conventions_configs');
      $DB->query("DELETE
                  FROM `glpi_documents_items`
                  WHERE `itemtype` ='PluginConventionsConfig'");

   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0){
      //if ($item->getType()=='Config'){
         return $this::getName();
      //}
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0){
      global $CFG_GLPI, $PLUGIN_HOOKS;
      self::menu($item);

      foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
         $item = new $type;
         self::menu($item);
      }

      return true;
   }
}