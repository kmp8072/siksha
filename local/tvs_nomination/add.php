<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
 $eid= $_POST['eid'];
 $cid= $_POST['cid'];
 $mid = $_POST['mid'];
$test= (explode(",",$eid));

for($i = 0; $test[$i] != "";$i++){
	// echo $test[$i];
	$select  = $DB->get_records_sql("SELECT * FROM {request_to_enrol} WHERE user_id = ".$test[$i]." AND manager_id = ".$mid." AND  course_id = ".$cid." ");
	if(empty($select)){
		$record = new stdclass();
		$record->manager_id = $mid;
		$record->user_id = $test[$i];
		$record->course_id=$cid;
		$insert = $DB->insert_record('request_to_enrol',$record);
		if($insert){
			echo "success";

		}else{
			echo "fail";
		}

	}else{
		echo"hi";
	}
}
// echo $ooo
