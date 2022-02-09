<?php

class PluginConventionsDocument extends CommonDBTM{
  
   /**
   * Show documents associated to an item
   *
   * @since 0.1.0
   *
   * @param $item            CommonDBTM object for which associated documents must be displayed
   * @param $withtemplate    (default 0)
   **/
   static function showForItem(CommonDBTM $item, $withtemplate = 0) {
      global $PLUGIN_HOOKS;
      $ID = $item->getField('id');

      if ($item->isNewID($ID)
         && !Document::canView()) {
         return false;
      }

      $params         = [];
      $params['rand'] = mt_rand();
      $params['type'] = 'PluginConventions'.$item->getType();

      self::showAddFormForItem($item, $withtemplate, $params);
      self::showListForItem($item,  $withtemplate, $params);
   }

   /**
   * @since 0.1.0
   *
   * @param $item
   * @param $withtemplate    (default 0)
   * @param $options         array
   *
   * @return boolean
   **/
   static function showAddFormForItem(CommonDBTM $item, $action, $params) {
      global $DB, $CFG_GLPI;

      if (!$item->can($item->fields['id'], READ)) {
         return false;
      }

      $type = $params['type'];

      // find documents already associated to the item
      $doc_item   = new Document_Item();
      $used_found = $doc_item->find([
         'items_id'  => $item->getID(),
         'itemtype'  => $type
      ]);
      $used       = array_keys($used_found);
      $used       = array_combine($used, $used);

      if ($item->canAddItem('Document')) {
         // Restrict entity for knowbase
         $entities = "";
         $entity   = $_SESSION["glpiactive_entity"];

         if ($item->isEntityAssign()) {
            /// Case of personal items : entity = -1 : create on active entity (Reminder case))
            if ($item->getEntityID() >=0) {
               $entity = $item->getEntityID();
            }

            if ($item->isRecursive()) {
               $entities = getSonsOf('glpi_entities', $entity);
            } else {
               $entities = $entity;
            }
         }
         $limit = getEntitiesRestrictRequest(" AND ", "glpi_documents", '', $entities, true);

         $count = $DB->request([
            'COUNT'     => 'cpt',
            'FROM'      => 'glpi_documents',
            'WHERE'     => [
               'is_deleted' => 0
            ] + getEntitiesRestrictCriteria('glpi_documents', '', $entities, true)
         ])->next();
         $nb = $count['cpt'];

         if (Document::canView()
            && ($nb > count($used))) {
            echo "<form name='document_form".$params['rand']."' id='document_form".$params['rand'].
                  "' method='post' action='".$action."'>";
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='tab_bg_1'>";
            echo "<td colspan='4' class='center'>";
            echo "<input type='hidden' name='itemtype' value='".$type."'>";
            echo "<input type='hidden' name='items_id' value='".$item->getID()."'>";
            foreach ($CFG_GLPI['languages'] as $option => $value) {
               $options[$option] = $value[0];
            }
            Dropdown::showFromArray("languages", $options,
                           ['value' => 'en_GB']);
            Document::dropdown(['entity' => $entities ,
                                    'used'   => $used]);
            echo "</td><td class='center' width='20%'>";
            echo "<input type='submit' name='add' value=\"".
                     _sx('button', 'Associate an existing document')."\" class='submit'>";
            echo "</td>";
            echo "</tr>";
            echo "</table>";
            Html::closeForm();
         }

         echo "</div>";
      }
   }  
   
   /**
    * @since 0.90
    *
    * @param $item
    * @param $withtemplate   (default 0)
    * @param $options        array
    */
    static function showListForItem(CommonDBTM $item, $withtemplate = 0, $options = []) {
      global $DB, $CFG_GLPI;

      if (is_array($options) && count($options)) {
         foreach ($options as $key => $val) {
            $params[$key] = $val;
         }
      }

      $type = $params['type'];

      $canedit = $item->canAddItem('Document') && Document::canView();

      $columns = [
         'language'  => __('Language'),
         'name'      => __('Name'),
         'entity'    => Entity::getTypeName(1),
         'filename'  => __('File'),
         'link'      => __('Web link'),
         'headings'  => __('Heading'),
         'mime'      => __('MIME type'),
         'tag'       => __('Tag'),
         'assocdate' => _n('Date', 'Dates', 1)
      ];

      if (isset($_GET["order"]) && ($_GET["order"] == "ASC")) {
         $order = "ASC";
      } else {
         $order = "DESC";
      }

      if ((isset($_GET["sort"]) && !empty($_GET["sort"]))
         && isset($columns[$_GET["sort"]])) {
         $sort = $_GET["sort"];
      } else {
         $sort = "assocdate";
      }

      if (empty($withtemplate)) {
         $withtemplate = 0;
      }
      $linkparam = '';

      if (get_class($item) == 'Ticket') {
         $linkparam = "&amp;tickets_id=".$item->fields['id'];
      }

      $criteria = [
         'SELECT'    => [
            'glpi_documents_items.id AS assocID',
            'glpi_documents_items.date_creation AS assocdate',
            'glpi_entities.id AS entityID',
            'glpi_entities.completename AS entity',
            'glpi_documentcategories.completename AS headings',
            'glpi_plugin_conventions_configs.language AS language', 
            'glpi_documents.*'
         ],
         'FROM'      => 'glpi_documents_items',
         'LEFT JOIN' => [
            'glpi_documents'  => [
               'ON' => [
                  'glpi_documents_items'  => 'documents_id',
                  'glpi_documents'        => 'id'
               ]
            ],
            'glpi_entities'   => [
               'ON' => [
                  'glpi_documents'  => 'entities_id',
                  'glpi_entities'   => 'id'
               ]
            ],
            'glpi_documentcategories'  => [
               'ON' => [
                  'glpi_documentcategories'  => 'id',
                  'glpi_documents'           => 'documentcategories_id'
               ]
            ],
            'glpi_plugin_conventions_configs' => [
               'ON' => [
                  'glpi_documents'                 => 'id',
                  'glpi_plugin_conventions_configs' => 'documents_id'
               ]
            ]
         ],
         'WHERE'     => [
            'glpi_plugin_conventions_configs.itemtype'  => $type
         ],
         'ORDERBY'   => [
            "$sort $order"
         ]
      ];

      if (Session::getLoginUserID()) {
         $criteria['WHERE'] = $criteria['WHERE'] + getEntitiesRestrictCriteria('glpi_documents', '', '', true);
      } else {
         // Anonymous access from FAQ
         $criteria['WHERE']['glpi_documents.entities_id'] = 0;
      }

      // Document : search links in both order using union
      $doc_criteria = [];
      if ($type == 'PluginConventionsDocument') {
         $owhere = $criteria['WHERE'];
         $o2where =  $owhere + ['glpi_documents_items.documents_id' => $item->getID()];
         unset($o2where['glpi_documents_items.items_id']);
         $criteria['WHERE'] = [
            'OR' => [
               $owhere,
               $o2where
            ]
         ];
      }

      $iterator = $DB->request($criteria);
      $number = count($iterator);
      $i      = 0;

      $documents = [];
      $used      = [];
      while ($data = $iterator->next()) {
         $documents[$data['language']] = $data;
         $used[$data['id']]           = $data['id'];
      }

      echo "<div class='spaced'>";
      /*
      if ($canedit
          && $number
          && ($withtemplate < 2)) {
         Html::openMassiveActionsForm('mass'.__CLASS__.$params['rand']);
         $massiveactionparams = ['num_displayed'  => min($_SESSION['glpilist_limit'], $number),
                                      'container'      => 'mass'.__CLASS__.$params['rand']];
         Html::showMassiveActions($massiveactionparams);
      }
      */

      echo "<form name='document_form".$params['rand']."' id='document_form".$params['rand'].
      "' method='post' action='".$withtemplate."'>";
      echo "<table class='tab_cadre_fixehov'>";

      $header_begin  = "<tr>";
      $header_mid    = '';
      $header_end    = '';
      
      if ($canedit
          && ($withtemplate < 2)) {
         $header_mid    .= "<th width='11'></th>";
      }

      foreach ($columns as $key => $val) {
         $header_end .= "<th".($sort == "$key" ? " class='order_$order'" : '').">".
                        "<a href='javascript:reloadTab(\"sort=$key&amp;order=".
                          (($order == "ASC") ?"DESC":"ASC")."&amp;start=0\");'>$val</a></th>";
      }

      $header_end .= "</tr>";
      echo $header_begin.$header_mid.$header_end;

      $used = [];

      // Don't use this for document associated to document
      // To not loose navigation list for current document
      if ($type != 'PluginConventionsDocument') {
         Session::initNavigateListItems('Document',
                           //TRANS : %1$s is the itemtype name,
                           //        %2$s is the name of the item (used for headings of a list)
                                          sprintf(__('%1$s = %2$s'),
                                                $item->getTypeName(1), $item->getName()));
      }

      foreach ($CFG_GLPI['languages'] as $option => $value) {
         $languages[$option] = $value[0];
      }

      $document = new Document();
      foreach ($CFG_GLPI['languages'] as $option => $value) {
         $language     = $value[0];
         $data         = $documents[$option];
         $docID        = $data["id"];
         $link         = NOT_AVAILABLE;
         $downloadlink = NOT_AVAILABLE;

         if ($document->getFromDB($docID)) {
            $link         = $document->getLink();
            $downloadlink = $document->getDownloadLink($linkparam);
         }

         if ($type != 'PluginConventionsDocument') {
            Session::addToNavigateListItems('Document', $docID);
         }
         $used[$docID] = $docID;
         $assocID      = $data["assocID"];

         echo "<tr class='tab_bg_1".($data["is_deleted"]?"_2":"")."'>";
         
         
         # editing buttons
         if ($canedit
               && ($withtemplate < 2)) {
            if ($data) {
            echo "<td width='10'>";
            echo "<button title='Remove' type='submit' name='delete' value=\"".
                     $language."\" class='submit'><i class='fas fa-trash'></button>";
            //echo "<button title='Remove'> <i class='fas fa-trash'></i></button>";
            echo "</td>";
            } else {
               echo "<td width='10> '' </td>";
            }
         }
         
         
         echo "<td class='center'>$language</td>";
         echo "<td class='center'>$link</td>";
         echo "<td class='center'>".$data['entity']."</td>";
         echo "<td class='center'>$downloadlink</td>";
         echo "<td class='center'>";
         if (!empty($data["link"])) {
            echo "<a target=_blank href='".Toolbox::formatOutputWebLink($data["link"])."'>".$data["link"];
            echo "</a>";
         } else {
            echo "&nbsp;";
         }
         echo "</td>";
         echo "<td class='center'>".Dropdown::getDropdownName("glpi_documentcategories",
                                                               $data["documentcategories_id"]);
         echo "</td>";
         echo "<td class='center'>".$data["mime"]."</td>";
         echo "<td class='center'>";
         echo !empty($data["tag"]) ? Document::getImageTag($data["tag"]) : '';
         echo "</td>";
         echo "<td class='center'>".Html::convDateTime($data["assocdate"])."</td>";
         echo "</tr>";
         $i++;
      }

      echo "</table>";
      /*
      if ($canedit && $number && ($withtemplate < 2)) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      */
      Html::closeForm();
      echo "</div>";
      
   }
}