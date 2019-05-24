<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $USER, $DB, $CFG;

require_login();

 $userid=$_POST['userid'];
 $key=$_POST['key'];
 $data=$_POST['data'];

 switch ($key) {
 	case 'sendwelcomemessage':
 		$result=sendwelcomemessage($userid);
 		echo $result;
 		break;

 	case 'findgurustable':
 		$result=findgurustable($userid);
 		echo $result;
 		break;

 	case 'assignguru':
 		$result=assignguru($data);
 		echo $result;
 		break;
 	
 	default:
 		
 		break;
 }

 function sendwelcomemessage($userid)
 {

 		global $USER, $DB, $CFG;

         $user_details_query="SELECT * FROM {user} WHERE id=$userid"; 
         $user_details_obj=$DB->get_record_sql($user_details_query);   

             $user = new stdClass();
		     $user->id = $user_details_obj->id;
             $user->email     = $user_details_obj->email;
             $user->firstname = $user_details_obj->firstname;
 		     $user->lastname = $user_details_obj->lastname;
             $user->maildisplay = true;
             $user->mailformat = 1;

 	$message1 = 'Dear '.$user->firstname.', Welcome to the TVS Siksha LMS App. Download link for Android Phone is https://tinyurl.com and iPhone is https://tinyurl.com. Your username is '.$user_details_obj->username.'. You will receive your password in a separate SMS. Regards, Team Tvs Siksha.';
		 //$mobile = $user_details_obj->emp_mobileno;
		 
		 //MESSAGE ONE
			$time = date('d-m-YTH:i:s');
			$message1 = urlencode($message1);
			$url1 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message1&reqid=1";
			$curl = curl_init();
		   // OPTIONS:
		   curl_setopt($curl, CURLOPT_URL, $url1);
		   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		   $result1 = curl_exec($curl);
		   if(!$result1){
		   	return 0;
		   	die("Connection Failure");

		   }
		   curl_close($curl);
		   
		   sleep(1);
		   //MESSAGE TWO
		   $message2 = 'Dear '.$user->firstname.', Your password to access TVS Siksha LMS app is Admin@123. Regards, Tvs Siksha.';
			$time = date('d-m-YTH:i:s');
			$message2 = urlencode($message2);
			$url2 = "http://alotsolutions.in/API/WebSMS/Http/v1.0a/index.php?username=ShezarWeb&password=^yiIVY!9&sender=TCLCPB&to=7903038104&message=$message2&reqid=1";
			$curl2 = curl_init();
		   // OPTIONS:
		   curl_setopt($curl2, CURLOPT_URL, $url2);
		   curl_setopt($curl2, CURLOPT_RETURNTRANSFER, 1);
		   curl_setopt($curl2, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		   $result2 = curl_exec($curl2);
		   if(!$result2){
		   	return 0;
		   	die("Connection Failure");

		   }
		   curl_close($curl2);

		   // echo "string";

		   if ($result1 && $result2) {

		   	//check if entry exists for this user

		   	  $check_existence_query="SELECT id,no_of_times FROM {welcome_msg_time} WHERE userid=$userid";

		   	$check_existence_obj=$DB->get_record_sql($check_existence_query);

		   	//print_object($check_existence_obj);

		   	 //echo $count=count($check_existence_obj);

		   	 $no_of_times=$check_existence_obj->no_of_times;

		   	 $sending_time_no=$no_of_times+1;

		   	if (!empty($check_existence_obj)) {
		   //update the table of welcome message sent

		   	$updatetime=time();

		   	   $update_query="UPDATE {welcome_msg_time} SET senderid=$USER->id,no_of_times=$sending_time_no WHERE userid=$userid";

		   	$DB->execute($update_query);
		   	}else{
		   		// insert data into table

                $current_time=time();
                $current_time=date('Y-m-d H:i:s', $current_time);

		   	    $update_query="INSERT INTO {welcome_msg_time} (userid,senderid,no_of_times,createddate)  VALUES($userid,$USER->id,1,$current_time)";


		   	$DB->execute($update_query);

		   	}

		   	return 1;

		   }else{

		   	return 0;

		   }




 }




function findgurustable($userid)
 {
 	global $USER, $DB, $CFG;

 	 $admins= get_admins();
     $adminkeys=array_keys($admins);
     $adminkeys=implode(',', $adminkeys);


     $find_gurus_query="SELECT u.id FROM mdl_user u 
     JOIN mdl_role_assignments ra ON ra.userid=u.id
     WHERE ra.roleid=4 AND u.id NOT IN($adminkeys)";   
     $getrecords=$DB->get_records_sql($find_gurus_query);

     $guru_data_objs=array();

     foreach ($getrecords as $getrecord) {

     $user_id=$getrecord->id;

     $no_of_nj_mapped_query="SELECT count(id) as nj_mapped_count FROM mdl_guru_nj_mapping WHERE (status=1 OR status=0) AND guru_id=$user_id";

     $no_of_nj_mapped_obj=$DB->get_record_sql($no_of_nj_mapped_query);

     $no_of_nj_mapped=$no_of_nj_mapped_obj->nj_mapped_count;

     $guru_data_query="SELECT u.id,u.username,CONCAT(u.firstname,' ',u.lastname) as userfullname, u.address FROM {user} u WHERE u.id=$user_id";

     $guru_data_obj=$DB->get_record_sql($guru_data_query);

     $guru_data_obj->mapped_nj=$no_of_nj_mapped;

     //find extra info about guru

     $guru_extra_info_query="SELECT uif.id,uif.name,uid.data FROM {user} u 
     JOIN {user_info_data} uid ON uid.userid=u.id
     JOIN {user_info_field} uif ON uif.id=uid.fieldid
     WHERE u.id=$user_id";

     $guru_extra_info_obj=$DB->get_records_sql($guru_extra_info_query);

     $guru_data_obj->extra_info=$guru_extra_info_obj;

     $guru_data_objs[]=$guru_data_obj;

     //print_object($guru_data_obj);
       
     }

?>
	
 	<form method="" action="" id="assignguruform">

 		<input type="hidden" name="userid" value="<?php echo $userid;?>">

      <table class="table" id="gurustable">
     <thead>
         <tr>
             <th></th>
             <th>Guru Name</th>
             <th>Address</th>
             <th>No of Students</th>
             <th>Unit</th>
             <th>Region</th>
             <th>Location</th>
             <th>State</th>
             <th>Department</th>
             <th>Designation</th>

             

         </tr>
     </thead>
     <tbody>
      <?php
             foreach ($guru_data_objs as $guru_data_ob) {

              ?>

               <tr>

             <td>
                 <div class="radio">
                     <label><input type="radio" id="<?php echo $guru_data_ob->id;?>" name="optradio" value="<?php echo $guru_data_ob->id;?>" onclick="enablesubmission(this.value)"></label>
                 </div>
             </td>
             <td><?php echo $guru_data_ob->userfullname ;?></td>
             <td><?php echo $guru_data_ob->address ;?></td>
             <td><?php echo $guru_data_ob->mapped_nj ;?></td>
             <td><?php echo $guru_data_ob->extra_info[2]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[3]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[5]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[6]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[7]->data ;?></td>
             <td><?php echo $guru_data_ob->extra_info[8]->data ;?></td>
             
         </tr>
          <?php
               
          }

         ?>   
         </tbody>
</table>
<!-- <input type="submit" name="submit" class="btn btn-default" id="assignguruformsubmit"> -->
 </form>

  <?php       

 }


function assignguru($data)
{

global $USER, $DB, $CFG;
	
$userid=$data['userid'];
$guruid=$data['optradio'];

// find the device token of guru to send push notification

$guru_details_query="SELECT u.id,CONCAT(u.firstname,' ',u.lastname) AS gurufullname,u.device_token,u.phone1 FROM mdl_user WHERE id=$guruid";

$guru_details_obj=$DB->get_record_sql($guru_details_query);

$device_token=$guru_details_obj->device_token;

$gurufullname=$guru_details_obj->gurufullname;

$mobile=$guru_details_obj->phone1;

// check if new joinee id is already in table 

 $check_existence_query="SELECT id FROM {guru_nj_mapping} WHERE nj_id=$userid";

$check_existence_obj=$DB->get_record_sql($check_existence_query);

if (empty($check_existence_obj)) {
	// make a entry for the new joinee to this table

	 $insert_nj_query="INSERT INTO {guru_nj_mapping} (guru_id,nj_id,status,successchamp_id) VALUES($guruid,$userid,3,$USER->id)";

	if($DB->execute($insert_nj_query)){

        // send message to guru 
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
       //if(!$result){die("Connection Failure");}
       curl_close($curl2); 

       // now send push notification

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

		return 1;
	}else{

	return 0;
   }

}else{

 $map_guru_to_nj_query="UPDATE {guru_nj_mapping} SET guru_id=$guruid,successchamp_id=$USER->id,status=3 WHERE nj_id=$userid";

if($DB->execute($map_guru_to_nj_query)){


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
       //if(!$result){die("Connection Failure");}
       curl_close($curl2); 

       // now send push notification

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

	return 1;

}else{

	return 0;
}

}

}



?>