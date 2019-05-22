<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Self enrol plugin external functions
 *
 * @package    enrol_self
 * @copyright  2013 Rajesh Taneja <rajesh@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
/**
 * Self enrolment external functions.
 *
 * @package   enrol_self
 * @copyright 2012 Rajesh Taneja <rajesh@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.6
 */
class enrol_nomination_external extends external_api {

    public static function enrol_user_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Id of the user'),
                'comment'=> new external_value(PARAM_RAW,'comments',VALUE_OPTIONAL),                
            )
        );
    }

    /**
     * Self enrol the current user in the given course.
     *
     * @param int $courseid id of course
     * @param string $password enrolment key
     * @param int $instanceid instance id of self enrolment plugin
     * @return array of warnings and status result
     * @since Moodle 3.0
     * @throws moodle_exception
     */
    public static function enrol_user() {
        global $CFG,$DB,$USER;
        $courseid=$_POST['courseid'];
        $comment= $_POST['comment'];
       // echo $courseid;
         $result = array();
      
        require_once($CFG->libdir . '/enrollib.php');

          $checkcourse=$DB->get_record_sql("SELECT * from {enrol} where courseid=? AND enrol=?",array($courseid,'nomination'));
        if(!empty($checkcourse)){
        	$record = new stdClass();
        	
        	$record->status = 1;
        	$record->enrolid=$checkcourse->id;
        	$record->userid=$USER->id;
        	$record->timeend=0;
        	$insertrecord=$DB->insert_record('user_enrolments',$record);

        	$sql=$DB->get_record_sql("SELECT * from {user_enrolments} where enrolid=? AND userid=?",array($checkcourse->id,$USER->id));
        	//print_r($sql);

        	$record1 = new stdClass();
        	$record1->userenrolmentid=$sql->id;
        	$record1->comment=$comment;
        	$insertrecord1=$DB->insert_record('enrol_nomination_applicationinfo',$record1);
        	$result['status'] = "Success";
	        $result['statuscode'] = 200;
	         return $result;
        	
        }else{

	         $result['status'] = "Failed";
	        $result['statuscode'] = 401;
	        
	        return $result;
        }


    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.0
     */
    public static function enrol_user_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'status'),
                'statuscode' => new external_value(PARAM_INT,'status code')
    
            )
        );
    }


}
