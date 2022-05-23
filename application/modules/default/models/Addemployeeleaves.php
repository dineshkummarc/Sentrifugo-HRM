<?php
/*********************************************************************************
 *  This file is part of Sentrifugo.
 *  Copyright (C) 2015 Sapplica
 *
 *  Sentrifugo is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Sentrifugo is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Sentrifugo.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  Sentrifugo Support <support@sentrifugo.com>
 ********************************************************************************/

class Default_Model_Addemployeeleaves extends Zend_Db_Table_Abstract
{
    protected $_name = 'main_employeeleaves';
    protected $_primary = 'id';

    /*
       I. This query fetches employees data based on roles.
    */
    public function getEmployeesData($sort, $by, $pageNo, $perPage, $searchQuery, $managerid = '', $loginUserId)
    {
        //the below code is used to get data of employees from summary table.
        $employeesData = "";
        $where = "  e.isactive = 1 AND e.user_id != " . $loginUserId . " 
        			and r.group_id NOT IN (" . MANAGEMENT_GROUP . "," . USERS_GROUP . ")
        			AND el.alloted_year = year(now()) ";

        if ($searchQuery != '')
            $where .= " AND " . $searchQuery;

        $employeesData = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'main_employees_summary'), array('id' => 'e.user_id', 'e.userfullname', 'e.employeeId', 'DATE_FORMAT(e.date_of_joining, \'%D %M, %Y\') as date_of_joining'))
            ->joinLeft(array('r' => 'main_roles'), 'e.emprole=r.id', array())
            ->joinLeft(array('el' => 'main_employeeleaves'), 'el.user_id=e.user_id', array(
                    'el.emp_leave_limit',
                    'earned_leaves' => new Zend_Db_Expr('(GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2))'),
                    'earned_casual_leaves' => new Zend_Db_Expr('GetEmployeeEarnedLeaves(e.user_id,1)'),
                    'earned_annual_leaves' => new Zend_Db_Expr('GetEmployeeEarnedLeaves(e.user_id,2)'),
                    'leaves_over_consumption' => new Zend_Db_Expr('if(el.used_leaves - (GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)) > 0, el.used_leaves - (GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)),\'\')'),
                    'el.used_leaves',
                    'used_casual_leaves' => new Zend_Db_Expr('GetEmployeeUsedLeave(e.user_id,1)'),
                    'used_annual_leaves' => new Zend_Db_Expr('GetEmployeeUsedLeave(e.user_id,2)'),
                    'casual_leaves_left' => new Zend_Db_Expr('IFNULL(GetEmployeeEarnedLeaves(e.user_id,1),0) - IFNULL(GetEmployeeUsedLeave(e.user_id, 1),0)'),
                    'annual_leaves_left' => new Zend_Db_Expr('IFNULL(GetEmployeeEarnedLeaves(e.user_id,2),0) - IFNULL(GetEmployeeUsedLeave(e.user_id, 2),0)'),
                    //'casual_leaves_left' => new Zend_Db_Expr('CEILING(((el.emp_leave_limit * 1/3) - GetEmployeeUsedLeave(e.user_id, 1)) / 0.5) * 0.5'),
                    //'annual_leaves_left' => new Zend_Db_Expr('CEILING(((el.emp_leave_limit * 2/3) - GetEmployeeUsedLeave(e.user_id, 2)) / 0.5) * 0.5'),
                    'el.alloted_year',
                    'el.createddate',
                    'el.isleavetrasnferset',
                    'remainingleaves' => new Zend_Db_Expr('(GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)) - el.used_leaves')
                )
            )->where($where)
            ->order("$by $sort")
            ->limitPage($pageNo, $perPage);
        return $employeesData;
    }

    public function getEmployeeData($loginUserId)
    {
        //the below code is used to get data of employee from summary table.
        $employeesData = "";
        $where = "  e.isactive = 1 AND e.user_id = " . $loginUserId . " 
        			and r.group_id NOT IN (" . MANAGEMENT_GROUP . "," . USERS_GROUP . ")
        			AND el.alloted_year = year(now()) ";

        $select = $this->select()
            ->setIntegrityCheck(false)
            ->from(array('e' => 'main_employees_summary'), array('id' => 'e.user_id', 'e.userfullname', 'e.employeeId', 'DATE_FORMAT(e.date_of_joining, \'%D %M, %Y\') as date_of_joining'))
            ->joinLeft(array('r' => 'main_roles'), 'e.emprole=r.id', array())
            ->joinLeft(array('el' => 'main_employeeleaves'), 'el.user_id=e.user_id', array(
                    'el.emp_leave_limit',
                    'earned_leaves' => new Zend_Db_Expr('(GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2))'),
                    'earned_casual_leaves' => new Zend_Db_Expr('GetEmployeeEarnedLeaves(e.user_id,1)'),
                    'earned_annual_leaves' => new Zend_Db_Expr('GetEmployeeEarnedLeaves(e.user_id,2)'),
                    'leaves_over_consumption' => new Zend_Db_Expr('if(el.used_leaves - (GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)) > 0, el.used_leaves - (GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)),\'\')'),
                    'el.used_leaves',
                    'used_casual_leaves' => new Zend_Db_Expr('GetEmployeeUsedLeave(e.user_id,1)'),
                    'used_annual_leaves' => new Zend_Db_Expr('GetEmployeeUsedLeave(e.user_id,2)'),
                    'casual_leaves_left' => new Zend_Db_Expr('IFNULL(GetEmployeeEarnedLeaves(e.user_id,1),0) - IFNULL(GetEmployeeUsedLeave(e.user_id, 1),0)'),
                    'annual_leaves_left' => new Zend_Db_Expr('IFNULL(GetEmployeeEarnedLeaves(e.user_id,2),0) - IFNULL(GetEmployeeUsedLeave(e.user_id, 2),0)'),
                    //'casual_leaves_left' => new Zend_Db_Expr('CEILING(((el.emp_leave_limit * 1/3) - GetEmployeeUsedLeave(e.user_id, 1)) / 0.5) * 0.5'),
                    //'annual_leaves_left' => new Zend_Db_Expr('CEILING(((el.emp_leave_limit * 2/3) - GetEmployeeUsedLeave(e.user_id, 2)) / 0.5) * 0.5'),
                    'el.alloted_year',
                    'el.createddate',
                    'el.isleavetrasnferset',
                    'remainingleaves' => new Zend_Db_Expr('(GetEmployeeEarnedLeaves(e.user_id,1) + GetEmployeeEarnedLeaves(e.user_id,2)) - el.used_leaves')
                )
            )->where($where);

        $employeesData = $this->fetchAll($select)->toArray();
        return current($employeesData);
    }

    public function getGrid($sort, $by, $perPage, $pageNo, $searchData, $call, $dashboardcall, $exParam1 = '', $exParam2 = '', $exParam3 = '', $exParam4 = '')
    {
        $searchQuery = '';
        $tablecontent = '';
        $emptyroles = 0;
        $empstatus_opt = array();
        $searchArray = array();
        $data = array();
        $id = '';
        $dataTmp = array();

        if ($searchData != '' && $searchData != 'undefined') {
            $searchValues = json_decode($searchData);

            foreach ($searchValues as $key => $val) {
                $searchQuery .= $key . " like '%" . $val . "%' AND ";
                $searchArray[$key] = $val;
            }
            $searchQuery = rtrim($searchQuery, " AND");
        }
        $objName = 'addemployeeleaves';


        $tablecontent = $this->getEmployeesData($sort, $by, $pageNo, $perPage, $searchQuery, '', $exParam1);

        if ($tablecontent == "emptyroles") {
            $emptyroles = 1;
        }

        $tableFields = array(
            'action' => 'Action',
            'id' => 'User ID',
            'userfullname' => 'Name',
            'date_of_joining' => 'Joining Date',
            'emp_leave_limit' => 'Yearly Leaves Limit',
            'earned_casual_leaves' => 'Casual Leaves Earned Till now',
            'earned_annual_leaves' => 'Annual Leaves Earned Till now',
            'earned_leaves' => 'Total Leaves Earned Till now',
            'used_casual_leaves' => 'Casual Leaves Used',
            'used_annual_leaves' => 'Annual Leaves Used',
            'used_leaves' => 'Total Leaves Used',
            'leaves_over_consumption' => 'Total Leaves Over Consumed',
            'casual_leaves_left' => 'Casual Leaves Left',
            'annual_leaves_left' => 'Annual Leaves Left',
            'remainingleaves' => 'Total Leaves Left'
        );


        $dataTmp = array(
            'userid' => $id,
            'sort' => $sort,
            'by' => $by,
            'pageNo' => $pageNo,
            'perPage' => $perPage,
            'tablecontent' => $tablecontent,
            'objectname' => $objName,
            'extra' => array(),
            'tableheader' => $tableFields,
            'jsGridFnName' => 'getAjaxgridData',
            'jsFillFnName' => '',
            'searchArray' => $searchArray,
            'menuName' => 'Employees',
            'dashboardcall' => $dashboardcall,
            'add' => 'add',
            'call' => $call,
            'emptyroles' => $emptyroles
        );

        return $dataTmp;
    }

    public function getMultipleEmployees($dept_id)
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $loginUserId = $auth->getStorage()->read()->id;
        }
        if ($dept_id != '' && $loginUserId != '') {
            $select = $this->select()
                ->setIntegrityCheck(false)
                ->from(array('e' => 'main_employees_summary'), array('e.id', 'e.user_id', 'e.userfullname', 'e.firstname', 'e.lastname', 'e.employeeId', 'e.department_id'))
                ->joinLeft(array('r' => 'main_roles'), 'e.emprole=r.id')
                ->joinLeft(array('el' => 'main_employeeleaves'), 'el.user_id=e.user_id', array('el.emp_leave_limit', 'el.used_leaves', 'el.alloted_year', 'el.createddate', 'el.isleavetrasnferset'))
                ->where('e.isactive = 1 and e.department_id in (' . $dept_id . ') and e.user_id!=' . $loginUserId . ' and r.group_id NOT IN (' . MANAGEMENT_GROUP . ',' . USERS_GROUP . ')')
                ->group('e.user_id')
                ->order('e.userfullname');

            return $this->fetchAll($select)->toArray();
        } else
            return array();
    }

}

?>