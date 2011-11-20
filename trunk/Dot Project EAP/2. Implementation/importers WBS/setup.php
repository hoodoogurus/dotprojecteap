<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}
/**
 * Name:        Project Importer
 * Directory:   importers
 * Version:     4.1
 * Type:        user
 * UI Name:     Project Importer
 * UI Icon:     ?
 */

$config = array();
$config['mod_name']        = 'Project Importer';
$config['mod_version']     = '4.1';
$config['mod_directory']   = 'importers';               // the module path
$config['mod_setup_class'] = 'CSetupProjectImporter';   // the name of the setup class
$config['mod_type']        = 'user';                    // 'core' for modules distributed with w2p itself, 'user' for addon modules
$config['mod_ui_name']	   = $config['mod_name'];       // the name that is shown in the main menu of the User Interface
$config['mod_ui_icon']     = 'projectimporter.png';     // name of a related icon
$config['mod_description'] = 'Import various XML formats';

if (@$a == 'setup') {
	echo w2PshowModuleConfig( $config );
}

class CSetupProjectImporter {

    public function configure() {
		return true;	
	}

    public function remove() {
        $q = new DBQuery;
        $q->setDelete('modules');
        $q->addWhere("mod_directory = 'importers'");
        $q->exec();
        return true;
	}

    public function upgrade($old_version) {
        return true;
	}

    public function install() {
        global $AppUI;

        $perms = $AppUI->acl();
        return $perms->registerModule('Project Importer', 'importers');
	}
}