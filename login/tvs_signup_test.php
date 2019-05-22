<?php 

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
//define('CLI_SCRIPT', true);
require('../config.php');
global $CFG,$DB;
require_once($CFG->dirroot .'/lib/moodlelib.php');

// fetch the user data from database 

$user_data_query="SELECT * FROM {employee_data} WHERE EMP_NO='szr_287'";

$responses=$DB->get_records_sql($user_data_query);

	foreach($responses as $userdetails){
		print_object($userdetails);
		echo 'Username '.$userdetails->emp_no;
		echo '<br>';
		
		
$check_user = $DB->get_records_sql('SELECT * FROM {user} where username = ?',array($userdetails->emp_no));

if(empty($check_user)){

	// find user lat lang

	$useraddress=$userdetails->emp_address;
    $usercityclean = str_replace (" ", "+", $useraddress);

    $url = "https://maps.google.com/maps/api/geocode/json?address=".$usercityclean."&sensor=false&key=AIzaSyDBN77JC4zcb0oZITxpDJrwehDDcoAdEmE";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$response = curl_exec($ch);
		curl_close($ch);

		$response = json_decode($response);

		// print_object($response);
		// die();

	  $originslat = $response->results[0]->geometry->location->lat;
	  $originslang = $response->results[0]->geometry->location->lng;

	
	$username = trim($userdetails->emp_name);
	
	//$names = preg_split(" / (.|' ') / ", $username);
	 $names = explode(" ", $username);
	 $wcount = count($names);
	
	if($wcount == 1){
		$firstname = $names[0];
		$lastname = ' ';
		}elseif($wcount == 2){
		$firstname = $names[0];
		$lastname = $names[1];
		}elseif($wcount == 3){
		$firstname = $names[0];
		$lastname =$names[1].' '.$names[2];
		}elseif($wcount == 4){
		$firstname = $names[0];
		$lastname = $names[1].' '.$names[2].' '.$names[3];
		}elseif($wcount == 5){
		$firstname = $names[0];
		$lastname = $names[1].' '.$names[2].' '.$names[3].' '.$names[4];
		}elseif($wcount == 6){
		$firstname = $names[0];
		$lastname = $names[1].' '.$names[2].' '.$names[3].' '.$names[4].' '.$names[5];
		}elseif($wcount == 7){
		$firstname = $names[0];
		$lastname = $names[1].' '.$names[2].' '.$names[3].' '.$names[4].' '.$names[5].' '.$names[6];
		}elseif($wcount == 8){
		$firstname = $names[0];
		$lastname = $names[1].' '.$names[2].' '.$names[3].' '.$names[4].' '.$names[5].' '.$names[6].' '.$names[7];
		}

	
	
$records = new stdClass();


		$records->username     		=  $userdetails->emp_no;

		$records->firstname    		=  $firstname;

		$records->lastname     		=  $lastname;

		$records->address     		=  $userdetails->emp_address;

		$records->password     		=  md5('Admin@123');	
		
		$records->email     		=  $userdetails->emp_emailid;
		
		$records->phone1     		=  $userdetails->emp_mobileno;			


		$records->confirmed     	=  '1';


		$records->mnethostid     	=  '1';


		$insertitems = $DB->insert_record('user', $records);


		$message1 = 'Dear '.$firstname.', Welcome to the TVS Siksha LMS App. Download link for Android Phone is https://tinyurl.com and iPhone is https://tinyurl.com. Your username is '.$userdetails->emp_no.'. You will receive your password in a separate SMS. Regards, Team Tvs Siksha.';
		 $mobile = $userdetails->emp_mobileno;
		 
		 //MESSAGE ONE
			$time = date('d-m-YTH:i:s');
			$message1 = urlencode($message1);
			$url1 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message1&reqid=1";
			$curl = curl_init();
		   // OPTIONS:
		   curl_setopt($curl, CURLOPT_URL, $url1);
		   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		   $result = curl_exec($curl);
		  // if(!$result){die("Connection Failure");}
		   curl_close($curl);
		   
		   sleep(1);
		   //MESSAGE TWO
		   $message2 = 'Dear '.$firstname.', Your password to access TVS Siksha LMS app is Admin@123. Regards, Tvs Siksha.';
			$time = date('d-m-YTH:i:s');
			$message2 = urlencode($message2);
			$url2 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message2&reqid=1";
			$curl2 = curl_init();
		   // OPTIONS:
		   curl_setopt($curl2, CURLOPT_URL, $url2);
		   curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($curl2, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		   $result = curl_exec($curl2);
		   //if(!$result){die("Connection Failure");}
		   curl_close($curl2);



		echo 'User Insert';
		print_r($insertitems);
		echo '<br>';
		
		 // die();
		
		$records2 = new stdClass();


		$records2->userid     					=  $insertitems;
		
		$records2->idnumber						=  '1';

		$records2->timecreated    				=  time();

		$records2->timemodified     			=  time();

		$records2->usermodified     			=  time();	
		
		$records2->positionassignmentdate     	=  time();
		
		$records2->sortorder     				=  '1';			


		$insertitems2 = $DB->insert_record('job_assignment', $records2);
		echo 'User jOB Assignment Insert';
		print_r($insertitems2);
		echo '<br>';
		
		
		
	
		for($i=2;$i<=12;$i++){
		$records1 = new stdClass();
		$userid = $insertitems;
		if($i == 2){
			$datas = $userdetails->emp_unit;
			}
			if($i == 3){
			$datas = $userdetails->emp_region;
			}
			if($i == 4){
			$datas = $userdetails->emp_p_l_area;
			}
			if($i == 5){
			$datas = $userdetails->emp_location;
			}
			if($i == 6){
			$datas = $userdetails->emp_state;
			}
			if($i == 7){
			$datas = $userdetails->emp_department;
			}
			if($i == 8){
			$datas = $userdetails->emp_designation;
			}
			if($i == 9){
			$datas = $userdetails->emp_appraiser_id;
			}
			if($i == 10){
			$datas = $userdetails->emp_appraiser_name;
			}
			if($i == 11){
			$datas = $userdetails->emp_product;
			}
			if($i == 12){
			$datas = $userdetails->emp_date_of_joining;
			}

		$records1->userid     		=  $userid;

		$records1->fieldid    		=  $i;

		$records1->data     			=  $datas;

		$insertitems1 = $DB->insert_record('user_info_data', $records1);
		
		echo 'User infodata Insert';
		print_r($insertitems1);
		echo '<br>';
		
		}


		$region=3;$unit=2;$department=7;$designation=8;

		$user_region=$userdetails->emp_region; 
		$user_unit=$userdetails->emp_unit;
		$user_dept=$userdetails->emp_department;
		$user_designation=$userdetails->emp_designation;
		$user_plarea=$userdetails->emp_p_l_area;

	// assign the success champion to this user based on region and P&L Area

  $find_success_champion_query="SELECT u.* FROM mdl_user u
	                          JOIN mdl_role_assignments ra ON ra.userid=u.id
	                          JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='$user_region'
	                          JOIN mdl_user_info_data uidplarea ON uidplarea.userid=u.id AND uidplarea.fieldid=4 AND uidplarea.data='$user_plarea'
	                          WHERE ra.roleid=10";

 // die();

 $success_champion_obj=$DB->get_record_sql($find_success_champion_query);

 $success_champion_id=$success_champion_obj->id;

 $success_champion_email=$success_champion_obj->email;


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


//assign success now in pending for now

// $assign_success_champion_query="INSERT INTO {nj_success_champion_mapping} 
// (nj_id, success_champ_id) VALUES ($insertitems, $success_champion_id)";                         
// $DB->execute($assign_success_champion_query);

		
		//now process for gurumapping

		//find only those gurus which belongs to this region,unit,department and designation only

	 $find_gurus_query="SELECT u.id,u.address,CONCAT(u.firstname,' ',u.lastname) AS gurufullname,uid.fieldid,uid.data,uidunit.data AS unit,uiddept.data AS depatment,uiddes.data AS designation FROM mdl_user u 
	JOIN mdl_role_assignments ra ON ra.userid=u.id 
	JOIN mdl_user_info_data uid ON uid.userid=u.id AND uid.fieldid=3 AND uid.data='$user_region' 
	JOIN mdl_user_info_data uidunit ON uidunit.userid=u.id AND uidunit.fieldid=2 AND uidunit.data='$user_unit' 
	JOIN mdl_user_info_data uiddept ON uiddept.userid=u.id AND uiddept.fieldid=7 AND uiddept.data='$user_dept' 
	JOIN mdl_user_info_data uiddes ON uiddes.userid=u.id AND uiddes.fieldid=8 AND uiddes.data='$user_designation' WHERE ra.roleid=4";

	$gurus_datas=$DB->get_records_sql($find_gurus_query);

	// now find if they have taken two induction in this month or not or if there is any current induction going on this time

	$first_minute = mktime(0, 0, 0, date("n"), 1);
    $last_minute = mktime(23, 59, 59, date("n"), date("t"));

     $distance=array(); 
     $mindis=9999999999;
     $guruid='';  

     if (!empty($gurus_datas)) {
     	

    foreach ($gurus_datas as $gurus_data) {
    	//check no of induction in this month

    	$no_of_induction_taken_query="SELECT COUNT(id) AS no_of_induction FROM mdl_inducation_dates WHERE guru_id=$gurus_data->id AND induction_start>='$first_minute' AND induction_end<='$last_minute'";

    	$no_of_induction_obj=$DB->get_record_sql($no_of_induction_taken_query);

    	$no_of_induction=$no_of_induction_obj->no_of_induction;

    	if ($no_of_induction>1) {
    		
    		continue;
    	}

    	// check if any existing induction is going on or not for this guru

    	$current_time=time();

    	$check_current_induction_query="SELECT id FROM mdl_inducation_dates WHERE guru_id=$gurus_data->id AND induction_start<=$current_time AND induction_end>=$current_time";

    	$check_current_induction=$DB->get_record_sql($check_current_induction_query);

    	if (!empty($check_current_induction)) {
    		
    		continue;
    	}

    	// find distance if guru is free of above two condition

    	$address=$gurus_data->address;
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

	  $d=distancebetween($originslat,$originslang,$Glat,$Glong);

	  if($d<$mindis && $d<=maxradius*1000){
      
       $mindis=$d;
       $guruid=$getrecord->id;
       $gurufullname=$getrecord->gurufullname;

   }

   if ($guruid) {

   	 $record=new stdclass();
	 $record->guru_id=$guruid;
	 $record->nj_id=$insertitems;
	 $current_time=time();
	 $current_time=date('Y-m-d H:i:s', $current_time);
	 $record->createddate=$current_time;

	 if($DB->insert_record('guru_nj_mapping',$record)){

	 	$message2 = 'Dear '.$gurufullname.', You have been assigned as guru to a user '.$firstname.' '.$lastname;
      $time = date('d-m-YTH:i:s');
      $message2 = urlencode($message2);
      $url2 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message2&reqid=1";
      $curl2 = curl_init();
       // OPTIONS:
      curl_setopt($curl2, CURLOPT_URL, $url2);
      curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($curl2, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
      $result = curl_exec($curl2);
      //if(!$result){die("Connection Failure");}
      curl_close($curl2);

      // now send mail to success champion

      $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

		'.$gurufullname.' is assigned to '.$user_details->firstname.' '.$user_details->lastname.' as a guru during registration. You will be notified when guru accepts or rejects this assignment.

		 ';

             email_to_user($user,$admin,'Automatic Guru Assignment to| '.$user_details->firstname.' '.$user_details->lastname ,$body);

             // insert data into custom_notification table

	 }
   	
   }else{

   	 $record=new stdclass();
	 $record->guru_id=0;
	 $current_time=time();
	 $current_time=date('Y-m-d H:i:s', $current_time);
	 $record->createddate=$current_time;
	 $record->nj_id=$insertitems;
	 

	 $user_details_query="SELECT * FROM {user} WHERE id=$insertitems";

	 $user_details=$DB->get_record_sql($user_details_query);

	 $DB->insert_record('guru_nj_mapping',$record);

	 // send a mail to the success champion of the user


	 $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

		No guru could be assigned to '.$user_details->firstname.' '.$user_details->lastname.' automatically. Please assign a guru manually to this user.

		 ';

 email_to_user($user,$admin,'Manual Guru Assignment to| '.$user_details->firstname.' '.$user_details->lastname ,$body);


      }
	
    }

}else{

	$record=new stdclass();
	 $record->guru_id=0;
	 $record->nj_id=$insertitems;
	 $current_time=time();
	 $current_time=date('Y-m-d H:i:s', $current_time);
	 $record->createddate=$current_time;

	 $user_details_query="SELECT * FROM {user} WHERE id=$insertitems";

	 $user_details=$DB->get_record_sql($user_details_query);

	 $DB->insert_record('guru_nj_mapping',$record);

	 // send a mail to the success champion of the user


	 $body='Hi'.' '.$success_champion_obj->firstname.' '.$success_champion_obj->lastname.',

		No guru could be assigned to '.$user_details->firstname.' '.$user_details->lastname.' automatically. Please assign a guru manually to this user.

		 ';

             email_to_user($user,$admin,'Manual Guru Assignment to| '.$user_details->firstname.' '.$user_details->lastname ,$body);

}
		
}else{
	echo 'User Exist '.$userdetails->EMP_NO;
	echo '<br>';
	
	$users = $DB->get_record_sql('SELECT * FROM {user} where username = ?',array($userdetails->EMP_NO));
	
	for($i=2;$i<=12;$i++){
	if($i == 2){
			$datas = $userdetails->emp_unit;
			}
			if($i == 3){
			$datas = $userdetails->emp_region;
			}
			if($i == 4){
			$datas = $userdetails->emp_p_l_area;
			}
			if($i == 5){
			$datas = $userdetails->emp_location;
			}
			if($i == 6){
			$datas = $userdetails->emp_state;
			}
			if($i == 7){
			$datas = $userdetails->emp_department;
			}
			if($i == 8){
			$datas = $userdetails->emp_designation;
			}
			if($i == 9){
			$datas = $userdetails->emp_appraiser_id;
			}
			if($i == 10){
			$datas = $userdetails->emp_appraiser_name;
			}
			if($i == 11){
			$datas = $userdetails->emp_product;
			}
			if($i == 12){
			$datas = $userdetails->emp_date_of_joining;
			}
		$updatecustomfields=$DB->execute("update {user_info_data} set data = '".$datas."' where fieldid ='".$i."' AND userid = '".$users->id."'");	
	}
					
	}
		}



 function distancebetween($userlat,$userlang,$gurulat,$gurulang){

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
	

?>