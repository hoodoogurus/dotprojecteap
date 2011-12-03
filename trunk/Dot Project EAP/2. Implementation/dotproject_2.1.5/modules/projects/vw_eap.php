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
	
	//Buscando nome do projeto
	$q  = new DBQuery;
	$q->addTable('projects');
	$q->addWhere('project_id = ' . $project_id);
	$nome = $q->exec();
	$q->clear();
	
	/*//Busca valores da eap cadastrados no banco de dados
	$q  = new DBQuery;
	$q->addTable('eap');
	$q->addQuery("id,nome,linha,coluna");
	$rc = $q->exec();
	
	//Listando valores cadastrados no BD
	while ($row = db_fetch_assoc($rc)) { $cont = $cont+1 ?>
		<tr>
			<td nowrap><?php echo $row["nome"];?></td>
		</tr>
		<?php if($row["id"]>$var) $var = $row["id"]; 
	}
	$q->clear();
	
	//Insere uma linha
	$q  = new DBQuery;
	$q->addTable('eap');
	$q->addQuery("id,nome,linha,coluna");
	$q->addInsert(nome,'Build '.($var+1));
	$q->addInsert(linha,2);
	$q->addInsert(coluna,3);
	$q->prepareInsert();
	$q->exec();
	$q->clear();

	//Altera valor da linha
	$q  = new DBQuery;
	$q->addTable('eap');
	$q->addQuery("id,nome,linha,coluna");
	$q->addUpdate(nome,'Build '.($var-5));
	$q->addWhere("id = 1");
	$q->prepareUpdate();
	$q->exec();
	$q->clear();

	//Delete uma linha
	$var = $var-1;
	$q  = new DBQuery;
	$q->addTable('eap');
	$q->setDelete('eap');
	$q->addWhere("id = $var");
	$q->prepareDelete();
	$q->exec();
	$q->clear();*/
	
	?>
	<head>
		<meta name="author" content="Darko Bunic"/>
		<meta name="description" content="Drag and drop table content with JavaScript"/>
		<link rel="stylesheet" href="/dotproject/modules/projects/style2.css" type="text/css" media="screen" />
		<script type="text/javascript" src="/dotproject/modules/drag.js"></script>
		<!-- initialize drag and drop -->
		<script type="text/javascript">
			window.onload = function () {
				// reference to the REDIPS.drag class
				var rd = REDIPS.drag;
				// initialization
				rd.init();
				// only "smile" can be placed to the marked cell
				rd.mark.exception.d8 = 'smile';
				// prepare handlers
				rd.myhandler_clicked    = function () {document.getElementById('message').innerHTML = 'Selecionado'}
				rd.myhandler_moved      = function () {document.getElementById('message').innerHTML = 'Movido'}
				rd.myhandler_notmoved   = function () {document.getElementById('message').innerHTML = 'N&atilde;o movido'}
				rd.myhandler_dropped    = function () {document.getElementById('message').innerHTML = 'Arrastado'}
				rd.myhandler_switched   = function () {document.getElementById('message').innerHTML = 'Trocado'}
				rd.myhandler_clonedend1 = function () {document.getElementById('message').innerHTML = 'Inserido'}
				rd.myhandler_clonedend2 = function () {document.getElementById('message').innerHTML = 'Inserido'}
				rd.myhandler_notcloned  = function () {document.getElementById('message').innerHTML = 'N&atilde;o duplicado'}
				rd.myhandler_deleted    = function () {document.getElementById('message').innerHTML = 'Exclu&iacute;do'}
				rd.myhandler_undeleted  = function () {document.getElementById('message').innerHTML = 'N&atilde;o exclu&iacute;do'}
				rd.myhandler_cloned     = function () {
					// display message
					document.getElementById('message').innerHTML = 'Duplicado';
				}
				rd.myhandler_changed    = function () {
					// define linha e coluna atual
					var ri = REDIPS.drag.current_cell.parentNode.rowIndex,
						ci = REDIPS.drag.current_cell.cellIndex;
					// mostra a linha e a coluna atual
					document.getElementById('message').innerHTML = 'Modificado: ' + ri + ' ' + ci;
				}
			}
			// toggles trash_ask parameter defined at the top
			function toggle_confirm(chk) {
				REDIPS.drag.trash_ask = chk.checked;
			}
			// toggles delete_cloned parameter defined at the top
			function toggle_delete_cloned(chk) {
				REDIPS.drag.delete_cloned = chk.checked;
			}
			// enables / disables dragging
			function toggle_dragging(chk) {
				REDIPS.drag.enable_drag(chk.checked);
			}
			// function sets drop_option parameter defined at the top
			function set_drop_option(radio_button) {
				REDIPS.drag.drop_option = radio_button.value;
			}
			// show prepared content for saving
			function save(){
				// scan first table
				var content = REDIPS.drag.save_content(0);
				// if content doesn't exist
				if (content === '') {
					alert('EAP Vazia!');
				}
				// display query string
				else {
					//window.open('/my/multiple-parameters.php?' + content, 'Mypop', 'width=350,height=160,scrollbars=yes');
					window.open('multiple-parameters.php?' + content, 'Mypop', 'width=450,height=300,scrollbars=yes');
				}
			}
		</script>
		<title>dotProject EAP</title>
	</head>
<body>
	<table width="100%" border="0" cellpadding="2" cellspacing="1" class="tbl">
		
		<?php $row = db_fetch_assoc($nome); ?>
		<tr>
			<th colspan="6" class="mark"><?php echo $AppUI->_('EAP');?></th>
		</tr>
		
		<?PHP /*<td>
		<iframe src="/dotproject/modules/projects/index2.html" allowtransparency="true" width="100%" height="700">
		
		</td>?>*/
		
		 //<form action="/dotproject/modules/projects/index2.html">?>
		
		</table>
		<br>
		<br>
		<div id="drag">
		<div><input type="checkbox" class="checkbox" onclick="toggle_confirm(this)" title="Confirmar antes de excluir pacote de trabalho" checked="true"/><span class="message_line">Confirmar antes de excluir objeto</span></div>
      <table border="0" align="center" cellpadding="2" cellspacing="2">
	  <br>
  <tr>
    <td width="140" valign="middle"><div id="d1" class="drag t3 clone">
      <textarea cols="30" rows="2" style="width: 130px; height: 30px; text-align:center; max-width:130px; max-height:30px;"></textarea>
    </div></td>
    <td width="140" align="middle" valign="middle" class="mark" id="message" title="Voc&ecirc; N&atilde;o Pode Arrastar Para Aqui"><i>Status</i></td>
    <td width="140" valign="middle" class="trash" title="Trash">Lixeira</td>
    <td width="140" valign="middle" class="mark"><div><span class="message_line"><input type="button" value="Save" class="button" onclick="save()" title="Salvar"/></span></div></td>
</tr></table>

	  <table id="table1" >
        <colgroup><col width="100"/><col width="100"/><col width="100"/><col width="100"/><col width="100"/></colgroup>
			<tr>
				<td colspan="3" bgcolor="#FFFFFF" class="mark">&nbsp;</td>
					<td colspan="2" bgcolor="#FFFFFF" class="mark">&nbsp;</td>
					<td class="mark"><?php echo $AppUI->_($row["project_name"]);?></td>
					<td colspan="2" bgcolor="#FFFFFF" class="mark">&nbsp;</td>
					<td colspan="2" bgcolor="#FFFFFF" class="mark">&nbsp;</td>
		    </tr>
			<tr style="background-color: #eee" class="rd">
				<td height="34" colspan="3"></td>
				<td colspan="2"></td>
				<td bgcolor="#FFFFFF" class="mark"></td>
				<td colspan="2"></td>
				<td colspan="2"></td>
		</tr>
        
			<tr class="rd"> 
				<td class="mark" width="20"></td>
				<td colspan="2"></td>
				<td class="mark" width="20"></td>
				<td></td>
				<td bgcolor="#FFFFFF" class="mark"></td>
				<td class="mark" width="20"></td>
				<td></td>
				<td class="mark" width="20"></td>
				<td></td>
		    </tr>
			<tr style="background-color: #eee" class="rd">
			
				<td></td>
                <td></td>
                <td></td>
				<td colspan="2"></td>
				<td bgcolor="#FFFFFF" class="mark"></td>
				<td colspan="2"></td>
				<td colspan="2"></td>
		    </tr>
			<tr class="rd">
				<td></td>
				<td colspan="2"></td>
                </td>
				<td colspan="2"></td>
				<td bgcolor="#FFFFFF" class="mark"></td>
				<td colspan="2"></td>
				<td colspan="2"></td>
		    </tr>
			<tr style="background-color: #eee" class="rd">
				<td colspan="2"></td>
				<td></td>
				<td colspan="2"></td>
				<td bgcolor="#FFFFFF" class="mark"></td>
				<td colspan="2"></td>
				<td colspan="2"></td>
				<!-- <td><div id="d8" class="drag t1"><img id="smile_img" src="/wp-includes/images/smilies/icon_smile.gif"/></div></td> -->
		    </tr>
      </table>
		
		
	</div>
		
	</body>
</html>
