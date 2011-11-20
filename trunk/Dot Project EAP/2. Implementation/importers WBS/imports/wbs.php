<?php
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}
/* Added by Wellison da Rocha Pereira, credited in license.txt */

class WBSImporter extends CImporter {

    public function import($AppUI) {
        $output = '';

        $company_id = (int) w2PgetParam($_POST, 'company_id', 0);

        if ($company_id == 0) {
            if (isset($_POST['new_company'])) {
                $companyName = w2PgetParam( $_POST, 'new_company', 'New Company');
                $company = new CCompany();
                $company->company_name = $companyName;
                $company->company_owner = $AppUI->user_id;
                ($AppUI->version_major <= 1 && $AppUI->version_minor <= 1) ? $company->store() : $company->store($AppUI);
                $company_id = $company->company_id;

                $output .= $AppUI->_('createcomp'). $companyName . '<br>';
                echo $output;
            } else {
                $error = $AppUI->_('emptycomp');
                return $error;
            }
        }

        $result = $this->_processProject($AppUI, $company_id, $_POST);
        if (is_array($result)) {
            $AppUI->setMsg($result, UI_MSG_ERROR);
            $AppUI->redirect('m=importers');
        }
        $this->project_id = $result;

        $q = new DBQuery();
        // Users Setup
        if (isset($_POST['users']) && is_array($_POST['users']) && $_POST['nouserimport'] != "true") {
            foreach($_POST['users'] as $ruid => $r) {
                $q->clear();

                if (empty($r['user_id'])) {
                    $result = $this->_processContact($AppUI, $r['user_username'], $company_id);
                    if (is_array($result)) {
                        $AppUI->setMsg($result, UI_MSG_ERROR);
                        $AppUI->redirect('m=importers');
                    }
                    $contact_id = $result;

                    //TODO:  Replace with the regular create users functionality
                    $q->addInsert('user_username', $r['user_username']);
                    $q->addInsert('user_contact', $contact_id);
                    $q->addTable('users');
                    $q->exec();
                    $insert_id = db_insert_id();

                    $r['user_id'] = $insert_id;
                }

                if (!empty($r['user_id'])) {
                    $resources[$ruid] = $r;
                }
            }
        }

        // Tasks Setup
        foreach ($_POST['tasks'] as $k => $task) {
            $result = $this->_processTask($AppUI, $this->project_id, $task);
            if (is_array($result)) {
                $AppUI->setMsg($result, UI_MSG_ERROR);
                $AppUI->redirect('m=importers');
            }
            $task_id = $result;

            // Task Parenthood
            $outline[$task['OUTLINENUMBER']] = $task_id;
            $q->clear();

            if (!strpos($task['OUTLINENUMBER'], '.')) {
                $q->addUpdate('task_parent', $task_id);
                $q->addWhere('task_id = ' . $task_id);
                $q->addTable('tasks');
            } else {
                $parent_string = substr($task['OUTLINENUMBER'], 0, strrpos($task['OUTLINENUMBER'], '.'));
                $parent_outline = isset($outline[$parent_string]) ? $outline[$parent_string] : $task_id;
                $q->addUpdate('task_parent', $parent_outline);
                $q->addWhere('task_id = ' . $task_id);
                $q->addTable('tasks');
            }
            $q->exec();

            $_POST['tasks'][$k]['task_id'] = $task_id;
            $task['task_id'] = $task_id;

            // Resources (Workers)
            $tasks[$task['UID']] = $task;

            if (count($task['resources']) > 0) {
                foreach($task['resources'] as $uk => $user) {
                    $alloc = $task['resources_alloc'][$uk];
                    if ($alloc > 0 && $resources[$user]['user_id'] > 0) {
                        $q->clear();
                        $q->addInsert('user_id', $resources[$user]['user_id']);
                        $q->addInsert('task_id', $task_id);
                        $q->addInsert('perc_assignment', $alloc);
                        $q->addTable('user_tasks');
                        $q->exec();
                    }
                }
            }

            // Task Dependencies
            if (is_array($task['dependencies'])) {
                foreach($task['dependencies'] as $task_uid) {
                    if ($task_uid > 0 && $tasks[$task_uid]['task_id'] > 0) {
                        $q->clear();
                        $q->addInsert('dependencies_task_id', $task_id);
                        $q->addInsert('dependencies_req_task_id', $tasks[$task_uid]['task_id']);
                        $q->addTable('task_dependencies');
                        $q->exec();
                    }
                }
            }
        }
        $this->_deDynamicLeafNodes($this->project_id);
        addHistory('projects', $this->project_id, 'add', $projectName, $this->project_id);
        return $output;
    }

    public function preview() {
        //The whole preview is based on the SimpleXML. It was a easier decision to get atributes from the
        //wbs files and it actually worked! I tried XMLReader but i've failed completely
        global $AppUI;
        $perms = &$AppUI->acl();

        $output = '';
        $data = $this->scrubbedData;

        $reader = simplexml_load_string($data);

        $project_name = $reader->proj->summary['Title'];
        if (empty($project_name))
            $project_name=$this->proName;
        $q = new DBQuery();
        $output .= '
          <table width="100%">
          <tr>
          <td align="right">' . $AppUI->_('Company Name') . ':</td>';

            $projectClass = new CProject();

            $output .= $this->_createCompanySelection($AppUI, $tree['COMPANY']);
            $output .= $this->_createProjectSelection($AppUI, $project_name);

            $users = $perms->getPermittedUsers('projects');
            $output .= '<tr><td align="right">' . $AppUI->_('Project Owner') . ':</td><td>';
            $output .= arraySelect( $users, 'project_owner', 'size="1" style="width:200px;" class="text"', $AppUI->user_id );
            $output .= '<td/></tr>';

            $pstatus =  w2PgetSysVal( 'ProjectStatus' );
            $output .= '<tr><td align="right">' . $AppUI->_('Project Status') . ':</td><td>';
            $output .= arraySelect( $pstatus, 'project_status', 'size="1" class="text"', $row->project_status, true );
            $output .= '<td/></tr>';

            $output .= '
          <tr>
            <td align="right">' . $AppUI->_('Start Date') . ':</td>
            <td><input type="text" name="project_start_date" value="' . $reader->proj->summary['Start']. '" /></td>
          </tr>
          <tr>
            <td align="right">' . $AppUI->_('End Date') . ':</td>
            <td><input type="text" name="project_end_date" value="' . $reader->proj->summary['Finish'].'" /></td>
          </tr>
          <tr>
            <td colspan="2">' . $AppUI->_('Users') . ':</td>
          </tr>';
        // Users (Resources)
        $resources=array();
        $resources[0]='';

        //check the existence of resources before trying to import
        if ($this->user_control)
        {
            $q = new DBQuery();
            $trabalhadores=$reader->proj->resources->children();
            foreach($trabalhadores as $r) {
                $q->clear();
                $q->addQuery('user_id');
                $q->addTable('users');
                $q->leftJoin('contacts', 'c', 'user_contact = contact_id');
                $q->addWhere("user_username LIKE '{$r['name']}' OR CONCAT_WS(contact_first_name, ' ', contact_last_name) = '{$r['name']}'");
                $r['LID'] = $q->loadResult();
                if (!empty($r['name'])) {
                    $output .= '
                  <tr>
                    <td>' . $AppUI->_('User name') . ': </td>
                    <td>
                        <input type="text" name="users[' . $r['uid'] . '][user_username]" value="' . ucwords(strtolower($r['name'])) . '"' . (empty($r['LID'])?'':' readonly') . ' />
                        <input type="hidden" name="users[' . $r['uid'] . '][user_id]" value="' . $r['LID'] . '" />
                        (' . $AppUI->_('Resource UID').": ".$r['uid'] . ')';
                    if (function_exists('w2PUTF8strlen')) {
                        if (w2PUTF8strlen($r['name']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $AppUI->_('username_min_len.') . '</em>';
                        }
                    } else {
                        if (strlen($r['name']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $AppUI->_('username_min_len.') . '</em>';
                        }
                    }
                    $output .= '</td></tr>';
                    $resources[sizeof($resources)] = strtolower($r['name']);
                }
            }
        }
            // Insert Tasks
            $output .= '
      <tr>
        <td colspan="2">' . $AppUI->_('Tasks') . ':</td>
      </tr>
      <tr>
        <td colspan="2">
        <table width="100%">
        <tr>
            <th></th>
            <th>' . $AppUI->_('Name') . '</th>
            <th>' . $AppUI->_('Start Date') . '</th>
            <th>' . $AppUI->_('End Date') . '</th>
            <th>' . $AppUI->_('user allocations') . '</th>
        </tr>';
        foreach($reader->proj->tasks->children() as $task) {
            if ($task['ID'] != 0 && trim($task['Name']) != '') {
                $newWBS=$this->montar_wbs($task['OutlineLevel']);
                $note=" ";
                $output .= '<tr><td>';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][UID]" value="' . $task['ID'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][OUTLINENUMBER]" value="' . $newWBS . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_name]" value="' . $task['Name'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_description]" value="' . $note . '" />';

                $priority = 0;
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_priority]" value="' . $priority . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_start_date]" value="' . $task['Start'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_end_date]" value="' . $task['Finish'] . '" />';

                $myDuration = $this->dur($task['Duration']);

                $percentComplete = isset($task['PercentComplete']) ? $task['PercentComplete'] : 0;
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_duration]" value="' . $myDuration . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_percent_complete]" value="' . $percentComplete . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_description]" value="' . $note . '" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_owner]" value="'.$AppUI->user_id.'" />';
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_type]" value="0" />';

                $milestone = ($task['Milestone'] == 'yes') ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$task['ID'].'][task_milestone]" value="' . $milestone . '" />';

                $temp = 0;
                if (!empty($task['UniqueIDPredecessors'])) {
                    $x=strpos($task['UniqueIDPredecessors'],",");
                    foreach ($task['UniqueIDPredecessors'] as $dependency) {
                        $output .= '<input type="hidden" name="tasks['.$task['ID'].'][dependencies][]" value="' . $dependency['UniqueIDPredecessors'] . '" />'; //'.$task['ID'].'-'.$temp.'
                        ++$temp;
                    }
                }
                $output .= '</td><td>';

                for($i = 1; $i < $task['OutlineLevel']; $i++) {
                    $output .= w2PshowImage(w2PfindImage('corner-dots.gif')) . '&nbsp;';
                }

                $output .= $task['Name'].'<br /><hr />'.$task['NOTES'].'<hr size="2" /></td><td>'.$task['Start'].'</td><td>'.$task['Finish'].'</td><td>';
                $recurso='';
                $perc = 100;
                //This is a bizarre function i've made to associate the resources with their tasks
                //If there's only one resource associate to a task, it skip the while. If there's more than one
                //the loop take care of it until the last resource
                //strange but it works, that's my moto.
                if (!empty($task['Resources']))
                {
                    $x=0;
                    $y=strpos($task['Resources'],';');
                    while (!empty($y))
                    {
                        $recurso=substr($task['Resources'],$x,($y-$x));
                        $output.= arraySelect($resources, 'tasks['.$task['ID'].'][resources][]', '', $recurso);
                        $output.= '<input type="text" name=tasks['.$task['ID'].'][resources_alloc][]" value="' . sprintf("%.0f", $perc) . '" size="3" />%<br />';
                        $x=$y+1;
                        $y=strpos($task['Resources'],';',$x);

                    }
                    $recurso=substr($task['Resources'],$x);
                    $output.= arraySelect($resources, 'tasks['.$task['ID'].'][resources][]', '', $recurso);
                    $output.= '<input type="text" name=tasks['.$task['ID'].'][resources_alloc][]" value="' . sprintf("%.0f", $perc) . '" size="3" />%<br />';
                }
                $output .= '</td></tr>';
            }
        }
        $output .= '</table></td></tr>';

        $output .= '</table>';
        return $output;
    }

    protected function loadFile($AppUI) {
        $filename = $_FILES['upload_file']['tmp_name'];
        $pos=strrpos($_FILES['upload_file']['name'],".");
        $fileName=substr($_FILES['upload_file']['name'],0,$pos);
        $file = fopen($filename, "r");
        $filedata = fread($file, $_FILES['upload_file']['size']);
        fclose($file);

        if (substr_count($filedata, '<tasks>') < 1) {
            return false;
        }
        $x = strpos($filedata, '<calendar>');
        $header = substr($filedata, 0, $x);
        $summaryNode = $this->stripper("<summary ","/>",$filedata);
        $taskNodes = $this->stripper("<tasks>","</tasks>",$filedata);
        $endNodes = "</proj></project>";

        if (substr_count($filedata, '<resources>') < 1) {
            echo "<b>".$AppUI->_("Failure")."</b> ".$AppUI->_("impinfo")."<BR>";
            $filedata=$header.$summaryNode.$taskNodes.$endNodes;
            $user_control=false;
        } else {
            $userNodes=$this->stripper("<resources>","</resources>",$filedata);
            $filedata=$header.$summaryNode.$userNodes.$taskNodes.$endNodes;
            $user_control=true;
        }
        /*
         * O resultado esperado Ã© esse:
         * <project>
         * 		<proj attributes....>
         * 			<summary ...../>
         * 			<resources>
         * 				<resource id="0"......./>
         * 				...
         * 				<resource id="X"......./>
         * 			</resources>
         * 			<tasks>
         * 				<task id="0"......./>
         * 				...
         * 				<task id="X"......./>
         * 			</tasks>
         * 		</proj>
         * </project>
         */

        $this->scrubbedData = $filedata;
        $this->proName=$fileName;
        return true;
    }
    /* Extrai uma determinada tag xml de uma string que com
     * conteúdo de um arquivo
     * @param    string    $startTag Tag de inicio
     *          string    $endTag    Tag de final
     *             string    $data Escopo onde vai ser procurado a tag
    */
    private function stripper($startTag,$endTag,$data) {
        $x=strpos($data, $startTag);
        $y=strpos($data, $endTag,$x)+strlen($endTag);
        $data = substr($data, $x, ($y-$x));
        return $data;
    }
    private function dur($duration) {
        //Not a very good duration function, just take the number of days and multiplies it for 8
        $Offset = strpos($duration, 'd');
        $x = substr($duration, 0, $Offset);
        return ($x*8);
    }
    private function montar_wbs($outline){
        /*This funcion build the wbs path of a task. WBS Gantt Chart Pro don't write this in their files
        so the program must calculate it*/
        global $wbsAnt,$nivelAnt;
        if ($outline==0) {

        } else if ($outline==1) {
            $wbs= "1";
        } else if ($outline==$nivelAnt) {
            $x=strripos($wbsAnt,".")+1;
            $inicioWBS=substr($wbsAnt,0,$x);
            $fimWBS=substr($wbsAnt,$x);
            $fimWBS++;
            $wbs=$inicioWBS.$fimWBS;
        } else if ($outline>$nivelAnt) {
            $wbs=$wbsAnt.".1";
        } else if ($outline<$nivelAnt) {
            $y=0;
            $x=0;
            $n=0;
            while ($n<$outline) {
                $x=$y;
                $x++;
                $y=strpos($wbsAnt,".",$x);
                $n++;
            }

            $inicioWBS=substr($wbsAnt,0,$x);
            $fimWBS=substr($wbsAnt,$x,($y-$x));
            $fimWBS++;
            $wbs=$inicioWBS.$fimWBS;
        }
        $wbsAnt=$wbs;
        $nivelAnt=$outline;
        return $wbs;
    }

}