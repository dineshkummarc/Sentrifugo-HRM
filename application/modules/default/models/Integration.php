<?php

class Default_Model_Integration extends Zend_Db_Table_Abstract
{
    public function getTodaysBirthdays()
    {
        $employees = $this->getEmployeeDates();
        $currentYear = (int)date("Y");
        $currentMonth = (int)date("m");
        $currentDay = (int)date("d");

        $result = [];
        foreach ($employees as $employee) {
            try {
                $employeeBirthdayYear = (int)date("Y", strtotime($employee['date_of_birth']));
                $employeeBirthdayMonth = (int)date("m", strtotime($employee['date_of_birth']));
                $employeeBirthdayDay = (int)date("d", strtotime($employee['date_of_birth']));

                if ($currentMonth == $employeeBirthdayMonth && $currentDay == $employeeBirthdayDay) {
                    $result[] = [
                        'name' => $employee['userfullname'],
                        'date_of_birth' => $employee['date_of_birth'],
                        'age' => ($currentYear - $employeeBirthdayYear)
                    ];
                }

            } catch (\Exception $exception) {

            }
        }

        return $result;
    }

    public function getTodaysWorkanniversaries()
    {
        $employees = $this->getEmployeeDates();
        $currentYear = (int)date("Y");
        $currentMonth = (int)date("m");
        $currentDay = (int)date("d");

        $result = [];
        foreach ($employees as $employee) {
            try {
                $employeeJoiningYear = (int)date("Y", strtotime($employee['date_of_joining']));
                $employeeJoiningMonth = (int)date("m", strtotime($employee['date_of_joining']));
                $employeeJoiningDay = (int)date("d", strtotime($employee['date_of_joining']));

                if ($currentMonth == $employeeJoiningMonth && $currentDay == $employeeJoiningDay) {
                    $result[] = [
                        'name' => $employee['userfullname'],
                        'date_of_joining' => $employee['date_of_joining'],
                        'years' => ($currentYear - $employeeJoiningYear)
                    ];
                }

            } catch (\Exception $exception) {

            }
        }

        return $result;
    }

    public function getProjectsData($status = null, $projectType = null)
    {
        $searchData = "";
        if(isset($status)){
            $searchData .= "(";
            foreach (explode(",",$status) as $stat) {
                $searchData .= " p.project_status = '" . $stat . "' OR";
            }
            $searchData = trim($searchData,"OR");
            $searchData .= ")";
        }

        if(isset($projectType)){
            $searchData .= " AND p.project_type = '" . $projectType . "'";
        }


        $tmp = new Timemanagement_Model_Projects();
        $select = $tmp->getProjectsData("ASC", " p.project_name", 1,200, $searchData);
        $response = $tmp->fetchAll($select)->toArray();
        return $response;
    }

    private function getEmployeeDates()
    {
        $query = "select mes.user_id, mu.userfullname, mes.date_of_joining, mes.date_of_leaving, mepd.dob date_of_birth from main_users mu
                inner join main_employees_summary mes on mu.id = mes.user_id
                inner join main_emppersonaldetails mepd on mu.id = mepd.user_id
                where mu.isActive = 1;";

        $db = Zend_Db_Table::getDefaultAdapter();
        $result = $db->query($query);
        $result = $result->fetchAll();

        return $result;
    }
}