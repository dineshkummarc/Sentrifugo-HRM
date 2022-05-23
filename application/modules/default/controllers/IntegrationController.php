<?php


class Default_IntegrationController extends Zend_Rest_Controller
{
    public function init()
    {
        $filePath = __DIR__ . "/resources-segregation.json";
        $json = file_get_contents($filePath);
        $employees = json_decode($json, false);
        $this->leads = $employees->leads;
        $this->managers = $employees->managers;
        $this->hradmin = $employees->hradmin;

        $this->_helper->viewRenderer->setNoRender(true);
    }

    public function indexAction()
    {
        // TODO: Implement indexAction() method.
    }

    public function getAction()
    {
        // TODO: Implement getAction() method.
    }

    public function postAction()
    {
        // TODO: Implement postAction() method.
    }

    public function putAction()
    {
        // TODO: Implement putAction() method.
    }

    public function deleteAction()
    {
        // TODO: Implement deleteAction() method.
    }

    public function getbirthdaysAction()
    {
        $integrationModel = new Default_Model_Integration();
        $result = $integrationModel->getTodaysBirthdays();
        $this->_helper->json($result);
    }

    public function getworkanniversariesAction()
    {
        $integrationModel = new Default_Model_Integration();
        $result = $integrationModel->getTodaysWorkanniversaries();
        $this->_helper->json($result);
    }

    public function getprojectdetailsAction()
    {
        $integrationModel = new Default_Model_Integration();
        $dbProjects = $integrationModel->getProjectsData($_REQUEST['status'], $_REQUEST['project_type']);

        $projectsData = $this->prepareProjectsData($dbProjects);
        $leadsData = $this->prepareLeadsData($projectsData);
        $companyWideStatsData = $this->getCompanyWideStatsData();

        $output = [];
        $output['projects_data'] = $projectsData;
        $output['leads_data'] = $leadsData;
        $output['company_data'] = $companyWideStatsData;
        return $this->_helper->json($output);
    }

    public function getemployeedetailsAction()
    {
        return $this->_helper->json($this->getCompanyWideStatsData());
    }

    private function prepareProjectsData($dbProjects)
    {
        $resourceTempArr = [];

        $allProjects = [];
        foreach ($dbProjects as $project) {
            $allProjects[$project['project_name']] = $project;
            $allProjects[$project['project_name']]['resources'] = [];
            $allProjects[$project['project_name']]['lead'] = "";
            unset($allProjects[$project['project_name']]['resources_on_project']);

            $resources = array_map('trim', explode(",", $project['resources_on_project']));

            foreach ($resources as $resource) {
                $resourceName = $this->getName($resource, true);
                $managerName = $this->getName($resource, false);

                if ($this->isLead($resourceName)) {
                    if (!$allProjects[$project['project_name']]['lead']) {
                        $allProjects[$project['project_name']]['lead'] = $resourceName;
                    }
                } else if ($this->isManager($resourceName)) {
                    //do nothing
                } else {
                    $allProjects[$project['project_name']]['resources'][$this->getName($resource)] =
                        ['resource' => $this->getName($resource), 'lead' => $this->getName($resource, false)];

                    //code for color coding duplicates
                    if(!$resourceTempArr[$this->getName($resource)]) {
                        $resourceTempArr[$this->getName($resource)] = [];
                        if(!$resourceTempArr[$this->getName($resource)]['color']) {
                            $resourceTempArr[$this->getName($resource)]['color'] = sprintf('#%06X', mt_rand(0, 0xFFFFFF));
                        }
                        if(!$resourceTempArr[$this->getName($resource)]['projects']) {
                            $resourceTempArr[$this->getName($resource)]['projects'] = [];
                        }
                    }

                    $resourceTempArr[$this->getName($resource)]['projects'][$project['project_name']] = $project;
                    //code for color coding duplicates ends here
                }
            }
        }

        //code for color coding duplicates
        foreach ($resourceTempArr as $tempResourceKey => $tempResourceKeyValue) {
            if(count($tempResourceKeyValue['projects']) > 1) {
                foreach ($tempResourceKeyValue['projects'] as $projectKey => $projectKeyValue){
                    $allProjects[$projectKey]['resources'][$tempResourceKey]['color'] = $tempResourceKeyValue['color'];
                }
            }
        }
        //code for color coding duplicates ends here
        //converting associative array of resources to key/value pair
        foreach ($allProjects as $projectKey => $projectKeyVal) {
            $allProjects[$projectKey]['resources'] = array_values($allProjects[$projectKey]['resources']);
        }

        $extensionProject = $allProjects['Company Extension Development'];
        $trainingProject = $allProjects['Magento Training'];

        unset($allProjects['Company Extension Development']);
        unset($allProjects['Magento Training']);

        $allProjects['Company Extension Development'] = $extensionProject;
        $allProjects['Magento Training'] = $trainingProject;

        //converting main associate array to simple array
        return array_values($allProjects);
    }

    private function prepareLeadsData($preparedProjects)
    {
        $leadershipData = [];
        foreach ($this->leads as $lead) {
            $leadershipData[$lead] = [];
            $leadershipData[$lead]['resources_on_trainings'] = 0;
            $leadershipData[$lead]['resources_on_extensions'] = 0;
            $leadershipData[$lead]['resources_on_projects'] = 0;
        }

        foreach ($preparedProjects as $preparedProject) {
            foreach ($preparedProject['resources'] as $projectResource) {
                $projectResourceLead = $projectResource['lead'];

                if (!$leadershipData[$projectResourceLead]['resource_allocation_count'])
                    $leadershipData[$projectResourceLead]['resource_allocation_count'] = [];
                if (!$leadershipData[$projectResourceLead]['resource_allocation_count'][$projectResource['resource']])
                    $leadershipData[$projectResourceLead]['resource_allocation_count'][$projectResource['resource']] = 0;

                $leadershipData[$projectResourceLead]['resource_allocation_count'][$projectResource['resource']]++;

                if ($preparedProject['project_name'] == 'Magento Training') {
                    $leadershipData[$projectResourceLead]['resources_on_trainings']++;
                } elseif ($preparedProject['project_name'] == 'Company Extension Development') {
                    $leadershipData[$projectResourceLead]['resources_on_extensions']++;
                } else {
                    $leadershipData[$projectResourceLead]['resources_on_projects']++;
                }
            }
        }

        foreach ($leadershipData as $leadName => $leadershipDatum){
            $leadershipData[$leadName]['lead_name'] = $leadName;
        }

        return array_values($leadershipData);
    }

    private function getCompanyWideStatsData(){
        $employeeModel = new Default_Model_Employee();
        $employees = $employeeModel->getEmployees("",1,9999,null,null,null,null,false);
        $totalEmployees = count($employees);
        $totalManagers = count($this->managers);
        $totalHrAdmin = count($this->hradmin);
        $totalLeads = count($this->leads);

        $output['employees'] = $employees;
        $output['total_employees'] = $totalEmployees;
        $output['total_managers'] = $totalManagers;
        $output['total_hradmin'] = $totalHrAdmin;
        $output['total_leads'] = $totalLeads;
        $output['total_developers'] = $totalEmployees-$totalManagers-$totalHrAdmin-$totalLeads;

        return $output;
    }

    private function isLead($resourceName)
    {
        return in_array($resourceName, $this->leads);
    }

    private function isManager($resourceName)
    {
        return in_array($resourceName, $this->managers);
    }

    private function getName($name, $ownName = true)
    {
        return $ownName == true ? explode("(", $name)[0] : trim(explode("(", $name)[1], ")");
    }
}