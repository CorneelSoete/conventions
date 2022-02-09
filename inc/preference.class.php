<?php

/**
 * This is an almost exact copy of the menu function in the PdfPreference,
 * with the small difference that it now also chooses the language
 */

class PluginConventionsPreference extends PluginPdfPreference{
   function menu($item, $action){
      global $DB, $PLUGIN_HOOKS, $CFG_GLPI;

      $type = $item->getType();

      // $ID set if current object, not set from preference
      if (isset($item->fields['id'])) {
         $ID = $item->fields['id'];
      } else {
         $ID = 0;
         $item->getEmpty();
      }

      if (!isset($PLUGIN_HOOKS['plugin_pdf'][$type])
          || !class_exists($PLUGIN_HOOKS['plugin_pdf'][$type])) {
         return;
      }
      $itempdf = new $PLUGIN_HOOKS['plugin_pdf'][$type]($item);
      $options = $itempdf->defineAllTabsPDF();

      $formid="plugin_pdf_${type}_".mt_rand();
      echo "<form name='".$formid."' id='".$formid."' action='$action' method='post' ".
             ($ID ? /*"target='_blank'"*/"" : "")."><table class='tab_cadre_fixe'>";

      $landscape = false;
      $values    = [];

      // this is a bit hardcoded (used to be $this->getTable() but does no longer work because we want the table of the parent)
      $table = "glpi_plugin_pdf_preferences";
      foreach ($DB->request($table,
                            ['SELECT' => 'tabref',
                             'WHERE'  => ['users_id' => $_SESSION['glpiID'],
                                          'itemtype' => $type]]) AS $data) {
         if ($data["tabref"] == 'landscape') {
            $landscape = true;
         } else {
            $values[$data["tabref"]] = $data["tabref"];
         }
      }
      // Always export, at least, main part.
      if (!count($values) && isset($options[$type.'$main'])) {
         $values[$type.'$main'] = 1;
      }

      echo "<tr><th colspan='6'>".sprintf(__('%1$s: %2$s'),
                                          __('Choose the tables to print in pdf', 'pdf'),
                                          $item->getTypeName());
      echo "</th></tr>";

      $i = 0;
      foreach ($options as $num => $title) {
         if (!$i) {
            echo "<tr class='tab_bg_1'>";
         }
         if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            $title = "$title ($num)";
         }
         $this->checkbox($num, $title, (isset($values[$num]) ? true : false));
         if ($i == 4) {
            echo "</tr>";
            $i = 0;
         } else {
            $i++;
         }
      }
      if ($i) {
         while ($i <= 4) {
            echo "<td width='20%'>&nbsp;</td>";
            $i++;
         }
         echo "</tr>";
      }

      echo "<tr class='tab_bg_2'><td colspan='2' class='left'>";
      echo "<a onclick=\"if (markCheckboxes('".$formid."') ) return false;\" href='".
           $_SERVER['PHP_SELF']."?select=all'>".__('Check all')."</a> / ";
      echo "<a onclick=\"if (unMarkCheckboxes('".$formid."') ) return false;\" href='".
           $_SERVER['PHP_SELF']."?select=none'>".__('Uncheck all')."</a></td>";

      echo "<td class='center'>";
      echo "<input type='hidden' name='plugin_pdf_inventory_type' value='".$type."'>";
      echo "<input type='hidden' name='indice' value='".count($options)."'>";

      if ($ID) {
         foreach ($CFG_GLPI['languages'] as $option => $value) {
            $langs[$option] = $value[0];
         }
         Dropdown::showFromArray("languages", $langs,
                        ['value' => 'en_GB']);
         echo '&nbsp;&nbsp;&nbsp;';
         echo "<input type='hidden' name='itemID' value='".$ID."'>";
         echo "<input type='submit' value='" . _sx('button', 'Save') .
              "' name='plugin_pdf_user_preferences_save' class='submit'>";
         echo "<input type='submit' value='". _sx('button','Print', 'pdf') .
              "' name='generate' class='submit'></td></tr>";
      } else {
         echo "<input type='submit' value='" . _sx('button', 'Save') .
              "' name='plugin_pdf_user_preferences_save' class='submit'></td></tr>";
      }
      echo "</table>";
      
      foreach ($options as $num => $title) {
         $tabFields = $itempdf->getFieldsForTab($item, $num);
         if ($tabFields && isset($values[$num])){
            $checkedFields = [];
            foreach($values as $key => $field){
               if (strpos($field, $num.'$') === 0){
                  $checkedFields[$field] = $field;
                  unset($values[$field]);
               }
            }
            $this->tabmenu($num, $title, $tabFields, $checkedFields);
         }
      }
      Html::closeForm();
   }
}