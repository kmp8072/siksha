<?php 
//define('CLI_SCRIPT', true);
require('../config.php');
global $CFG,$DB;

$url = "https://ssoexternal.tvscredit.com/Service.Asmx/Fun_Get_Emp_Details";

$headers = array(
                        "Content-type: application/json;charset=\"utf-8\"",
                        "Accept: application/json",
                        "Cache-Control: no-cache",
                        "Pragma: no-cache",
                       
                    ); //SOAPAction: your op URL
  
			
	$data = array("Access_Code" => "VSH00001","Access_Password" => "Tvs@So@3318","NoOfDays" => "12470");	
	$data_string = json_encode($data);	
			$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,$data_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

$server_output = curl_exec ($ch);


$responses = json_decode($server_output);

print_object($server_output);
die();

foreach($responses as $response){
	foreach($response as $userdetails){
		echo 'Username '.$userdetails->EMP_NO;
		echo '<br>';
		
		$check_user = $DB->get_records_sql('SELECT * FROM {user} where username = ?',array($userdetails->EMP_NO));

if(empty($check_user)){
	
	$username = trim($userdetails->EMP_NAME);
	
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


		$records->username     		=  $userdetails->EMP_NO;

		$records->firstname    		=  $firstname;

		$records->lastname     		=  $lastname;

		$records->password     		=  md5('Admin@123');	
		
		$records->email     		=  $userdetails->EMP_EMAILID;
		
		$records->phone1     		=  $userdetails->EMP_MOBILENO;			


		$records->confirmed     	=  '1';


		$records->mnethostid     	=  '1';


		$insertitems = $DB->insert_record('user', $records);
		echo 'User Insert';
		print_r($insertitems);
		echo '<br>';
		
		
		
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
		
		
		
	
		for($i=2;$i<=10;$i++){
		$records1 = new stdClass();
		$userid = $insertitems;
		if($i == 2){
			$datas = $userdetails->EMP_UNIT;
			}
			if($i == 3){
			$datas = $userdetails->EMP_REGION;
			}
			if($i == 4){
			$datas = $userdetails->EMP_P_L_AREA;
			}
			if($i == 5){
			$datas = $userdetails->EMP_LOCATION;
			}
			if($i == 6){
			$datas = $userdetails->EMP_STATE;
			}
			if($i == 7){
			$datas = $userdetails->EMP_DEPARTMENT;
			}
			if($i == 8){
			$datas = $userdetails->EMP_DESIGNATION;
			}
			if($i == 9){
			$datas = $userdetails->EMP_APPRAISER_ID;
			}
			if($i == 10){
			$datas = $userdetails->EMP_APPRAISER_NAME;
			}

		$records1->userid     		=  $userid;

		$records1->fieldid    		=  $i;

		$records1->data     			=  $datas;

		$insertitems1 = $DB->insert_record('user_info_data', $records1);
		
		echo 'User infodata Insert';
		print_r($insertitems1);
		echo '<br>';
		
		}
		
		
		
}else{
	echo 'User Exist '.$userdetails->EMP_NO;
	echo '<br>';
	
	$users = $DB->get_record_sql('SELECT * FROM {user} where username = ?',array($userdetails->EMP_NO));
	
	for($i=2;$i<=10;$i++){
	if($i == 2){
			$datas = $userdetails->EMP_UNIT;
			}
			if($i == 3){
			$datas = $userdetails->EMP_REGION;
			}
			if($i == 4){
			$datas = $userdetails->EMP_P_L_AREA;
			}
			if($i == 5){
			$datas = $userdetails->EMP_LOCATION;
			}
			if($i == 6){
			$datas = $userdetails->EMP_STATE;
			}
			if($i == 7){
			$datas = $userdetails->EMP_DEPARTMENT;
			}
			if($i == 8){
			$datas = $userdetails->EMP_DESIGNATION;
			}
			if($i == 9){
			$datas = $userdetails->EMP_APPRAISER_ID;
			}
			if($i == 10){
			$datas = $userdetails->EMP_APPRAISER_NAME;
			}
		$updatecustomfields=$DB->execute("update {user_info_data} set data = '".$datas."' where fieldid ='".$i."' AND userid = '".$users->id."'");	
	}
			
			
	}
		}
	}
if ( curl_errno($ch) ) { 

			$result = 'Curl Error -> ' . curl_errno($ch) . ': ' . curl_error($ch); 
		} else { 
			$returnCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); 
			switch($returnCode){ 
				case 200: 
					//$result = 'Response: ' . $result;
					break; 
				default: 
					$result = 'HTTP ERROR -> ' . $returnCode; 
					break; 
			} 
		} 
curl_close ($ch);
?>