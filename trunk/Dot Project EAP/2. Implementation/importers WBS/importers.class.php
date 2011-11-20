<?php
if (!defined('W2P_BASE_DIR')){
  die('You should not access this file directly.');
}

class CImporter {
    public $fileType = '';
    public $importType='';
    public $project_id = 0;

    protected $scrubbedData = '';
    protected $importClassname = '';
    protected $proName='';
    protected $user_control='';

    public static function resolveFiletype($filetype = '') {
        include_once 'imports/msproject.php';
        /* Added by Wellison da Rocha Pereira, credited in license.txt */
        include_once 'imports/wbs.php';

        switch($filetype) {
            case '.wbs':
                $importer = new WBSImporter();
                $importer->fileType = '.wbs';
                break;
            case '.xml':
            default:
                $importer = new MSProjectImporter();
                $importer->fileType = '.xml';
        }
        return $importer;
    }

    protected function _createCompanySelection($AppUI, $companyInput) {
        $company = new CCompany();
        $companyMatches = $company->getCompanyList($AppUI, -1, $companyInput);
        $company_id = (count($companyMatches) == 1) ? $companyMatches[0]['company_id'] : $AppUI->user_company;
        $companies = $company->getAllowedRecords($AppUI->user_id, 'company_id,company_name', 'company_name');
        $companies = arrayMerge(array('0' => ''), $companies);

        $output .= '<td>' .
            arraySelect($companies, 'company_id', ' onChange=this.form.new_company.value=\'\'', $company_id) .
            '<input type="text" name="new_company" value="' . (($company_id > 0) ? '' : $companyInput) . '" />';
        if ($company_id == 0) {
            $output .= '<br /><em>'.$AppUI->_('compinfo').'</em>';
        }
        $output .= '</td></tr>';

        return $output;
    }

    protected function _createProjectSelection($AppUI, $project_name) {
        $output .= '<tr><td align="right">' . $AppUI->_('Project Name') . ':</td>';
        $q = new DBQuery();
        $q->addQuery('project_id');
        $q->addTable('projects');
        $q->addWhere("project_name = '{$project_name}'");
        $project_id = $q->loadResult();

        $output .= '<td>';
        $output .= '<input type="text" name="new_project" value="' . $project_name . '" size="36" />';
        if ($project_id) {
            $output .= '<input type="hidden" name="project_id" value="' . $project_id . '" />';
            $output .= $AppUI->_('pexist') ;
        }
        $output .= '</td></tr>';

        return $output;
    }

    protected function _deDynamicLeafNodes($projectId) {
        $q = new DBQuery();
        $q->addUpdate('task_dynamic', 0);
        $q->addWhere("task_project = $projectId");
        $q->addTable('tasks');
        $q->exec();
        
        $q->addQuery('distinct(task_parent)');
        $q->addTable('tasks');
        $q->addWhere("task_project = $projectId");
        $q->addWhere("task_id <> task_parent");
        $taskList = $q->loadHashList();

        foreach($taskList as $id => $nothing){
            $dynamicTasks .= $id.',';
        }
        $dynamicTasks .= '0';
        $q->clear();
        $q->addUpdate('task_dynamic', 1);
        $q->addWhere("task_project = $projectId");
        $q->addWhere("task_id IN ($dynamicTasks)");
        $q->addTable('tasks');
        $q->exec();
    }

    protected function _processContact(CAppUI $AppUI, $username, $company_id) {
        $space = strrpos($username, ' ');
        if ($space === false) {
            $first_name = '';
            $last_name = $username;
        } else {
            $first_name = substr($username, 0, $space);
            $last_name = substr($username, $space + 1);
        }
        $contact = new CContact;
        $contact->contact_first_name = ucwords($first_name);
        $contact->contact_last_name = ucwords($last_name);
        $contact->contact_order_by = $username;
        $contact->contact_company = $company_id;
        $result = $contact->store($AppUI);

        return (is_array($result)) ? $result : $contact->contact_id;
    }

    protected function _processTask(CAppUI $AppUI, $project_id, $task) {
        $myTask = new CTask;
        $myTask->task_name = w2PgetCleanParam($task, 'task_name', null);
        $myTask->task_project = $project_id;
        $myTask->task_description = w2PgetCleanParam($task, 'task_description', '');
        $myTask->task_start_date = $task['task_start_date'];
        $myTask->task_end_date = $task['task_end_date'];
        $myTask->task_duration = $task['task_duration'];
        $myTask->task_milestone = (int) $task['task_milestone'];
        $myTask->task_owner = (int) $task['task_owner'];
        $myTask->task_dynamic = (int) $task['task_dynamic'];
        $myTask->task_priority = (int) $task['task_priority'];
        $myTask->task_percent_complete = $task['task_percent_complete'];
        $myTask->task_duration_type = 1;
        $result = $myTask->store($AppUI);

        return (is_array($result)) ? $result : $myTask->task_id;
    }

    protected function _processProject(CAppUI $AppUI, $company_id, $projectInfo) {

        $projectName = w2PgetParam( $projectInfo, 'new_project', 'New Project' );
        $projectStartDate = w2PgetParam( $projectInfo, 'project_start_date', 'New Project' );
        $projectEndDate = w2PgetParam( $projectInfo, 'project_end_date', 'New Project' );
        $projectOwner = w2PgetParam( $projectInfo, 'project_owner', $AppUI->user_id );
        $projectStatus = w2PgetParam( $projectInfo, 'project_status', 0 );

        $project = new CProject;
        $project->project_name = $projectName;
        $project->project_short_name = substr($projectName, 0, 8);
        $project->project_company = $company_id;
        $project->project_start_date = $projectStartDate;
        $project->project_end_date = $projectEndDate;
        $project->project_owner = $projectOwner;
        $project->project_creator = $AppUI->user_id;
        $project->project_status = $projectStatus;
        $project->project_active = 1;
        $project->project_priority = '0';
        $project->project_type = '0';
        $project->project_color_identifier = 'FFFFFF';
        $project->project_parent = null;
        $project->project_original_parent = null;
        $result = $project->store($AppUI);

        return (is_array($result)) ? $result : $project->project_id;
    }
}