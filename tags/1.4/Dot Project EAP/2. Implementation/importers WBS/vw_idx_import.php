<?php
if (!defined('W2P_BASE_DIR'))
{
  die('You should not access this file directly.');
}
/* Modified by Thomas Zander Version 1.3 thomas-zander@arcor.de  */
/* Modified by Wellison da Rocha Pereira wellisonpereira@gmail.com */

global $AppUI, $canRead, $canEdit, $m;

$action = w2PgetParam($_POST, 'action', '');
$filetype = w2PgetParam($_POST, 'filetype', null);

$myMemLimit = ini_get('memory_limit');
$myMemLimit = intval(substr($myMemLimit, 0, strlen($myMemLimit) - 1));
$maxFileSize = substr(ini_get('memory_limit'), 0, strlen(ini_get('memory_limit') - 1)) * 1024* 1000;

if ($myMemLimit < 256) {
    ini_set('memory_limit', '256M');
    ini_set('post_max_size', '256M');
    ini_set('upload_max_filesize', '256M');
}

switch($action) {
    case 'import':
        if ($_FILES['upload_file']['size'] == 0) {
            unset($action);
            echo "<br /><b>".$AppUI->_("Failure")."</b> ".$AppUI->_('You must select a file to upload')."<br /><br />";
        ?>
        <form enctype="multipart/form-data" action="index.php?m=importers" method="post">
          <input type="file" name="upload_file" size="60" />
          <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxFileSize; ?>" />
          <input type="hidden" name="action" value="import" />
          <input type="submit" name="submit" value="<?php echo $AppUI->_("Import Data"); ?>" />
        </form>
        <?php
            break;
        } else  {
            $fileext = substr($_FILES['upload_file']['name'], -4);
            $importer = CImporter::resolveFiletype($fileext);
            if (($fileext == '.xml') || ($fileext == '.wbs')) {
                $action = 'preview';
            }
            if (!$importer->loadFile($AppUI)) {
                unset($action);
                echo "<b>".$AppUI->_("Failure")."</b> ".$AppUI->_('taskerror');
                break;
            }
        }
    case 'preview':
        ?><form name="preForm" action="?m=importers" method="post">
            <?php echo $importer->preview(); ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="filetype" value="<? echo $importer->fileType;?>">
            <input type="submit" class="text" name="submit" value="<? echo $AppUI->_('Import');?>" onClick="validateImport(); return false;">
            <input type="submit" class="text" name="submit" value="<? echo $AppUI->_('cancel');?>" onClick="this.form.action.value='cancel'">
        </form>
        <?php
        break;
    case 'save':
        $importer = CImporter::resolveFiletype($_POST['filetype']);

        echo $importer->import($AppUI);
        if (isset($error)) {
            echo $AppUI->_('Failure') . $error;
        } else {
            echo $AppUI->_('Success');
            if ($importer->project_id) {
                echo '<br />';
                echo '<a href="m=projects&a=view&project_id='.$importer->project_id.'">';
                echo $AppUI->_('View the project here.');
                echo '</a>';
            }
        }
        unset($action);
        break;
    case 'cancel':
        echo $AppUI->_('Import cancelled.  Reason:').$error;
        unset($action);
        break;
    default:
        // No specific action set, go back to the form
        ?>
        <form enctype="multipart/form-data" action="index.php?m=importers" method="post">
          <input type="file" name="upload_file" size="60" />
          <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxFileSize; ?>" />
          <input type="hidden" name="action" value="import" />
          <input type="submit" name="submit" value="<?php echo $AppUI->_("Import Data"); ?>" />
        </form>
        <?php
}