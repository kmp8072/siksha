<?php

require('../config.php');
global $DB, $CFG;
require_once '../commonClass.php';
$comObj = new common();

require_once 'apiClass.php';
$objAPI = new apiClass();


$action = $_REQUEST['action'];
switch ($action) {
    /*
     * Get the NJ location and marked that he is coming to guru for induction training.
     */
    case 'NJSTARTFORTRAINING':
        if (
                (isset($_REQUEST['nj_id']) && $_REQUEST['nj_id'] != '') &&
                (isset($_REQUEST['date']) && $_REQUEST['date'] != '') &&
                (isset($_REQUEST['latitude']) && $_REQUEST['latitude'] != '') &&
                (isset($_REQUEST['longitude']) && $_REQUEST['longitude'] != '') &&
                (isset($_REQUEST['course_id']) && $_REQUEST['course_id'] != '')
        ) {
            $nj_id = $_REQUEST['nj_id'];
            $date = $_REQUEST['date'];
            $latitude = $_REQUEST['latitude'];
            $longitude = $_REQUEST['longitude'];
            $course_id = $_REQUEST['course_id'];
            echo $objAPI->jsonEncode($objAPI->add_NJ_start_point($nj_id, $latitude, $longitude, $date, $course_id));
        } else {
            echo $objAPI->jsonEncode(array('success' => 'false', 'message' => 'Parameter missing'));
        }

        break;

    /*
     *  check if a user have already check in for training or not
     */
    case 'NJCHECKINDETAIL':
        if (
                (isset($_REQUEST['nj_id']) && $_REQUEST['nj_id'] != '') &&
                (isset($_REQUEST['course_id']) && $_REQUEST['course_id'] != '')
        ) {
            $nj_id = $_REQUEST['nj_id'];
            $course_id = $_REQUEST['course_id'];
            echo $objAPI->jsonEncode($objAPI->get_nj_checkedin_details($nj_id, $course_id));
        } else {
            echo $objAPI->jsonEncode(array('success' => 'false', 'message' => 'Parameter missing'));
        }

        break;

    /*
     * NJ to checked in when he reached in vicinity of his mapped guru for training.
     */
    case 'NJCHECKEDGURUTRAINING':
        if (
                (isset($_REQUEST['nj_id']) && $_REQUEST['nj_id'] != '') &&
                (isset($_REQUEST['date']) && $_REQUEST['date'] != '') &&
                (isset($_REQUEST['latitude']) && $_REQUEST['latitude'] != '') &&
                (isset($_REQUEST['longitude']) && $_REQUEST['longitude'] != '') &&
                (isset($_REQUEST['course_id']) && $_REQUEST['course_id'] != '')
        ) {
            $nj_id = $_REQUEST['nj_id'];
            $date = $_REQUEST['date'];
            $latitude = $_REQUEST['latitude'];
            $longitude = $_REQUEST['longitude'];
            $course_id = $_REQUEST['course_id'];
            echo $objAPI->jsonEncode($objAPI->add_NJ_reached_point($nj_id, $latitude, $longitude, $date, $course_id));
        } else {
            echo $objAPI->jsonEncode(array('success' => 'false', 'message' => 'Parameter missing'));
        }

        break;

    /*
     * NJ to checked in when he reached in vicinity of his mapped guru for training.
     */
    case 'GURUCONFIRMNJATTENDANCE':
        if (
                (isset($_REQUEST['nj_id']) && $_REQUEST['nj_id'] != '') &&
                (isset($_REQUEST['guru_id']) && $_REQUEST['guru_id'] != '') &&
                (isset($_REQUEST['latitude']) && $_REQUEST['latitude'] != '') &&
                (isset($_REQUEST['longitude']) && $_REQUEST['longitude'] != '') &&
                (isset($_REQUEST['course_id']) && $_REQUEST['course_id'] != '') &&
                (isset($_REQUEST['guru_response']) && $_REQUEST['guru_response'] != '') &&
                (isset($_REQUEST['date']) && $_REQUEST['date'] != '')
        ) {
            $nj_id = $_REQUEST['nj_id'];
            $guru_id = $_REQUEST['guru_id'];
            $date = $_REQUEST['date'];
            $latitude = $_REQUEST['latitude'];
            $longitude = $_REQUEST['longitude'];
            $course_id = $_REQUEST['course_id'];
            $guru_response = $_REQUEST['guru_response'];
            echo $objAPI->jsonEncode($objAPI->guru_confirm_attendance($nj_id, $guru_id, $guru_response, $latitude, $longitude, $date, $course_id));
        } else {
            echo $objAPI->jsonEncode(array('success' => 'false', 'message' => 'Parameter missing'));
        }

        break;

    default:
        echo 'Invalid case calling';
        break;
}

