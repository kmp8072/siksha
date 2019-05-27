<?php

require('../config.php');
require_once '../commonClass.php';
$comObj = new common();

global $CFG, $DB ,$USER;

$action = $_POST['action'];
switch ($action) {
    case 'GETATTENDANCEDETAIL':
        $userid = $_POST['userid'];
        $attendance = $DB->get_records('nj_guru_attendance_track', array('nj_id' => $userid));

        $table = '<table border="1">';
        $table .= '<thead><tr><th>Module</th><th>Started</th><th>Reached</th><th>Guru Confirmed</th><th>Attendance</th></tr></thead><tbody>';
        foreach ($attendance as $record) {
            if ($record->nj_attendance === NULL) {
                $nj_attendance = 'Not marked'.'<button type="button" class="btn btn-primary markattendance" id='.$record->id.-$userid.' onclick="markattendance(this.id)">Mark Attendance</button>';
            } else if ($record->nj_attendance == 'yes') {
                $nj_attendance = 'Present';
            } else if ($record->nj_attendance == 'no') {
                $nj_attendance = 'Absent'.'<button type="button" class="btn btn-primary markattendance" id='.$record->id.' onclick="markattendance(this.id)">Mark Attendance</button>';
            }
            $course = $DB->get_record('course', array('id' => $record->course_id));
            $table .= '<tr>';
            $table .= '<td>' . $course->fullname . '</td>';
            $table .= '<td>' . date('d-m-Y H:i', $record->nj_checked_time) . '</td>';
            $table .= '<td>' . date('d-m-Y H:i', $record->nj_reached_time) . '</td>';
            $table .= '<td>' . date('d-m-Y H:i', $record->guru_confirm_time) . '</td>';
            $table .= '<td>' . $nj_attendance . '</td>';
            $table .= '</tr>';
        }
        $table .= '</tbody></table>';
        echo $table;
        break;

        case 'MARKATTENDANCE':
             $id = $_POST['id'];

             $current_time=time();

               $mark_attendance_query="UPDATE {nj_guru_attendance_track} SET nj_attendance='yes',updated_time='$current_time',updated_by=$USER->id WHERE id=$id";

             if ($DB->execute($mark_attendance_query)) {

                 echo 1;
             
             }else{

                echo 0;
             }
        
        break;

    default:
        echo 0;
        break;
}
