<?php 

// first make a cron for updating isactive column in nj_guru_mapping table

require_once 'config.php';

global $DB;

 $current_time=time();
 $current_date=date('Y-m-d', $current_time);

// check duration of mapping

$duration_query="SELECT DISTINCT id,nj_id,guru_id,UNIX_TIMESTAMP(createddate) AS createddate,induction_start_date,status FROM mdl_guru_nj_mapping";

$duration_objs=$DB->get_records_sql($duration_query);

//print_object($duration_objs);

echo "updating nj_guru_mapping and detaching if completed one year period";
echo "<br>";

foreach ($duration_objs as $duration_obj) {
	
	// check if current time and created time difference is greater than one year detach them
	$id=$duration_obj->id;
	$nj_id=$duration_obj->nj_id;
	$status=$duration_obj->status;
	$guru_id=$duration_obj->guru_id;
	$createddate=$duration_obj->createddate;
	$induction_start_date=$duration_obj->induction_start_date;
	$induction_start_date = date('Y-m-d', $induction_start_date);

	if ($current_time-$createddate>=31536000) {
		
		$detach_query="UPDATE mdl_guru_nj_mapping SET isactive=5 WHERE id=$id";

	}


	// send notification to nj and guru if their induction start day matches with the current date

	if ($induction_start_date==$current_date) {
		
		// find phone no of current guru and nj

		$nj_details_query="SELECT * FROM mdl_user WHERE id=$nj_id";
		$guru_details_query="SELECT * FROM mdl_user WHERE id=$guru_id";

		$nj_details_obj=$DB->get_record_sql($nj_details_query);
		$guru_details_obj=$DB->get_record_sql($guru_details_query);

		// send message to new joinee

		$message1 = 'Dear '.$nj_details_obj->firstname.' '.$nj_details_obj->lastname.', Today is your first day of induction please enable location service and update about induction in lms. Regards, Team Tvs Siksha.';
		 $mobile = $nj_details_obj->phone1;
		 
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


		   $message1 = 'Dear '.$guru_details_obj->firstname.' '.$guru_details_obj->lastname.', Today is your first day of induction please enable location service and update about induction in lms. Regards, Team Tvs Siksha.';
		   $mobile = $guru_details_obj->phone1;
		 
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

	}


	// now check if guru has not responded in 24 hours of request

	if ($status==3 && ($current_time-$createddate>=86400)) {
		//send a reminder to guru


		$message1 = 'Dear '.$guru_details_obj->firstname.' '.$guru_details_obj->lastname.', Please respond to induction request of user. Regards, Team Tvs Siksha.';
		   $mobile = $guru_details_obj->phone1;
		 
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

	}


}







?>