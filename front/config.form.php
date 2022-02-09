<?php

use Glpi\Event;
define('GLPI_ROOT', '../../..');
include(GLPI_ROOT. '/inc/includes.php');

$config = new PluginConventionsConfig();
if(isset($_POST["update"])){
    Html::back();
}

if (isset($_POST["delete"])){
    $DB->query("DELETE
    FROM `glpi_plugin_conventions_configs`
    WHERE `language` ='".$_POST["languages"]."'
          AND `itemtype`='" . $_POST["itemtype"]."'");


Html::displayErrorAndDie("lost");
}

$document_item   = new Document_Item();

if (isset($_POST["add"])) {
    $document_item->check(1, CREATE, $_POST);
    //print_r($_POST);
    $type = $_POST['itemtype'];
    $_POST['itemtype'] = 'PluginConventionsConfig';
    if ($document_item->add($_POST)) {
        Event::log($_POST["documents_id"], "documents", 4, "document",
                    //TRANS: %s is the user login
                    sprintf(__('%s adds a link with an item'), $_SESSION["glpiname"]));
        $DB->query("DELETE
                    FROM `glpi_plugin_conventions_configs`
                    WHERE `language` ='".$_POST["languages"]."'
                          AND `itemtype`='" . $_POST["itemtype"]."'");
        $DB->query("INSERT INTO `glpi_plugin_conventions_configs`
                    (`id` ,`name` ,`users_id` ,`itemtype` ,`language`, `documents_id`)
             VALUES (NULL , 'Conventions Plugin',
                     '".$_SESSION["glpiID"]."',
                     '".$type."', 
                     '".$_POST["languages"]."',
                     '".$_POST["documents_id"]."')");
                     
    }
    //print_r($_POST);
    Html::back();

}

if (!count($_POST)){
    $adress = Toolbox::getItemTypeFormURL('Config');
    Html::redirect($adress);
}

Html::displayErrorAndDie("lost");