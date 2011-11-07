<?php /* PROJECTS $Id: vw_forums.php*/
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

GLOBAL $AppUI, $project_id;
// Forums mini-table in project view action
$q  = new DBQuery;
$q->addTable('eap');
$q->addQuery("id,nome,linha,coluna");
/*$q->addJoin('projects', 'p', 'project_id = forum_project');
$q->addWhere("forum_project = $project_id");
$q->addOrder('forum_project, forum_name');*/
$rc = $q->exec();
?>

<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
<tr>
	<th nowrap>&nbsp;</th>
	<th nowrap width="100%"><?php echo $AppUI->_('EAP Name');?></th>
	<th nowrap><?php echo $AppUI->_('Messages');?></th>
	<th nowrap><?php echo $AppUI->_('Last Post');?></th>
</tr>
<?php
while ($row = db_fetch_assoc($rc)) { ?>
<tr>
	<td nowrap align=center>
<?php
	if ($row["forum_owner"] == $AppUI->user_id) { ?>
		<A href="./index.php?m=forums&a=addedit&forum_id=<?php echo $row["forum_id"];?>"><img src="./images/icons/pencil.gif" alt="expand forum" border="0" width=12 height=12></a>
<?php } ?>
	</td>
	<td nowrap><A href="./index.php?m=forums&a=viewer&forum_id=<?php echo $row["forum_id"];?>"><?php echo $row["forum_name"];?></a></td>
	<td nowrap><?php echo $row["forum_message_count"];?></td>
	<td nowrap>
		<?php echo (intval($row["forum_last_date"]) > 0) ? $row["forum_last_date"] : 'n/a'; ?>
	</td>
</tr>
<tr>
	<td></td>
	<td colspan=3><?php echo $row["forum_description"];?></td>
</tr>
<?php }
$q->clear();
?>
</table>
<input type="button" value ="<?php echo $AppUI->_('new EAP'); ?>" class="button" onClick="window.alert('Nova EAP')">
<input type="button" value ="<?php echo $AppUI->_('Cadastrar'); ?>" class="button" onClick="window.alert('Cadastro info')">
<input type="button" value ="<?php echo $AppUI->_('Alterar'); ?>" class="button" onClick="window.alert('Alteração info')">
<input type="button" value ="<?php echo $AppUI->_('Excluir'); ?>" class="button" onClick="window.alert('Excluir info')">
<input type="button" value ="<?php echo $AppUI->_('Recuperar'); ?>" class="button" onClick="window.alert('Recuperar info')">

