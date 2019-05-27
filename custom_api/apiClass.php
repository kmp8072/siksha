<?php

class apiClass extends common {

    public function jsonEncode($array) {
        return json_encode($array);
    }

    protected function get_nj_guru($nj_id) {
        global $DB;
        if ($DB->record_exists('guru_nj_mapping', array('nj_id' => $nj_id, 'status' => 1, 'isactive' => 4))) {
            return $DB->get_record('guru_nj_mapping', array('nj_id' => $nj_id, 'status' => 1, 'isactive' => 4));
        }
        return FALSE;
    }

    /*
     * date format 'YYYY-MM-DD'
     */

    public function add_NJ_start_point($nj_id, $latitude, $longitude, $date, $course_id) {
        global $DB;
        $result = array();
        //check if NJ has already checked in for the day
        $SQL = "SELECT * FROM mdl_nj_guru_attendance_track 
                WHERE nj_id = $nj_id AND nj_checked = 'yes' 
                AND DATE(FROM_UNIXTIME(nj_checked_time)) = '$date' AND course_id = $course_id";
        if (!$DB->record_exists_sql($SQL)) {
            $object = new stdClass();
            $object->course_id = $course_id;
            $object->nj_id = $nj_id;
            $object->nj_checked = 'yes';
            $object->nj_checked_time = time();
            $object->nj_checked_lat = $latitude;
            $object->nj_checked_long = $longitude;
            if ($DB->insert_record('nj_guru_attendance_track', $object)) {
                //send notification to guru
                if ($get_nj_guru = $this->get_nj_guru($nj_id)) {
                    //get guru device tokens
                    $tagmessage = "NJ has arriving for training";
                    $this->send_notification_to_user($get_nj_guru->guru_id, $tagmessage);
                }
                $result = array('success' => 'true', 'message' => 'You have checked in for induction training');
            }
        } else {
            $result = array('success' => 'false', 'message' => 'You have already checked in for induction training');
        }
        return $result;
    }

    public function get_nj_checkedin_details($nj_id, $course_id) {
        global $DB;
        if ($data = $DB->get_record('nj_guru_attendance_track', array('nj_id' => $nj_id, 'course_id' => $course_id))) {

            return array('success' => 'true', 'message' => 'you have already started training', 'data' => $data);
        }
        return array('success' => 'false', 'message' => 'you have not yet started training', 'data' => array());
    }

    /*
     * 
     */

    public function add_NJ_reached_point($nj_id, $latitude, $longitude, $date, $course_id) {
        global $DB;
        $result = array();
        //check if NJ has already checked in for the day
        $SQL = "SELECT * FROM mdl_nj_guru_attendance_track 
                WHERE nj_id = $nj_id AND nj_checked = 'yes' 
                AND DATE(FROM_UNIXTIME(nj_checked_time)) = '$date' AND course_id = $course_id
                AND nj_reached_time IS NULL";


        if ($record = $DB->get_record_sql($SQL)) {

            $object = new stdClass();
            $object->id = $record->id;
            $object->nj_reached_time = time();
            $object->nj_reached_lat = $latitude;
            $object->nj_reached_long = $longitude;
            if ($DB->update_record('nj_guru_attendance_track', $object)) {
                //send notification to guru
                if ($get_nj_guru = $this->get_nj_guru($nj_id)) {
                    //get guru device tokens
                    $tagmessage = "NJ has reached";
                    $this->send_notification_to_user($get_nj_guru->guru_id, $tagmessage);
                }
                $result = array('success' => 'true', 'message' => 'You have checked in for induction training');
            }
        } else {
            $result = array('success' => 'false', 'message' => 'You have already checked in for induction training');
        }
        return $result;
    }

    /*
     * 
     */

    public function guru_confirm_attendance($nj_id, $guru_id, $guru_response, $latitude, $longitude, $date, $course_id) {
        global $DB;
        $result = array();
        //check if NJ has already checked in for the day
        $SQL = "SELECT * FROM mdl_nj_guru_attendance_track 
                WHERE nj_id = $nj_id AND nj_checked = 'yes' 
                AND DATE(FROM_UNIXTIME(nj_checked_time)) = '$date' AND course_id = $course_id
                AND DATE(FROM_UNIXTIME(nj_reached_time)) = '$date' AND guru_confirm IS NULL";


        if ($record = $DB->get_record_sql($SQL)) {

            $object = new stdClass();
            $object->id = $record->id;
            $object->guru_id = $guru_id;
            $object->guru_confirm = $guru_response;
            $object->guru_confirm_time = time();
            $object->guru_lat = $latitude;
            $object->guru_long = $longitude;
            if ($DB->update_record('nj_guru_attendance_track', $object)) {
                //send notification to guru
                $tagmessage1 = "NJ has reached";
                $this->send_notification_to_user($guru_id, $tagmessage1);

                //send notification to NJ
                $tagmessage2 = "NJ has reached";
                $this->send_notification_to_user($nj_id, $tagmessage2);

                //mark attendance
                $data = $DB->get_record('nj_guru_attendance_track', array('id' => $record->id));

                $distance = $this->distancebetween($data->nj_reached_lat, $data->nj_reached_long, $data->guru_lat, $data->guru_long);
                if ($distance <= 20) { // distance in meter
                    $object = new stdClass();
                    $object->id = $record->id;
                    $object->nj_attendance = $guru_response;
                    if ($DB->update_record('nj_guru_attendance_track', $object)) {
                        //send notification to NJ
                        $tagmessage3 = "Your attendance have been marked";
                        $this->send_notification_to_user($nj_id, $tagmessage3);
                    }
                }
                $result = array('success' => 'true', 'message' => 'You have checked in for induction training');
            }
        } else {
            $result = array('success' => 'false', 'message' => 'You have already checked in for induction training');
        }
        return $result;
    }

    //send notification to user
    public function send_notification_to_user($userid, $message) {
        global $DB;
        $guru_tokens = $DB->get_records('user_devices', array('userid' => $userid));
        foreach ($guru_tokens as $token) {
            $this->sendPushNotification($token->pushid, $message);
        }
    }

}
