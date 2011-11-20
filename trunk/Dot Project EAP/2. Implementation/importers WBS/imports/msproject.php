<?php
if (!defined('W2P_BASE_DIR'))	{
  die('You should not access this file directly.');
}

include_once('xml.inc.php');

class MSProjectImporter extends CImporter {

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

                if (!empty($r['user_username'])) {
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
                } else {
                    $r['user_id'] = $r['user_userselect'];
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

            $task['task_id'] = $task_id;
            $tasks[$task['UID']] = $task;

            // Resources (Workers)
            if (count($task['resources']) > 0) {
                $sql = "DELETE FROM user_tasks WHERE task_id = $task_id";
                db_exec($sql);
                $resourceArray = array();

                foreach($task['resources'] as $uk => $user) {
                    $alloc = $task['resources_alloc'][$uk];

                    if ($alloc > 0 && $resources[$user]['user_id'] > 0) {
                        $q->clear();
                        if (!in_array($resources[$user]['user_id'], $resourceArray)) {
                            $q->addInsert('user_id', $resources[$user]['user_id']);
                            $q->addInsert('task_id', $task_id);
                            $q->addInsert('perc_assignment', $alloc);
                            $q->addTable('user_tasks');
                          $q->exec();
                        }
                      $resourceArray[] = $resources[$user]['user_id'];
                    }
                }
            }
        }

        //dependencies have to be handled alone after all tasks have been saved since the
        //predecessor (ms project term) task might come later and the associated task id
        //is not yet available.
        foreach ($tasks as $k => $task) {
            // Task Dependencies

            if (isset($task['dependencies']) && is_array($task['dependencies'])) {
                $sql = "DELETE FROM task_dependencies WHERE dependencies_task_id = $task_id";
                db_exec($sql);
                $dependencyArray = array();

                foreach($task['dependencies'] as $task_uid) {
                    if ($task_uid > 0 && $tasks[$task_uid]['task_id'] > 0) {
                        $q->clear();

                        if (!in_array($tasks[$task_uid]['task_id'], $dependencyArray)) {
                            $q->addInsert('dependencies_task_id', $task['task_id']);
                            $q->addInsert('dependencies_req_task_id', $tasks[$task_uid]['task_id']);
                            $q->addTable('task_dependencies');
                            $q->exec();
                        }
                        $dependencyTestArray[] = $tasks[$task_uid]['task_id'];
                    }
                }
            }
        }
        $this->_deDynamicLeafNodes($this->project_id);
        addHistory('projects', $this->project_id, 'add', $projectName, $this->project_id);
        return $output;
    }

    public function preview() {
        global $AppUI;
        $perms = &$AppUI->acl();

        $output = '';
        $data = $this->scrubbedData;
        $tree = xmlParse($data);
        $i = 0;
        if ((int) $tree[0]['children'][0]['cdata']) {
            $i = 1;
        }
        $project_name = str_replace('.xml', '', $tree[0]['children'][$i]['cdata']);
        $tree = rebuildTree($tree);
        $tree = $tree['PROJECT'][0];

        $output .= '
            <script type="text/javascript" src="'.$base.'js/utils.js"></script>

            <script type="text/javascript">
                function ToggleUserFields() {
                    userFields = document.getElementsByName("userRelated");
                    for (i=0; i<userFields.length; i++) {
                        if (userFields[i].style.visibility == "hidden") {
                            userFields[i].style.visibility = "visible";
                        } else {
                            userFields[i].style.visibility = "hidden";
                        }
                    }
                }

                function addNew_choice(selection) {
                    var selValue = selection.options[selection.selectedIndex].text;
                    return selValue == "Add New";
                }

                function valid(menu, txt) {
                    if (txt.value == "") {
                        if (addNew_choice(menu)) {
                            alert("You need to type the user name to add into the text box");
                            return false;
                        } else {
                            return true;
                        }
                    } else {
                        if (!addNew_choice(menu)) {
                            alert("Incompatible selection");
                            return false;
                        } else {
                            return true;
                        }
                    }
                }

                function activateTxtFld(field) {
                    field.style.visibility  = "visible";
                    field.focus();
                }

                function process_input(textfield) {
                    var parents = getParent(textfield, "tr");
                    var selRow = (parents ? parents[0] : null);
                    var selection = selRow.cells[1].children[0];
                    adjust_task_users(selection.name.match(/\d+/)[0], textfield.value);
                }

                function process_choice(selection) {
                    var parents = getParent(selection, "tr");
                    var selRow = (parents ? parents[0] : null);
                    var textfield = selRow.cells[2].children[0];

                    if (addNew_choice(selection)) {
                        if (typeof textfield != "undefined") {
                            activateTxtFld(textfield);
                        }
                    } else {
                        if (typeof textfield != "undefined") {
                            textfield.style.visibility  = "hidden";
                            textfield.value = "";
                        }
                        adjust_task_users(selection.name.match(/\d+/)[0], selection.options[selection.selectedIndex].text);
                    }
                }

                function check_choice(textfield) {
                    var parents = getParent(textfield, "tr");
                    var selRow = (parents ? parents[0] : null);
                    var menu = selRow.cells[1].children[0];

                    if (!addNew_choice(menu)) {
                        textfield.blur();
                        alert("Please check your menu selection first");
                        menu.focus();
                    }
                }

                function adjust_task_users(uid, newText) {
                    var selects = document.getElementsByTagName("select");

                    if (selects) {
                        for (var i=0; i < selects.length; i++) {
                            var selName = selects[i].name;
                            if (selName.search(/^tasks\[\d+\]\[resources\]\[\]/) != -1) {
                                var taskuser = selects[i];
                                for (var j=0; j < taskuser.options.length; j++){
                                    if (taskuser.options[j].value == uid) {
                                        taskuser.options[j].text = newText;
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            </script>
            <table width="100%">
            <tr>
            <td align="right">' . $AppUI->_('Company Name') . ':</td>';

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
                <td><input type="text" name="project_start_date" value="%%STARTDATE%%" /></td>
            </tr>
            <tr>
                <td align="right">' . $AppUI->_('End Date') . ':</td>
                <td><input type="text" name="project_end_date" value="' . $tree['FINISHDATE'] . '" /></td>
            </tr>
            <tr>
                <td align="right">' . $AppUI->_('Do Not Import Users') . ':</td>
                <td><input type="checkbox" name="nouserimport" value="true" onclick="ToggleUserFields()" /></td>
            </tr>
            <tr>
                <td colspan="2">' . $AppUI->_('Users') . ':</td>
            </tr>
            <tr>
                <td colspan="2"><div name="userRelated"><br /><em>'.$AppUI->_('userinfo').'</em>
            <table>';

		$q = new DBQuery();
        $q->addQuery('u.*,co.*,concat(co.contact_first_name,\' \',co.contact_last_name) as full_name,comp.company_name');
        $q->addTable('users', 'u');
        $q->addJoin('contacts','co','co.contact_id = u.user_contact', 'inner');
        $q->addJoin('companies','comp','comp.company_id=co.contact_company');
        $q->addOrder('co.contact_first_name, co.contact_last_name');
        $workers = $q->loadList();

        $percent = array(0 => '0', 5 => '5', 10 => '10', 15 => '15', 20 => '20', 25 => '25', 30 => '30', 35 => '35', 40 => '40', 45 => '45', 50 => '50', 55 => '55', 60 => '60', 65 => '65', 70 => '70', 75 => '75', 80 => '80', 85 => '85', 90 => '90', 95 => '95', 100 => '100');

        $q = new DBQuery();
        // Users (Resources)
        $resources = array();
        $resources[0] = '';

        foreach($tree['RESOURCES'][0]['RESOURCE'] as $r) {
            $q->clear();
            $q->addQuery('user_id');
            $q->addTable('users');
            $q->leftJoin('contacts', 'c', 'user_contact = contact_id');
            $myusername =  mysql_real_escape_string(strtolower($r['NAME']));
            $q->addWhere("LOWER(user_username) LIKE '{$myusername}' OR LOWER(CONCAT_WS(' ', contact_first_name, contact_last_name)) = '{$myusername}'");
            $r['LID'] = $q->loadResult();

            if (!empty($myusername)) {
                $output .= '
                    <tr>
                    <td>' . $AppUI->_('User name') . ': </td>
                    <td align="left">
                    <select name="users['.$r['UID'].'][user_userselect]" onChange="process_choice(this);">';

                if (empty($r['LID'])) {
                    $resources[$r['UID']] = ucwords(strtolower($r['NAME']));
                    $output .= '
                    <option value="-1" selected>'.$AppUI->_('Add New').'</option>\n';
                }

                foreach ($workers as $user) {
                    if (!empty($r['LID']) && $user["user_id"]==$r['LID']) {
                        $resources[$r['UID']] = $user["contact_first_name"].' '.$user["contact_last_name"];
                    }
                    $output .= '<option value="'.$user["user_id"].'"'.(!empty($r['LID']) && $user["user_id"]==$r['LID']?"selected":"").'>'.$user["contact_first_name"].' '.$user["contact_last_name"].'</option>\n';
                }
                $output .= '</select></td><td>';

                if (empty($r['LID'])) {
                    $output .= '<input type="text" name="users['.$r['UID'].'][user_username]" value="' . ucwords(strtolower($r['NAME'])) . '" onfocus="check_choice(this)" onChange="process_input(this);"/>';
                } else {
                    $output .= '&nbsp;';
                }
                $output .= '</td><td>(' . $AppUI->_('Resource') . ' UID ' . $r['UID'] . ')</td>';
                if (empty($r['LID'])) {
                    if (function_exists('w2PUTF8strlen')) {
                        if (w2PUTF8strlen($r['NAME']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $AppUI->_('username_min_len.') . '</em>';
                        }
                    } else {
                        if (strlen($r['NAME']) < w2PgetConfig('username_min_len')) {
                            $output .= ' <em>' . $AppUI->_('username_min_len.') . '</em>';
                        }
                    }
                }
                $output .= '</td></tr>';
            }
        }

        // Insert Tasks
        $output .= '
            </table>
            </div></td></tr>';

        $output .= '
            <tr>
            <td colspan="2">' . $AppUI->_('Tasks') . ':</td>
            </tr>
            <tr>
            <td colspan="2">
            <table width="100%" style="border-collapse:collapse;">
            <tr>
            <th></th>
            <th>' . $AppUI->_('Name') . '</th>
            <th>' . $AppUI->_('Start Date') . '</th>
            <th>' . $AppUI->_('End Date') . '</th>
            <th>' . $AppUI->_('user allocations') . '</th>
            </tr>';

        $taskCount = 0;
        foreach($tree['TASKS'][0]['TASK'] as $k => $task) {
            if ($task['UID'] != 0 && trim($task['NAME']) != '') {
                $output .= '<tr style="border:1px solid #000; margin-bottom:4px;"><td>';
                $output .= '<input type="hidden" name="tasks['.$k.'][UID]" value="' . $task['UID'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][OUTLINENUMBER]" value="' . $task['OUTLINENUMBER'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_name]" value="' . $task['NAME'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_description]" value="' . $task['NOTES'] . '" />';

                $priority = ($task['PRIORITY'] > 0) ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_priority]" value="' . $priority . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_start_date]" value="' . $task['START'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_end_date]" value="' . $task['FINISH'] . '" />';
                if ($taskCount == 0) {
                    $output = str_replace('%%STARTDATE%%', $task['START'], $output);
                }

                $myDuration = $this->_calculateWork($task['REGULARWORK'], $task['DURATION']);

                $percentComplete = isset($task['PERCENTCOMPLETE']) ? $task['PERCENTCOMPLETE'] : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_duration]" value="' . $myDuration . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_percent_complete]" value="' . $percentComplete . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_description]" value="' . $task['NOTES'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_dynamic]" value="' . $task['TYPE'] . '" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_owner]" value="'.$AppUI->user_id.'" />';
                $output .= '<input type="hidden" name="tasks['.$k.'][task_type]" value="0" />';

                $milestone = ($task['MILESTONE'] == '1') ? 1 : 0;
                $output .= '<input type="hidden" name="tasks['.$k.'][task_milestone]" value="' . $milestone . '" />';
                if (is_array($task['PREDECESSORLINK'])) {
                    foreach ($task['PREDECESSORLINK'] as $dependency) {
                        $output .= '<input type="hidden" name="tasks['.$k.'][dependencies][]" value="' . $dependency['PREDECESSORUID'] . '" />';
                    }
                }
                $output .= '</td><td>';

                $tasklevel = substr_count($task['OUTLINENUMBER'], '.');
                for($i = 0; $i < $tasklevel; $i++) {
                    $output .= w2PshowImage(w2PfindImage('corner-dots.gif')) . '&nbsp;';
                }

                $output .= $task['NAME'];

                if ($milestone) {
                    $output .= '<img src="' . w2PfindImage('icons/milestone.gif', $m) . '" border="0" />';
                }

                if (!empty($task['NOTES'])) {
                    $output .= '<br /><hr />'.$task['NOTES'].'<hr size="2" />';
                }
					
                $output .=
                    '</td>
                    <td>'.$task['START'].'</td>
                    <td>'.$task['FINISH'].'</td>
                    <td>';

                foreach($tree['ASSIGNMENTS'][0]['ASSIGNMENT'] as $a) {
                    if ($a['TASKUID'] == $task['UID']) {
                        if ($this->_calculateWork($task['REGULARWORK'], $task['DURATION']) > 0) {
                            $perc = 100 * $a['UNITS'];
                        }
                        $output .= '<div name="userRelated">';
                        $output .= arraySelect($resources, 'tasks['.$k.'][resources][]', '', $a['RESOURCEUID']);
                        $output .= '&nbsp;';
                        $output .= arraySelect($percent, 'tasks['.$k.'][resources_alloc][]', 'size="1" class="text"', intval(round($perc/5))*5) . '%';
                        $output .= '</div>';
                    }
                }
                $taskCount++;
                $output .= '</td></tr>';
            }
        }
        $output .= '</table></td></tr>';

        $output .= '</table>';
        return $output;
    }

    public function loadFile($AppUI) {
        $filename = $_FILES['upload_file']['tmp_name'];
        $pos=strrpos($_FILES['upload_file']['name'],".");
        $fileName=substr($_FILES['upload_file']['name'],0,$pos);

        $file = fopen($filename, "r");
        $this->scrubbedData = fread($file, $_FILES['upload_file']['size']);
        fclose($file);

        if (substr_count($this->scrubbedData, '<Resource>') <= 1) {
            echo "<br />";
            echo $AppUI->_("impinfo");
            echo "<br/><br/>";
        }

        $this->proName=$fileName;
        return true;
    }

    private function _calculateWork($regularWork, $regularDuration = '') {
        $hourOffset = strpos($regularWork, 'H', 0);
        $minOffset = strpos($regularWork, 'M', 0);
        $hours = substr($regularWork, 2, $hourOffset - 2);
        $minutes = substr($regularWork, $hourOffset + 1, $minOffset - $hourOffset - 1);
        $workHours = $hours + $minutes/60;

        if ($workHours == 0 && $regularDuration != '') {
            $workHours = $this->_calculateWork($regularDuration);
        }

        return round($workHours, 2);
    }
}