<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}

$canAuthor = canAdd('projects');

if (!$canAuthor)
{
  $AppUI->redirect("m=public&a=access_denied");
}

$AppUI->savePlace();
$tab = 0;
$AppUI->setState("msimportIdxTab", $tab);

$titleBlock = new CTitleBlock('importers', 'projectimporter.png', $m, "$m.$a");
$titleBlock->show();

echo $AppUI->_('msinfo');
$tabBox = new CTabBox("?m=$m", W2P_BASE_DIR . "/modules/$m/", $tab);
$tabBox->add('vw_idx_import', $AppUI->_('Import'));
$tabBox->show();