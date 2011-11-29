<html>
		<script type="text/javascript">
			var redips_url = '/javascript/drag-and-drop-table-row/';
		</script>
		<script type="text/javascript" src="header.js"></script>
		<script type="text/javascript" src="redips-drag-min.js"></script>
		<script type="text/javascript" src="script.js"></script>

	
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
<body>
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
		<tr>
			<th nowrap width="100%"><?php echo $AppUI->_('EAP Name');?></th>
		</tr>
		<?php
		//Listando valores cadastrados no BD
		while ($row = db_fetch_assoc($rc)) { $cont = $cont+1 ?>
			<tr>
				<td nowrap><?php echo $row["nome"];?></td>
			</tr>
			<?php if($row["id"]>$var) $var = $row["id"]; 
		}
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
		$q->addUpdate(nome,'Build '.($var-5));
		$q->addWhere("id = 1");
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
	<center>
		<div id="drag">
			<table id="tbl1" class="tbl" width="100%">
				<colgroup>
					<col width="30"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
				</colgroup>
				<tbody>
					<tr>
						<th colspan="6" class="mark"><?php echo $AppUI->_('DotProject EAP');?></th>
					</tr>
					<tr class="rd">
						<td class="rowhandler"><div class="drag row"></div></td>
						<td>Iniciacao</td>
						<td>Elaboracao</td>
						<td>Construcao</td>
						<td>Transicao</td>
						<td></td>
					</tr>
					<tr class="rl">
						<td class="rowhandler"><div class="drag row"></div></td>
						<td></td>
						<td></td>
						<td class="cdark"><div class="drag orange">Integrar modulos</div></td>
						<td></td>
						<td></td>
					</tr>
					<tr class="rl">
						<td class="rowhandler"><div class="drag row"></div></td>
						<td></td>
						<td class="cdark"><div class="drag blue">Relatorio Testes</div></td>
						<td></td>
						<td class="cdark"><div class="drag blue">Entrega do Projeto</div></td>
						<td></td>
					</tr>
					<tr class="rl">
						<td class="rowhandler"><div class="drag row"></div></td>
						<td class="cdark"><div class="drag orange">Elaborar EAP</div></td>
						<td></td>
						<td></td>
						<td></td>
						<td class="cdark"><div class="drag orange"></div></td>
					</tr>
					<tr class="rd">
						<td class="rowhandler"><div class="drag row"></div></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
					<tr>
						<td colspan="6" class="mark"><span id="msg">Linha de Mensagem</span></td>
					</tr>
				</tbody>
			</table>
			<table id="tbl2" class="tbl" width="100%">
				<colgroup>
					<col width="30"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
					<col width="100"/>
				</colgroup>
				<tbody>
					<tr>
						<th colspan="6" class="mark">Lista Atividades</th>
					</tr>
					<tr class="rd">
						<td> Orcamento Inicial</td>
						<td> Diagrama de Caso de Uso</td>
						<td></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>
				</tbody>
			</table>
		</div>
	</center>
		<form method="get" action="/dotproject/tests/calculadora_test.php">
		<input type="submit" value="<?php echo $AppUI->_('Teste'); ?>" class="button" />
</form>
	</body>
</html>
