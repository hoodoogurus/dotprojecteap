<?php

	if (!defined('DP_BASE_DIR')) {
		die('You should not access this file directly.');
	}
	
	GLOBAL $AppUI, $project_id;
	
	require_once('simpletest/autorun.php');
	?>
	
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
		<tr>
			<th nowrap width="100%"><?php echo $AppUI->_('EAP Name');?></th>
		</tr>
		<tr>
			<td nowrap><b><?php echo "Caso de teste da build 2 - Banco de Dados";?></b></td>
		</tr>
		<td>
		</td>
		<tr>
			<td nowrap><?php echo "Selecionar, Alterar, Incluir, Deletar dados da EAP";?></td>
		</tr>
		<?php
	
		class TestDoBD extends UnitTestCase {

			function testSelectBD() {
				$q  = new DBQuery;
				$q  = new DBQuery;
				$q->addTable('eap');
				$q->addQuery("id,nome,linha,coluna");
				$this->assertEqual($q->exec(), true);
				$q->clear();
			}
			
			function testInsertBD() {
				$q  = new DBQuery;
				$q->addTable('eap');
				$q->addQuery("id,nome,linha,coluna");
				$q->addInsert(nome,'Build Teste');
				$q->addInsert(linha,2);
				$q->addInsert(coluna,3);
				$q->prepareInsert();
				$this->assertEqual($q->exec(), true);
				$q->clear();
			}
			
			function testDeleteBD() {
				$q  = new DBQuery;
				$q->addTable('eap');
				$q->setDelete('eap');
				$q->addWhere("nome ='Build Teste'");
				$q->prepareDelete();
				$this->assertEqual($q->exec(), true);
				$q->clear();
			}
			
			function testUpdateBD() {
				$q  = new DBQuery;
				$q->addTable('eap');
				$q->addQuery("id,nome,linha,coluna");
				$q->addUpdate(nome,'Dot Project');
				$q->addWhere("id = 1");
				$q->prepareUpdate();
				$this->assertEqual($q->exec(), true);
				$q->clear();
			}
			
			//$q->clear();

		}
		?>
	
	</table>