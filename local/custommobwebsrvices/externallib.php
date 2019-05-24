
<?php


// Moodle is free software: you can redistribute it and/or modify


// it under the terms of the GNU General Public License as published by


// the Free Software Foundation, either version 3 of the License, or

// (at your option) any later version.//

// Moodle is distributed in the hope that it will be useful,

// but WITHOUT ANY WARRANTY; without even the implied warranty of

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

// GNU General Public License for more details.//

// You should have received a copy of the GNU General Public License

// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/** * External Web Service Template



 *

 * @package    localwstemplate


 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */



require_once($CFG->libdir . "/externallib.php");

class custommobwebsrvices extends external_api {


    /**


     * Returns description of method parameters

     * @return external_function_parameters

     */

 public static function hello_world_parameters()
     {

        return new external_function_parameters(

                array('userid' => new external_value(PARAM_INT, 'Retrieve information based on userid ', VALUE_DEFAULT, 2))
                );

    }



    /**

     * Returns welcome message

     * @return string welcome message

     */

    public static function hello_world() {

        global $USER;


        $context = get_context_instance(CONTEXT_USER, $USER->id);

        self::validate_context($context);

        if (!has_capability('moodle/user:viewdetails', $context)) {

            throw new moodle_exception('cannotviewprofile');

        }

       global $DB;

      $courses = enrol_get_my_courses();
      $mandatry_course=$DB->get_records_sql("SELECT * FROM mdl_course WHERE mandatory = 1");
       $id_key=array();

    foreach ($mandatry_course as $mandatry_courses) 
  {

        $id_key[]=$mandatry_courses->id;

    }

    foreach ($id_key as $id_keys) 
  {

        unset($courses[$id_keys]);

    }
 return $courses;

    }

      

      public static function hello_world_returns()
     {

       return new external_multiple_structure(

      new external_single_structure(

          array(

                'id'=> new external_value(PARAM_INT,'course  id'),

                'fullname'=> new external_value(PARAM_TEXT,'course fULL Name'),

                 'shortname'=> new external_value(PARAM_TEXT,'short name'),
              )));

      }

   






    public static function view_programs_parameters() {

        return new external_function_parameters(        

                array( 'userid' => new external_value(PARAM_INT, 'USERID'), 
				
				)
        );
    }

    public static function view_programs($userid) {
        

        global $DB;

 
		$result = array();  
		
		$programdetails = $DB->get_records_sql('SELECT * FROM {prog_user_assignment} where userid=?',array($userid));
		
        if(!empty($programdetails)){
			 foreach ($programdetails as $programdetail) {
             $programs = $DB->get_records_sql('SELECT * FROM {prog} where id=?',array($programdetail->programid));  
				foreach($programs as $program){
					$id = $program->id;
                	$pname = $program->fullname;
                	$data = array('Programid'=>$id,'Program Name'=>$pname);
					$result[] = $data;
					}
					
            }
			
		}

       return $result;

    }

    public static function view_programs_returns() {

        return new external_multiple_structure(

            new external_single_structure(

                array(

               
                             'Programid'=> new external_value(PARAM_INT,'status'),     

                            'Program Name'=> new external_value(PARAM_TEXT,'StatusCode'),
                      

                    )
            )
        );
    }

    public static function view_program_courses_parameters() {

        return new external_function_parameters(        

                array( 
                    'programid' => new external_value(PARAM_INT, 'programid'), 
                )
                
        );
    }

    public static function view_program_courses($programid) {
        

        global $DB;
        $newdata = array(); 
        $bb = array();
        $status = array();
        $data = array(); 
        $checkprogram = $DB->get_record_sql("SELECT * FROM {prog} WHERE id = ".$programid." ");
        if(!empty($checkprogram)){  
      
          $program = $DB->get_records_sql("SELECT pcc.courseid,c.fullname FROM {prog_courseset} pc JOIN {prog_courseset_course} pcc ON pc.id = pcc.coursesetid JOIN {course} c ON pcc.courseid = c.id WHERE pc.programid =".$programid."");
         
          $status = array('Status'=> 'Success', 'StatusCode'=>200);

          foreach ($program as $key => $value) {
              
              $data = array('courseid'=>$value->courseid,'Course Name'=> $value->fullname);
              $bb[]=$data;

          }
         
            $status['programdetails'] = $bb;
            $newdata[] = $status;
            return $newdata;
        }else{

          $status = array('Status'=> 'Success', 'StatusCode'=>200);
          $data = array('courseid'=>$value->courseid,'Course Name'=> $value->fullname);
          $bb[] = $data;
          $status['programdetails'] = $bb;
          $newdata[] = $status;

        }
        return $newdata;
      

    }

    public static function view_program_courses_returns() {

        return new external_multiple_structure(

            new external_single_structure(

                array(

                    'Status'=> new external_value(PARAM_TEXT,'status'),     

                    'StatusCode'=> new external_value(PARAM_INT,'StatusCode'),

                    'programdetails' => new external_multiple_structure(
                          new external_single_structure(
                          array(
                             'courseid'=> new external_value(PARAM_INT,'id'), 
                             'Course Name'=> new external_value(PARAM_TEXT,'name'), 
                        )))

                    )
            )
        );
    }

public static function program_leaderboard_parameters() {

        return new external_function_parameters(        

                array( 
                    'programid' => new external_value(PARAM_INT, 'programid'), 
                )
                
        );
    }

    public static function program_leaderboard($programid) {
        

        global $DB,$USER;
        // $programid = $_POST['programid'];
        $newdata = array(); 
        $bb = array();
        $status = array();
        $data = array();     

        $checkprogram = $DB->get_record_sql("SELECT * FROM {prog} as p JOIN {prog_user_assignment} pu ON p.id=pu.programid WHERE p.id = ".$programid." AND pu.userid = ".$USER->id." ");

     
        if(!empty($checkprogram)){

            $query = $DB->get_records_sql("SELECT * FROM {leaderboard} WHERE programid = ? ORDER BY score DESC",array($programid));
            $status = array('Status'=> 'Success', 'StatusCode'=>200);
            foreach ($query as $key => $value) {
                $pieces = explode(" ", $value->fullname);

                $myid = $DB->get_record_sql("SELECT id FROM {user} WHERE firstname = ? AND lastname =?",array($pieces[0],$pieces[1]));
                // print_r($myid);

                $data = array('Id'=>$myid->id,'Name'=>$value->fullname,'Score'=>$value->score);
                $bb[] = $data;
            }
            $status['Program Leaderboard'] = $bb;
            $newdata[] = $status;
            
          
        }else{
          $status = array('Status'=> 'Failed', 'StatusCode'=>401);
             $data = array('Id'=>" ",'Name'=>" ",'Score'=>" ");
                $bb[] = $data;
                $status['Program Leaderboard'] = $bb;
            $newdata[] = $status;

        }

      return $newdata;

    }

    public static function program_leaderboard_returns() {

        return new external_multiple_structure(

            new external_single_structure(

                array(

                    'Status'=> new external_value(PARAM_TEXT,'status'),     

                    'StatusCode'=> new external_value(PARAM_INT,'StatusCode'),

                    'Program Leaderboard' => new external_multiple_structure(
                          new external_single_structure(
                          array(
                             'Id'=>new external_value(PARAM_RAW,'id'),
                             'Name'=> new external_value(PARAM_TEXT,'name'), 
                             'Score'=> new external_value(PARAM_RAW,'score'), 
                        )))

                    )
            )
        );
    }

public static function course_leaderboard_parameters() {

        return new external_function_parameters(        

                array( 
                    'courseid' => new external_value(PARAM_INT, 'courseid'), 
                )
                
        );
    }

    public static function course_leaderboard($courseid) {
        

        global $DB,$USER;
        $newdata = array(); 
        $bb = array();
        $status = array();
        $data = array();     


        $checkcourse = $DB->get_record_sql("SELECT * FROM {course} WHERE id = ".$courseid."");
        if(!empty($checkcourse)){

        $context = context_course::instance($courseid);
		    $sl= is_enrolled($context, $USER->id, '', true);
        if($sl){
		  $details = $DB->get_records_sql("SELECT  gg.userid, gg.finalgrade,u.firstname, u.lastname,u.id FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid JOIN {user} u ON u.id = gg.userid WHERE gi.courseid = ? AND gi.itemtype = ? AND gg.finalgrade <> ? ORDER BY gg.finalgrade DESC LIMIT 10",array($courseid,'course',' '));
              $status = array('Status'=> 'Success', 'StatusCode'=>200);
              foreach ($details as $key => $value) {
                $grades = $value->finalgrade;
                $score = round($grades,2);
                  $data = array('Id'=>$value->id,'Name'=>$value->firstname." ".$value->lastname,'Score'=>$score);
                  $bb[] = $data;
              }
              $status['Course Leaderboard'] = $bb;
              $newdata[] = $status;

        }else{
        	 $status = array('Status'=> 'Failed', 'StatusCode'=>401);
             $data = array('Id'=>"",'Name'=>" ",'Score'=>" ");
             $bb[] = $data;
             $status['Course Leaderboard'] = $bb;
             $newdata[] = $status;
        }    
      }else
      {

        $status = array('Status'=> 'Failed', 'StatusCode'=>401);
             $data = array('Id'=>"",'Name'=>" ",'Score'=>" ");
                $bb[] = $data;
                $status['Course Leaderboard'] = $bb;
               $newdata[] = $status;

      }
         return $newdata;


      

    }

    public static function course_leaderboard_returns() {

        return new external_multiple_structure(

            new external_single_structure(

                array(

                    'Status'=> new external_value(PARAM_TEXT,'status'),     

                    'StatusCode'=> new external_value(PARAM_INT,'StatusCode'),

                    'Course Leaderboard' => new external_multiple_structure(
                          new external_single_structure(
                          array(
                             'Id'=> new external_value(PARAM_RAW,'id'), 
                             'Name'=> new external_value(PARAM_TEXT,'name'), 
                             'Score'=> new external_value(PARAM_RAW,'score'), 
                        )))

                    )
            )
        );
    }

public static function notify_course_enrolment_parameters() {

    return new external_function_parameters(        

      array( 
            'userid' => new external_value(PARAM_INT, 'USERID') 
      ));
  }

  public static function notify_course_enrolment($userid) {
          
    global $DB;
    $result = array();  
    //SELECT * FROM mdl_role_assignments ra JOIN mdl_role r ON ra.roleid = r.id JOIN mdl_context con ON ra.contextid = con.id JOIN mdl_course c ON c.id = con.instanceid AND con.contextlevel = 50 WHERE r.shortname = 'student' AND ra.userid = '.$userid.' AND ra.flag = 0

    $new_enrolment = $DB->get_records_sql('SELECT c.id as courseid,c.fullname as course_fullname 
                                        FROM {role_assignments} ra JOIN {role} r ON ra.roleid = r.id 
                                        JOIN {context} con ON ra.contextid = con.id 
                                        JOIN {course} c ON c.id = con.instanceid AND con.contextlevel = 50 
                                        WHERE r.shortname = "student" AND ra.userid = '.$userid.' AND ra.flag = 0');

    if(!empty($new_enrolment)){
      foreach ($new_enrolment as $key => $value) {
          $id = $value->courseid;
          $coursename = $value->course_fullname;
          $message = "You have been Enrolled in ".$coursename."";
          // $data = array('Course Id'=>$id,'Course Name'=>$coursename);
          $data = array('Message'=>$message,'Course Id'=>$id);
            $result[] = $data;
      }
    }

    return $result;

  }

  public static function notify_course_enrolment_returns() {

    return new external_multiple_structure(

      new external_single_structure(

          array(
                 'Course Id'=> new external_value(PARAM_INT,'id'),     
                
                'Message'=> new external_value(PARAM_TEXT,'msg'),

                )
      )
    );
  }





    public static function notify_status_update_parameters() {

    return new external_function_parameters(        

      array( 
            'status' => new external_value(PARAM_INT, 'Status'),
            'courseid' => new external_value(PARAM_INT, 'id'),
            'userid'=>new external_value(PARAM_INT,'userid'), 

      ));
  }

  public static function notify_status_update($status,$courseid,$userid) {
          
    global $DB;
    $result = array();  
    if($status == 1)
    {
      $contextid = get_context_instance(CONTEXT_COURSE, $courseid);
      
      $update = $DB->execute("UPDATE {role_assignments} SET `flag`= 1 WHERE userid = ".$userid." AND contextid = ".$contextid->id."");
       if($update){
        $result[] = array('Status'=> 'Success', 'StatusCode'=>200);
       }else{
        $result[] = array('Status'=> 'Failed', 'StatusCode'=>401);
         
       }

    }else{
      $result[] = array('Status'=> 'Failed', 'StatusCode'=>401);
        
    }

   
    return $result;

  }

  public static function notify_status_update_returns() {

    return new external_multiple_structure(

      new external_single_structure(

          array(
                 // 'Course Id'=> new external_value(PARAM_INT,'id'),     

                 // 'Course Name'=> new external_value(PARAM_TEXT,'name'),
                
                'Status'=> new external_value(PARAM_RAW,'status'),     

                'StatusCode'=> new external_value(PARAM_RAW,'StatusCode'),

                )
      )
    );
  }
  
  
  public static function user_push_login_parameters() {

        return new external_function_parameters(		

                array(

				)
        );

    }

	public static function user_push_login() {

		global $DB;	
		
		$result = array();
		
		$userdetails = $DB->get_records_sql('SELECT * FROM {user_devices} where appid = ?',array('com.moodle.tvs'));
		
		if(!empty($userdetails)){
			foreach($userdetails as $userdetail){
				
			$data = array('id'=>$userdetail->id,'userid'=> $userdetail->userid,'appid'=> $userdetail->appid,'name'=> $userdetail->name,'model'=> $userdetail->model,'platform'=> $userdetail->platform,'version'=> $userdetail->version,'pushid'=> $userdetail->pushid,'uuid'=> $userdetail->uuid,'timecreated'=> $userdetail->timecreated);
			
              $result[] = $data;
			}
			
			return $result;
		}
		
		
		
	
		}

	  public static function user_push_login_returns() {

        return new external_multiple_structure(

    new external_single_structure(

    array(		

	 'id'    => new external_value(PARAM_INT, 'id'),
     'userid'    => new external_value(PARAM_INT, 'userid'),
	 'appid'    => new external_value(PARAM_RAW, 'appid'),
	 'name'    => new external_value(PARAM_TEXT, 'name'),
	 'model'    => new external_value(PARAM_RAW, 'model'),
	 'platform'    => new external_value(PARAM_RAW, 'platform'),
	 'version'    => new external_value(PARAM_RAW, 'version'),
	 'pushid'    => new external_value(PARAM_RAW, 'pushid'),
	 'uuid'    => new external_value(PARAM_RAW, 'uuid'),
	 'timecreated'    => new external_value(PARAM_TEXT, 'timecreated'),
				
                )
            )
        );
    }



// ADDED BY KRISHNA AND POOJA.

// find  nearest guru
 public static function findguru_parameters() {

        return new external_function_parameters(    

                array(
                 'newjoinerusername' => new external_value(PARAM_RAW, 'newjoinerusername'), 
                 'Latitude'=>new external_value(PARAM_RAW,'Latitude'),
                 'Longitude'=>new external_value(PARAM_RAW,'Longitude')
        )
        );

    }

  public static function findguru($newjoinerusername,$Latitude,$Longitude) {
 
   global $DB; 

   // first check if guru is already assigned

   // $check_guru_assigned="SELECT id FROM {mdl_guru_nj_mapping} WHERE nj_id=";



   // get userid of user.

$user=$DB->get_record_sql("SELECT id FROM {user} WHERE username='$newjoinerusername'");
$region=3;$unit=2;$department=7;$designation=8;
$getregion=findcustomdata($user->id,$region); //get user region
$getunit=findcustomdata($user->id,$unit);// get user unit
$getdept=findcustomdata($user->id,$department);// get user department
$getdesign=findcustomdata($user->id,$designation);// get user designation

if(!$DB->record_exists('guru_nj_mapping',array('nj_id'=>$user->id)) && $getregion!='' && $getunit!='' && $getdept!='' && $getdesign!=''){

   $originslat=$Latitude;
   $originslang=$Longitude;

   $initialradius=initialradius;
   $radiusincrease=radiusincrease;
   $maxradius=maxradius;

   
// print_object($response);
// die();
     $admins= get_admins();
     $adminkeys=array_keys($admins);
     $adminkeys=implode(',', $adminkeys);


     $find_gurus_query=" SELECT u.id,u.address,u.device_token,CONCAT(u.firstname,' ',u.lastname) AS gurufullname,uid.fieldid,uid.data,uidunit.data AS unit,uiddept.data AS depatment,uiddes.data AS designation FROM mdl_user u JOIN mdl_role_assignments ra ON ra.userid=u.id JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='$getregion' JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 AND uidunit.data='$getunit' JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 AND uiddept.data='$getdept' JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 AND uiddes.data='$getdesign' WHERE ra.roleid=4 AND u.id NOT IN(SELECT guru_id FROM `mdl_guru_nj_mapping` WHERE status!=2 GROUP BY guru_id HAVING count(guru_id) <2)";

     $getrecords=$DB->get_records_sql($find_gurus_query);

   
     $distance=array(); 
     $mindis=9999999999;
     $guruid='';  
      
foreach ($getrecords as $getrecord) {
  # code...


   $address=$getrecord->address;
   $cityclean = str_replace (" ", "+", $address);

  $url = "https://maps.google.com/maps/api/geocode/json?address=".$cityclean."&sensor=false&key=AIzaSyDBN77JC4zcb0oZITxpDJrwehDDcoAdEmE";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$response = json_decode($response);

  $Glat = $response->results[0]->geometry->location->lat;
  $Glong = $response->results[0]->geometry->location->lng;  

 
   $d=self::distancebetween($originslat,$originslang,$Glat,$Glong);

   if($d<$mindis && $d<=maxradius*1000){
      
       $mindis=$d;
       $guruid=$getrecord->id;
       $gurufullname=$getrecord->gurufullname;
       $device_token=$getrecord->device_token;

   }
  // $guruarr[$getrecord->id]=array('guruid'=>$getrecord->id,'distance'=>$d);
  //  print_object($guruarr);

 // array_push(array, var)


 // $rad = function(x) {
 //            return x * Math.PI / 180;
 //          };

 //            var R = 6378137; // Earth’s mean radius in meter
 //            var dLat = rad(gurulat - userlat);
 //            var dLong = rad(gurulang - userlang);
 //            var a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
 //              Math.cos(rad(userlat)) * Math.cos(rad(gurulat)) *
 //              Math.sin(dLong / 2) * Math.sin(dLong / 2);
 //            var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
 //            var d = R * c;


}


// insert in nj_guru_mapping table.
  
 $record=new stdclass();
 $record->guru_id=$guruid;
 $record->nj_id=$user->id;
 if($DB->insert_record('guru_nj_mapping',$record)){

  if ($guruid=='') {

    return $message=array('guruid'=>$guruid,'userid'=>$user->id,'message'=>'No guru assigned','assigned'=>0);

  }else{

    return $message=array('guruid'=>$guruid,'userid'=>$user->id,'message'=>'guru assigned successfully','assigned'=>1);

    //so a guru is found to assign to nj 
    // send him a notification

    //find guru's details to create message template


    $message2 = 'Dear '.$gurufullname.', You have been assigned as guru to a user ';
      $time = date('d-m-YTH:i:s');
      $message2 = urlencode($message2);
      $url2 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=$mobile&message=$message2&reqid=1";
      $curl2 = curl_init();
       // OPTIONS:
       curl_setopt($curl2, CURLOPT_URL, $url2);
       curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($curl2, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
       $result = curl_exec($curl2);
       if(!$result){die("Connection Failure");}
       curl_close($curl2);

       // start to send push notification

       // API access key from Google API's Console
define('API_ACCESS_KEY','YOUR-API-ACCESS-KEY-GOES-HERE');
$url = 'https://fcm.googleapis.com/fcm/send';
$registrationIds = array($_GET['id']);
// prepare the message
$message = array( 
  'title'     => 'This is a title.',
  'body'      => 'Here is a message.',
  'vibrate'   => 1,
  'sound'      => 1
);
$fields = array( 
  'registration_ids' => $device_token, 
  'data'             => $message
);
$headers = array( 
  'Authorization: key='.API_ACCESS_KEY, 
  'Content-Type: application/json'
);
$ch = curl_init();
curl_setopt( $ch,CURLOPT_URL,$url);
curl_setopt( $ch,CURLOPT_POST,true);
curl_setopt( $ch,CURLOPT_HTTPHEADER,$headers);
curl_setopt( $ch,CURLOPT_RETURNTRANSFER,true);
curl_setopt( $ch,CURLOPT_SSL_VERIFYPEER,false);
curl_setopt( $ch,CURLOPT_POSTFIELDS,json_encode($fields));
$result = curl_exec($ch);
curl_close($ch);

  }

  
 }
 }// end if

 else{
  // echo "string";
  // die();

    $message=array('guruid'=>(int)'','userid'=>$user->id,'message'=>'guru already assigned','assigned'=>1);
    return $message;

   // print_object($message);
   // die();

 }


 }

    public static function findguru_returns() {

    return new external_single_structure(

            array(    

           'guruid'    => new external_value(PARAM_INT, 'guruid'),
           'userid'    => new external_value(PARAM_INT, 'userid'),
           'message'    => new external_value(PARAM_TEXT, 'message'),
           'assigned'    => new external_value(PARAM_INT, 'assigned')
                   
                )
            
        );
    }

public function distancebetween($userlat,$userlang,$gurulat,$gurulang){

             // $R = 6378137; // Earth’s mean radius in meter
             // $dLat = self::rad($gurulat - $userlat);
             // $dLong = self::rad($gurulang - $userlang);
             // $a = sin($dLat / 2) * sin($dLat / 2) +
             //  cos(self::rad($userlat)) * cos(self::rad($gurulat)) *
             //  sin($dLong / 2) * sin($dLong / 2);
             // $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
             // $d = $R * $c;

  $origins=$userlat.','.$userlang;
  $destinations=$gurulat.','.$gurulang;


 $url="https://maps.googleapis.com/maps/api/distancematrix/json?origins=".$origins."&destinations=".$destinations."&key=AIzaSyDBN77JC4zcb0oZITxpDJrwehDDcoAdEmE";
  

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$output = curl_exec($ch);
curl_close($ch);

$arr = json_decode($output, TRUE);

return $arr['rows'][0]['elements'][0]['distance']['value'];

             
  
}

//get the guru's pending new joiner.
public static function gurupendingnewjoiner_parameters()
     {

        return new external_function_parameters(

                array('guruusername' => new external_value(PARAM_RAW, 'Retrieve information based on guruusername '))
                );

    }



    /**

     * Returns welcome message

     * @return string welcome message

     */

    public static function gurupendingnewjoiner($guruusername) {

        global $DB;
        $record=array();

        $getgurudetails= $DB->get_record_sql("SELECT * FROM {user} WHERE username=$guruusername");

        // get allnewjoiner with pending status.
       $getallnewjoinerquery="SELECT gnm.*,u.username,CONCAT(u.firstname,' ',u.lastname) AS joinerfullname FROM {guru_nj_mapping} gnm INNER JOIN {user} u on u.id=gnm.nj_id WHERE (gnm.status=3 OR gnm.status=0) AND u.deleted <>1 AND u.suspended <>1 AND gnm.guru_id=$getgurudetails->id";
      
             $getallnewjoiner =$DB->get_records_sql($getallnewjoinerquery);
       
               if(!empty($getallnewjoiner)){
               foreach ($getallnewjoiner as $key => $value) {

                  $subrecord=array('newjoinerid'=>$value->nj_id,'newjoinerusername'=>$value->username,'newjoinername'=> $value->joinerfullname,'newjoinerrquestdate'=>date('d-M-Y H:i:s',strtotime($value->createddate)));

                  array_push($record,$subrecord);
               
               }

             return $record;
            // print_object($record);
            // die();
          }

            // print_object($record);
            // die();

       return $record; 

    }

      

      public static function gurupendingnewjoiner_returns()
     {

       return new external_multiple_structure(

      new external_single_structure(

          array(


                'newjoinerid'=> new external_value(PARAM_TEXT,'newjoinerid'),

                'newjoinerusername'=> new external_value(PARAM_TEXT,'newjoinerusername'),

                'newjoinername'=> new external_value(PARAM_TEXT,'newjoinername'),

                'newjoinerrquestdate'=> new external_value(PARAM_TEXT,'newjoinerrquestdate'),
              )));

      }

   
// update the status of the newjoiner after accept.


public static function update_gurunnewjoiner_status_parameters()
     {

        return new external_function_parameters(

        array(

          'guruid' => new external_value(PARAM_INT, 'guruid '),
          'newjoinerid' => new external_value(PARAM_INT, 'newjoinerid'),
          'status' => new external_value(PARAM_INT, 'status'),
          'reason' => new external_value(PARAM_TEXT, 'reason',VALUE_DEFAULT,''),
          'induction_start_date' => new external_value(PARAM_TEXT, 'induction_start_date',VALUE_DEFAULT,'')

              )
                );

    }
      public static function update_gurunnewjoiner_status($guruid,$newjoinerid,$status,$reason,$induction_start_date) {

        global $DB;




        

    



        // first check if creation date and current date difference is not more than 7 days if yes just return

        $current_date_format=date("d-m-Y");

         $created_query="SELECT DATE_FORMAT(createddate,'%d-%m-%Y') AS createddate FROM mdl_guru_nj_mapping WHERE nj_id=$newjoinerid AND guru_id=$guruid";

        $created_query_obj=$DB->get_record_sql($created_query);

        $createddate=$created_query_obj->createddate;

        $earlier = new DateTime($createddate);
        $later = new DateTime($current_date_format);

        $diff = $later->diff($earlier)->format("%a");

        if ($diff>7) {

          $record=array('status'=>"failure",'message'=>'limit of 7 days exceeded');
          return $record;
          
        }


        require_once '../../lib/moodlelib.php';

        $mail=0;


        // case when guru rejects the request

        if ($status==0) {
         
         $sql="UPDATE {guru_nj_mapping} SET status=2, reason='$reason' WHERE nj_id=$newjoinerid AND guru_id=$guruid";
        }

        

        // case when guru accepts the request

        if ($status==1) {

           $sql="UPDATE {guru_nj_mapping} SET status=1,induction_start_date='$induction_start_date' WHERE nj_id=$newjoinerid AND guru_id=$guruid";

          $mail=1;

        }

        if($DB->execute($sql)){

          //find user details to find his success champion

           $base_sql="SELECT u.id,u.email,u.username,u.phone1,CONCAT(u.firstname,' ',u.lastname) AS userfullname,uid.data AS region,uidpl.data AS plarea FROM mdl_user u 
  JOIN mdl_role_assignments ra ON ra.userid=u.id 
  JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 
  JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 
  WHERE ra.roleid=5 AND u.id=$newjoinerid";

  $base_sql_obj=$DB->get_record_sql($base_sql);

  $user_region=$base_sql_obj->region;
  $user_plarea=$base_sql_obj->plarea;
  $userfullname=$base_sql_obj->userfullname;
  $user_phone=$base_sql_obj->phone1;

          // first send a mail to success champion of new joinee

    $find_success_champion_query="SELECT u.* FROM mdl_user u
              JOIN mdl_role_assignments ra ON ra.userid=u.id
              JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='$user_region'
              JOIN mdl_user_info_data uidplarea ON uidplarea.userid=u.id AND uidplarea.fieldid=4 AND uidplarea.data='$user_plarea'
              WHERE ra.roleid=10";
 
    $success_champion_obj=$DB->get_record_sql($find_success_champion_query);

     $admins=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array('2'));
     $admin = new stdClass();
             $admin->id = $admins->id;
             $admin->email     = $admins->email;
             $admin->firstname = $admins->firstname;
             $admin->lastname = $admins->lastname;
             $admin->maildisplay = true;
             $admin->mailformat = 1; 


     $user = new stdClass();
             $user->id = $success_champion_obj->id;
             $user->email     = $success_champion_obj->email;
             $user->firstname = $success_champion_obj->firstname;
             $user->lastname = $success_champion_obj->lastname;
             $user->maildisplay = true;
             $user->mailformat = 1;


             // insert program id in mdl_nj_guru_mapping table if not inserted

        $check_query="SELECT induction_program_id FROM mdl_guru_nj_mapping WHERE nj_id=$newjoinerid AND guru_id=$guruid";

        $check_obj=$DB->get_record_sql($check_query);

        $check=$check_obj->induction_program_id;

        if (is_null($check) || $check=='') {

          // find program details considering first assigned program is induction program   

    $find_programs_query="SELECT MIN(id),programid FROM mdl_prog_user_assignment WHERE userid=$newjoinerid";

    $program_id_obj=$DB->get_record_sql($find_programs_query);

    $programid=$program_id_obj->programid;

    // update in mdl_nj_guru_mapping table

    $update_query="UPDATE mdl_guru_nj_mapping SET induction_program_id=$programid,successchamp_id=$success_champion_obj->id WHERE nj_id=$newjoinerid AND guru_id=$guruid";

    $DB->execute($update_query);
         
        }


             if ($mail==0) {

              $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

    '.$gurufullname.' has rejected the request of induction for user '.$userfullname.'. Please assign a guru manually for this user. Reason given by the guru is as follows: '.$reason;

   // email_to_user($user,$admin,'Induction Rejected for | '.$userfullname ,$body);
               
             }

             if ($mail==1) {

              //send a message to new joinee with guru details and induction start date


              $message1 = 'Dear '.$userfullname.', '.$gurufullname.' has accepted your request for induction. Your induction start date is '.$induction_start_date.' Regards, Team Tvs Siksha.';
     $mobile = $user_phone;
     
     //MESSAGE ONE
      $time = date('d-m-YTH:i:s');
      $message1 = urlencode($message1);
      $url1 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message1&reqid=1";
      $curl = curl_init();
       // OPTIONS:
       curl_setopt($curl, CURLOPT_URL, $url1);
       curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
       curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
       $result = curl_exec($curl1);
      // if(!$result){die("Connection Failure");}
       curl_close($curl);

              //sending message ends

              // send email to success champion


              $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

    '.$gurufullname.' has accepted the request of induction for user '.$userfullname.'. Induction start date is set to'.$induction_start_date.' .';

    //email_to_user($user,$admin,'Induction Acceptance for | '.$userfullname ,$body);

    // now set up the program dates and remove exceptions
     $find_assigned_programs="SELECT min(id) as id, programid, assignmentid FROM mdl_prog_user_assignment WHERE id IN( SELECT MIN(id) FROM mdl_prog_user_assignment GROUP BY programid, assignmentid, userid) AND userid=$newjoinerid AND exceptionstatus=1";

   $assigned_programs=$DB->get_records_sql($find_assigned_programs);

   foreach ($assigned_programs as $assigned_program) {
    $id=$assigned_program->id;
    $programid=$assigned_program->programid;
    $assignmentid=$assigned_program->assignmentid;
    
    $find_completion_time="SELECT completiontime FROM {prog_assignment} WHERE id=$assignmentid AND programid=$programid";

     $completion_time_obj=$DB->get_record_sql($find_completion_time);

     $completion_time=$completion_time_obj->completiontime;

     $current_time=time();

     $due_time=$current_time+$completion_time;

     // now change the assignment time and completion time

     $update_user_assignment="UPDATE {prog_user_assignment} SET timeassigned=$current_time,exceptionstatus=0 WHERE id=$id";

     $DB->execute($update_user_assignment);

     // update user due dates

     $update_user_completiondate="UPDATE {prog_completion} SET timestarted=$current_time,timedue=$due_time WHERE programid=$programid AND userid=$newjoinerid";

     $DB->execute($update_user_completiondate);

     // update induction duration mdl_nj_guru_mapping table

     if ($completion_time>3600) {
       
       $induction_duration=ceil($completion_time/86400);


       $update_induction_duration="UPDATE {guru_nj_mapping} SET induction_duration=$induction_duration WHERE guru_id=$guruid AND nj_id=$newjoinerid";

       $DB->execute($update_induction_duration);

     }

     // find the induction id

     $induction_id_query="SELECT id FROM {guru_nj_mapping} WHERE guru_id=$guruid AND nj_id=$newjoinerid";

     $induction_id_obj=$DB->get_record_sql($induction_id_query);

      $induction_id=$induction_id_obj->id;

     // start setting up induction calender

     $induction_start_date_format=date('d-m-Y', $induction_start_date);

     // calender can be set up only when induction start date is not null

     if (!(is_null($induction_start_date) ||  $induction_start_date=='')) {
       
     // set induction start date as day one without any validation

     $insert_induction_date_query="INSERT INTO mdl_inducation_dates (induction_id,induction_date,induction_day) VALUES($induction_id,'$induction_start_date_format',1) "; 

      $DB->execute($insert_induction_date_query);
     // now for the no of left days check next days . if not weekend and holiday incrase induction_day_count and insert that day

     $count_days=1;

     $next_day=$induction_start_date_format;

     while ($count_days < $induction_duration) {
       
      $next_day=date('d-m-Y', strtotime("+1 day", strtotime($next_day)));

      // check if weekoff

      $bool=self::isWeekend($next_day);

      if ($bool!=1) {

        // if not weekoff check for holiday

        $check_holiday_query="SELECT id FROM mdl_holiday_dates WHERE DATE_FORMAT(STR_TO_DATE(holiday_date, '%Y-%m-%d'), '%d-%m-%Y')=$next_day";
        $check_holiday_obj=$DB->get_record_sql($check_holiday_query);

        if (empty($check_holiday_obj)) {
          $day_no=$count_days+1;

           $insert_date_query="INSERT INTO mdl_inducation_dates (induction_id,induction_date,induction_day) VALUES($induction_id,'$next_day',$day_no)";

          $DB->execute($insert_date_query);

          $count_days++;
        }
        
      }



      } 
      
    
   


     }

    


     //print_object($completion_time);


   }



               
             }


             

             $record=array('status'=>"success",'message'=>'updated successfully');
              return $record;

        }
        // $getgurudetails= $DB->get_record_sql("SELECT *FROM {user} WHERE username=$guruusername");

      else{
           
           $record=array('status'=>"success",'message'=>'something went wrong');


           return $record; 

      }

      

    }

      
 public static function update_gurunnewjoiner_status_returns()
     { 
    return   new external_single_structure(
          array(

                'status'=> new external_value(PARAM_TEXT,'status'),

                'message'=> new external_value(PARAM_TEXT,'message'),

              )

        );

      }

 

function isWeekend($date) {
    $weekDay = date('w', strtotime($date));
    return ($weekDay == 0 || $weekDay == 6);
}



public function  findcustomdata($userid,$fieldid){

global $DB;

$sql="SELECT * FROM {user_info_data} WHERE userid=$userid AND fieldid=$fieldid";

$getrecord=$DB->get_record_sql($sql);

return $getrecord->data;


}




// public function rad($x){

//  return $x * pi() / 180;

// }



// show notification to users  

// show data from custom_notifications table


  public static function custom_notification_parameters()
     {

        return new external_function_parameters(

        array(

          'userid' => new external_value(PARAM_INT, 'userid')

              )
                );

    }
    public static function custom_notification($userid) {

    global $DB;
    $sql="SELECT * FROM {custom_notifications} WHERE sendto=$userid AND isviewed=0";
        
    $datas=$DB->get_records_sql($sql); 

    // print_object($datas);
    // die();

    return $datas;

    }

      
 public static function custom_notification_returns()
     { 
        return  new external_multiple_structure(
                 new external_single_structure(
                  array(
                    'id'=>new external_value(PARAM_INT,'id'),
                    'notification_type'=>new external_value(PARAM_TEXT,'notification_type'),
                    'notification_text'=>new external_value(PARAM_RAW,'notification_text'),
                    'notification_subject'=>new external_value(PARAM_RAW,'notification_subject')
                  )
              )  

            );

      }



      // show notification to users  

// show data from custom_notifications table


  public static function update_induction_status_parameters()
     {

        return new external_function_parameters(

        array(

          'userid' => new external_value(PARAM_INT, 'userid'),
          'guruid' => new external_value(PARAM_INT, 'guruid'),
          'loggedinuserid' => new external_value(PARAM_INT, 'loggedinuserid'),
          'induction_status' => new external_value(PARAM_RAW, 'induction_status')

              )
                );

    }
    public static function update_induction_status($userid,$guruid,$loggedinuserid,$induction_status) {

    global $DB;

    require_once '../../lib/moodlelib.php';
    
     $sql="UPDATE {guru_nj_mapping} SET induction_status='$induction_status',induction_status_updatedby=$loggedinuserid WHERE (nj_id=$userid AND guru_id=$guruid)";

    
        
    if($DB->execute($sql)){

      $record=array('status'=>200,'message'=>'updated successfully');

      // if status is marked as complete send a mail to success champion and supervisor

      if ($induction_status=='complete') {
       
       //find user details to find his success champion

           $base_sql="SELECT u.id,u.email,u.username,CONCAT(u.firstname,' ',u.lastname) AS userfullname,uid.data AS region,uidpl.data AS plarea FROM mdl_user u 
      JOIN mdl_role_assignments ra ON ra.userid=u.id 
      JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 
      JOIN mdl_user_info_data uidpl ON uidpl.userid=u.id AND uidpl.fieldid=4 
      WHERE ra.roleid=5 AND u.id=$userid";

      $base_sql_obj=$DB->get_record_sql($base_sql);

      $user_region=$base_sql_obj->region;
      $user_plarea=$base_sql_obj->plarea;
      $userfullname=$base_sql_obj->userfullname;

      $find_success_champion_query="SELECT u.* FROM mdl_user u
              JOIN mdl_role_assignments ra ON ra.userid=u.id
              JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='$user_region'
              JOIN mdl_user_info_data uidplarea ON uidplarea.userid=u.id AND uidplarea.fieldid=4 AND uidplarea.data='$user_plarea'
              WHERE ra.roleid=10";

     $success_champion_obj=$DB->get_record_sql($find_success_champion_query);

     $admins=$DB->get_record_sql('SELECT * FROM {user} where id = ?',array('2'));
     $admin = new stdClass();
             $admin->id = $admins->id;
             $admin->email     = $admins->email;
             $admin->firstname = $admins->firstname;
             $admin->lastname = $admins->lastname;
             $admin->maildisplay = true;
             $admin->mailformat = 1; 


     $user = new stdClass();
             $user->id = $success_champion_obj->id;
             $user->email     = $success_champion_obj->email;
             $user->firstname = $success_champion_obj->firstname;
             $user->lastname = $success_champion_obj->lastname;
             $user->maildisplay = true;
             $user->mailformat = 1;

             $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

    '.$gurufullname.' has completed the induction for user '.$userfullname.' .';

    email_to_user($user,$admin,'Induction completed for | '.$userfullname ,$body);

      }



    }else{

      $record=array('status'=>200,'message'=>'something went wrong');

    }

    return $record;
    // print_object($datas);
    // die();

    }

      
 public static function update_induction_status_returns()
     { 
    return   new external_single_structure(
          array(

                'status'=> new external_value(PARAM_TEXT,'status'),

                'message'=> new external_value(PARAM_TEXT,'message'),

              )

        );

      }

// code to show calender events for users 
      // currently finding data for only induction dates of new joinees

      public static function show_user_calender_parameters() {
        return new external_function_parameters(
                array( 
                  'userid' => new external_value(PARAM_INT,'USER ID')  
                ));
}
 public static function show_user_calender($userid) {
         global $USER,$DB;
         date_default_timezone_set('Asia/Kolkata');
         // find programs due of the user and send their details

         
    $user_calender_query="SELECT pc.programid,pc.timestarted,pc.timedue,p.fullname FROM mdl_prog_completion pc
      JOIN mdl_prog p ON p.id=pc.programid
      WHERE pc.userid=$userid AND pc.timedue!=0 AND pc.status=0";

      $user_calender_events=$DB->get_records_sql($user_calender_query);

      // foreach ($user_calender_events as $user_calender_event) {

      //   $duedate=$user_calender_event->timedue;

      //   $user_calender_event->timedue=strtotime($duedate);

      // }

      $user_calender=array('user_calender_events'=>$user_calender_events); 

      return $user_calender;

       
}
 public static function show_user_calender_returns() {  
     return new external_single_structure(
         
        array(
              'user_calender_events' => new external_multiple_structure(
                new external_single_structure(
                  array(

                    'programid'=>new external_value(PARAM_RAW,'programid'),
                    'timestarted'=>new external_value(PARAM_RAW,'timestarted'),
                    'timedue'=>new external_value(PARAM_RAW,'timedue'),
                    'fullname'=>new external_value(PARAM_RAW,'fullname')
                    
                  )
                )

              )

            )
      
    );
}


  public static function show_user_programs_parameters() {
        return new external_function_parameters(
                array( 
                  'userid' => new external_value(PARAM_INT,'USER ID')  
                ));
}
 public static function show_user_programs($userid) {
         global $USER,$DB;
         // first of all find all the cohorts in which user exists

         $find_cohorts_query="SELECT DISTINCT(cohortid) FROM {cohort_members} WHERE userid=$userid";

         $cohorts_objs=$DB->get_records_sql($find_cohorts_query);

         //print_object($cohorts_objs);

         // now find which programs are assigned to these cohorts

         $programs_arr=array();

         foreach ($cohorts_objs as $cohorts_obj) {
           
          $find_programs_query="SELECT DISTINCT(programid) FROM {prog_assignment} WHERE assignmenttype=3 AND assignmenttypeid=$cohorts_obj->cohortid";

          $programs_objs=$DB->get_records_sql($find_programs_query);

          foreach ($programs_objs as $programs_obj) {
            
            $programid=$programs_obj->programid;
            array_push($programs_arr, $programid);

          }

         }

          $programsids= implode(",",$programs_arr);

         // now find the details of these programs and return these details

         
          
           $prog_details_query="SELECT id,category,fullname,shortname FROM {prog} WHERE available=1 AND visible=1 AND audiencevisible=2 AND id IN($programsids)";

           $prog_details_objs=$DB->get_records_sql($prog_details_query);

            return $prog_details_objs;

       
}

 public static function show_user_programs_returns() {  
     return new external_multiple_structure(
         
              new external_single_structure(
                
                  array(

                    'id'=>new external_value(PARAM_INT,'id'),
                    'category'=>new external_value(PARAM_INT,'category'),
                    'fullname'=>new external_value(PARAM_TEXT,'fullname'),
                    'shortname'=>new external_value(PARAM_TEXT,'shortname')
                    
                  )
                )
         
              );
}


  public static function courses_of_program_parameters() {
        return new external_function_parameters(
                array( 
                  'programid' => new external_value(PARAM_INT,'PROGRAM ID'),
                  'userid' => new external_value(PARAM_INT,'USER ID')  
                ));
}
 public static function courses_of_program($programid,$userid) {
         global $USER,$DB;

         $courseinfo=array();
         $current_year=date("Y");

         // find the induction start date of this user

  $induction_start_date_query="SELECT induction_start_date FROM {guru_nj_mapping} WHERE nj_id=$userid";

  $induction_start_date_obj=$DB->get_record_sql($induction_start_date_query);

  $induction_start_date=$induction_start_date_obj->induction_start_date;

  $induction_start_date_format=date('d-m-Y', $induction_start_date);

  // if induction not started just return 

  if (is_null($induction_start_date) ||  $induction_start_date=='') {
    
    $courseinfo['courses'][]=array("id"=>0,"category"=>0,"fullname"=>'',"shortname"=>'',"tag"=>0);
     $courseinfo['induction_status']=0;
  
    return $courseinfo;

  }

  // we need to find which course should be shown

  // get the induction day number using the induction start date sat sun and holiday dates

  $current_date=time();
  $current_date_format=date("d-m-Y");
  $$no_of_courses_to_open=0;

  // current time should be greater than induction start date

  if ($current_date>=$induction_start_date) {
    // check no of sat sun and holiday between current date and induction start date

     $startDate = $induction_start_date_format; 
     $endDate = $current_date_format; 
         
        $startDate = new DateTime($startDate);
        $endDate = new DateTime($endDate);

        $weekoffdays = array();


      while ($startDate <= $endDate) {
          if ($startDate->format('w') == 0 || $startDate->format('w') == 6) {
              $weekoffdays[] = $startDate->format('d-m-Y');
          }

          $startDate->modify('+1 day');
      }

      $no_of_weekoff_days=count($weekoffdays);

      $weekoffdays=implode("','",$weekoffdays);

      $weekoffdays="'" .$weekoffdays. "'";

      
    // now find the no of holidays between these two dates

     $no_of_holidays_query="SELECT COUNT(id) AS no_of_holiday FROM {holiday_dates} WHERE year=$current_year AND UNIX_TIMESTAMP(holiday_date)>=$induction_start_date AND UNIX_TIMESTAMP(holiday_date)<=$current_date AND DATE_FORMAT(STR_TO_DATE(holiday_date, '%Y-%m-%d'), '%d-%m-%Y') NOT IN ($weekoffdays)";

    $no_of_holidays_obj=$DB->get_record_sql($no_of_holidays_query);

    $no_of_holiday=$no_of_holidays_obj->no_of_holiday;

    $no_of_days_between_dates=round(($current_date-$induction_start_date)/86400);

     $no_of_courses_to_open=$no_of_days_between_dates+1-$no_of_holiday-$no_of_weekoff_days;
    
  }

         
         // first find the course-sets for this program

    $course_set_query="SELECT id FROM {prog_courseset} WHERE programid=$programid";

    $course_set_objs=$DB->get_records_sql($course_set_query);

    $course_set_arr=(array) $course_set_objs;

    $course_set_arr_keys=array_keys($course_set_arr);

    $course_sets=implode(",",$course_set_arr_keys);

    // now find courseids using these coursesets

     $courseids_query="SELECT id,courseid FROM {prog_courseset_course} WHERE coursesetid IN ($course_sets)";
    $courseids_objs=$DB->get_records_sql($courseids_query);
    
    // now find course details and return these details

    $course_count=1;

    //one use case may be $courseids_objs is empty which is not considered yet

    foreach ($courseids_objs as $courseids_obj) {
     
     $courseid=$courseids_obj->courseid;

     $course_details_query="SELECT id,category,fullname,shortname FROM {course} WHERE id=$courseid AND visible=1";

     $course_details=$DB->get_record_sql($course_details_query);

     if ($course_count<=$no_of_courses_to_open) {

       $course_details->tag=1;

     }else{

       $course_details->tag=0;
     
     }

     //find the induction start date

     $courseinfo['courses'][]=$course_details;

     $course_count++;

    }

    $courseinfo['induction_status']=1;    

    //  print_object($courseinfo);
    // die();

    return $courseinfo;

   
       
}

 public static function courses_of_program_returns() {  

  // return null;

     return new external_single_structure(
         
        array(
              'courses' => new external_multiple_structure(
                new external_single_structure(
                  array(

                    'id'=>new external_value(PARAM_INT,'id'),
                    'category'=>new external_value(PARAM_INT,'category'),
                    'fullname'=>new external_value(PARAM_TEXT,'fullname'),
                    'shortname'=>new external_value(PARAM_TEXT,'shortname'),
                    'tag'=>new external_value(PARAM_INT,'tag')
                  )
                )

              ),  

              'induction_status' => new external_value(PARAM_RAW,'induction_status')  

            )
      
    );

}


public static function show_my_induction_parameters() {
        return new external_function_parameters(
                array( 
                  'userid' => new external_value(PARAM_INT,'USER ID')  
                ));
}
 public static function show_my_induction($userid) {
         global $USER,$DB;
         
         // first of all find induction ids for this user

    $induction_id_query="SELECT id FROM mdl_guru_nj_mapping WHERE nj_id=$userid";

    $induction_id_obj=$DB->get_record_sql($induction_id_query);

    $induction_id=$induction_id_obj->id;  

    // find program details considering first assigned program is induction program   

    $find_programs_query="SELECT MIN(id),programid FROM mdl_prog_user_assignment WHERE userid=$userid";

    $program_id_obj=$DB->get_record_sql($find_programs_query);

    $programid=$program_id_obj->programid;

    // find program details

    $program_details_query="SELECT id,category,fullname,shortname FROM {prog} WHERE available=1 AND visible=1 AND id=$programid";

    $programdetails=$DB->get_record_sql($program_details_query);

    // find induction dates for the induction id

    $induction_dates_query="SELECT * FROM mdl_inducation_dates WHERE induction_id=$induction_id";

    $induction_dates=$DB->get_records_sql($induction_dates_query);

    $induction_calender=array('program_details'=>$programdetails,'induction_dates'=>$induction_dates);

    // print_object($induction_calender);

    // die();

    return $induction_calender;


       
}

 public static function show_my_induction_returns() { 

             return new external_single_structure(
                
                  array(

                    'program_details' => new external_single_structure(
                      
                      array(

                    'id'=>new external_value(PARAM_INT,'id'),
                    'category'=>new external_value(PARAM_INT,'category'),
                    'fullname'=>new external_value(PARAM_TEXT,'fullname'),
                    'shortname'=>new external_value(PARAM_TEXT,'shortname')
                      )   
                     ),

                    'induction_dates' => new external_multiple_structure(
                        new external_single_structure(
                  array(

                    'id'=>new external_value(PARAM_INT,'id'),
                    'induction_id'=>new external_value(PARAM_INT,'induction_id'),
                    'induction_date'=>new external_value(PARAM_RAW,'induction_date'),
                    'induction_day'=>new external_value(PARAM_INT,'induction_day')
                  )
                )

              )
            )
          );
         
             
}



}