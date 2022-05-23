<?php


class Timemanagement_TimesheetbyprojectController extends Zend_Controller_Action
{
    /**
     * show hours allocated for a certain month or week range for each project
     */
    public function indexAction()
    {
        $now = new DateTime();

        $selYrMon = $this->_getParam('selYrMon');
        $week = ($this->_getParam('week') != '') ? $this->_getParam('week') : 1;
        $calWeek = $this->_getParam('calWeek');

        $selYrMon = ($selYrMon != '') ? $selYrMon : $now->format('Y-m');
        $yrMon = explode('-', $selYrMon);

        if ($selYrMon == $now->format('Y-m') && $calWeek == '') {
            $calWeek = strftime('%U', strtotime($selYrMon . '-' . $now->format('d')));
            $startCalWeek = strftime('%U', strtotime($selYrMon . '-01'));
            $week = ($calWeek - $startCalWeek) + 1;
        }

        if ($calWeek == '')
            $calWeek = strftime('%U', strtotime($selYrMon . '-01'));

        if ($calWeek >= 1 && $calWeek <= 9)
            $calWeek = '0' . $calWeek;

        $this->view->selYrMon = $selYrMon;
        $this->view->selWeek = $week;

        $empTimesheets_model = new Timemanagement_Model_Emptimesheets();
        $projectWiseWeeklyAggregatedData = $empTimesheets_model->getProjectWiseWeeklyAggregatedData($yrMon[0], $yrMon[1]);
        //asort($projectWiseWeeklyAggregatedData, 0);
        $this->view->projectWiseWeeklyAggregatedData = $projectWiseWeeklyAggregatedData;
    }
}