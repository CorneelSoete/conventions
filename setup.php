<?php

function plugin_init_conventions(){
    global $CFG_GLPI, $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['conventions'] = true;

    Plugin::registerClass('PluginConventionsConfig', ['addtabon' => 'Config']);

    if (Session::haveRight('plugin_conventions', READ)) {
        Plugin::registerClass('PluginConventionsConfig', ['addtabon' => 'Preference']);
     }


     $plugin = new Plugin();
     if ($plugin->isActivated("pdf")){
         $PLUGIN_HOOKS['post_init']['conventions'] = 'plugin_conventions_postinit';
     }
     //Define the the type for which we know to generate 
}

function plugin_version_conventions(){

    return ['name'           => 'Conventions',
            'version'        => '0.1.0',
            'author'         => 'Corneel Soete',
            'license'        => 'GPLv3+',
            'homepage'       => 'https://github.com/CorneelSoete/conventions',
            'minGlpiVersion' => '9.5'];
}