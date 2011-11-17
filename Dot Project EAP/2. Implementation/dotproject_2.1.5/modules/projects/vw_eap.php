<?php 
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

GLOBAL $AppUI, $project_id;

$cont = 0;

//Busca valores da eap cadastrados no banco de dados
$q  = new DBQuery;
$q->addTable('eap');
$q->addQuery("id,nome,linha,coluna");
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
//Listando valores cadastrados no BD

while ($row = db_fetch_assoc($rc)) { $cont = $cont+1?>
<tr>
	<td nowrap align=center>
	</td>
	<td nowrap><?php echo $row["nome"];?></td>
</tr>
	<?php if($row["id"]>$var) $var = $row["id"]; ?>
<tr>
	<td></td>
	<td colspan=3><?php echo $row["forum_description"];?></td>
</tr>
<?php }
$q->clear();
?>

<?php

//Insere uma linha
	$q  = new DBQuery;
	$q->addTable('eap');
	//$sql = "INSERT INTO `letsnight_1`.`dotp_eap` (`id`, `nome`, `linha`, `coluna`) VALUES (NULL, 'Build 3', '2', '3')";
	$q->addQuery("id,nome,linha,coluna");
	$q->addInsert(nome,'Build '.($var+1));
	$q->addInsert(linha,2);
	$q->addInsert(coluna,3);
	$q->prepareInsert();
	$q->exec();
	$q->clear();
?>

<?php 
//Altera valor da linha
$q  = new DBQuery;
$q->addTable('eap');
$q->addQuery("id,nome,linha,coluna");
$q->addUpdate(nome,'Build'.($var-5));
$q->addWhere("id = 58");
$q->prepareUpdate();
$q->exec();
$q->clear();
?>

<?php
//Delete uma linha
$var = $var-1;
$q  = new DBQuery;
$q->addTable('eap');
$q->setDelete('eap');
$q->addWhere("id = $var");
$q->prepareDelete();
$q->exec();
$q->clear();
?>



</table>

<form method="get" action="./tratarBotoesPHP.php">
<input type="hidden" name="m" value="forums" />
<input type="hidden" name="a" value="addedit" />
<input type="hidden" name="forum_project" value="<?php echo $project_id; ?>" />
<input type="submit" value="<?php echo $AppUI->_('new forum'); ?>" class="button" />
</form>
