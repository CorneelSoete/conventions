<?php

function plugin_conventions_postinit(){
    global $CFG_GLPI, $PLUGIN_HOOKS;

    foreach ($PLUGIN_HOOKS['plugin_pdf'] as $type => $typepdf) {
        CommonGLPI::registerStandardTab($type, 'PluginConventionsCommon');
     }
}

function plugin_conventions_install(){
    global $DB;

    $migration = new Migration('1.7.0');

    if (!$DB->tableExists('glpi_plugin_conventions_configs')) {
        include_once(Plugin::getPhpDir('conventions')."inc/config.class.php");
        PluginConventionsConfig::install($migration);
     }

    return true;
}

function plugin_conventions_uninstall(){
    global $DB;

    $migration = new Migration('1.7.0');
    if ($DB->tableExists('glpi_plugin_conventions_configs')) {
        include_once(Plugin::getPhpDir('conventions')."inc/config.class.php");
        PluginConventionsConfig::uninstall($migration);
     }

    return true;
}