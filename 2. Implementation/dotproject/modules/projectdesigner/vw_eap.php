<?php /* PROJECTS $Id: vw_eap.php,v 1.1 2007/03/15 18:16:42 pedroix Exp $ */
GLOBAL $AppUI, $project_id, $deny, $canRead, $canEdit, $dPconfig;
require_once( $AppUI->getModuleClass( 'eap' ) );
   
$showProject = false;
require( dPgetConfig('root_dir') . '/modules/eap/index_table.php' );
?>
