<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

  global $CFG,$USER,$DB;
  $reqid = $_POST['request_id'];

  for($i = 0; $reqid[$i] != "";$i++){
    $req = $reqid[$i];
  	
  	$sql = $DB->get_record_sql("SELECT * FROM {request_to_enrol} WHERE id = ".$reqid[$i]." ");
		$userid= $sql->user_id;
		$cid = $sql->course_id;
    $user =  $DB->get_record_sql("SELECT * FROM {user} WHERE id = ".$userid." ");
	  $enrolid = $DB->get_record_sql("SELECT id FROM {enrol} WHERE courseid = ".$cid." AND enrol = 'manual'");
  	$enrol = $DB->get_record_sql("SELECT id FROM {user_enrolments} WHERE userid = ".$userid." AND enrolid = ".$enrolid->id." AND status = 1");
    $delete_enrol = $DB->execute("DELETE FROM {user_enrolments} WHERE id = ".$enrol->id." ");
	  $delete_request = $DB->execute("DELETE FROM {request_to_enrol} WHERE id = ".$req." ");
	  // echo $delete_request;
    echo "<div style ='background-color:rgba(241, 39, 39, 0.5); padding : 10px;'>".$user->firstname." ".$user->lastname." Request Canceled</div>";

  }