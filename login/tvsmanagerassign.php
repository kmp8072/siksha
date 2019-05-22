<?php 
// define('CLI_SCRIPT', true);
require('../config.php');
global $CFG,$DB;

$allusers = $DB->get_records_sql('SELECT * FROM {user} WHERE id != ? AND id != ?',array('1','2'));
foreach($allusers as $alluser){
	echo 'userid '.$alluser->id;
	echo '<br>';
	$managers = $DB->get_record_sql('SELECT * FROM {user_info_data} WHERE userid = ? AND fieldid = ?',array($alluser->id,'8'));
	if(!empty($managers)){
	echo 'managerid '.$managers->data;
	echo '<br>';
	$searchusers = $DB->get_record_sql('SELECT * FROM {user} WHERE username = ? ',array($managers->data));
	if(!empty($searchusers)){
	echo 'searchuserid '.$searchusers->id;
	echo '<br>';
	$jobidsearchs = $DB->get_record_sql('SELECT * FROM {job_assignment} WHERE userid = ? ',array($searchusers->id));
	if(!empty($jobidsearchs)){
	echo 'searchuserid '.$jobidsearchs->id;
	echo '<br>';
	
	$updatecustomfields=$DB->execute("update {job_assignment} set managerjaid = '".$jobidsearchs->id."' where userid ='".$alluser->id."'");	
	print_R($updatecustomfields);
	}
	}
}
	}
?>