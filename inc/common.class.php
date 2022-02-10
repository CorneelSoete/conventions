<?php

class PluginConventionsCommon extends CommonDBTM{

   static private $_instance = NULL;
   static $rightname = "config";

   static function canCreate() {
      return self::canUpdate();
   }

   /**
   * Summary of getTypeName
   * @param mixed $nb plural
   * @return mixed
   */
   static function getTypeName($nb = 0) {
      return "Convention";
   }

   /**
   * Summary of getName
   * @param mixed $with_comment with comment
   * @return mixed
   */
   function getName($with_comment = 0) {
      return self::getTypeName();
   }

   static function getInstance() {
      if (!isset(self::$_instance)) {
         self::$_instance = new self();
         if (!self::$_instance->getFromDB(1)) {
            self::$_instance->getEmpty();
         }
      }
      return self::$_instance;
   }
     
   static function menu($item){

      $config = new PluginConventionsConfig;

      echo "<table class='tab_cadre_fixe'>";
      echo "<tr><th colspan='6'>Available language files for: ".$item->getTypeName(1);
      echo "</th></tr></table>";
      $action = Toolbox::getItemTypeFormURL(get_class($config));
      
      PluginConventionsDocument::showForItem($item, $action);

      return false;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate=0){
      //if ($item->getType()=='Config'){
         return $this::getName();
      //}
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0){
      $pref = new PluginConventionsPreference;
      $pref->menu($item, Plugin::getWebDir('pdf')."/front/export.php");
      PluginConventionsCommon::menu($item);

      return true;
   }
}