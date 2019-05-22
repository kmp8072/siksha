<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
global $USER, $DB;
$user = $_POST['users'];
$score = $_POST['score'];
$pid = $_POST['pid'];
$id = $_POST['id'];

for($i = 0; $i < sizeof($user);$i++){
	 $val = $user[$i];
	 $sco = $score[$i];
	 if($id[$i] > 0){
		 	
		 	$update = $DB->execute("UPDATE {leaderboard} SET fullname = '".$val."', score = ".$sco." WHERE id = ".$id[$i]."");
		 }else{

		 $record = new stdclass();
		 $record->programid = $pid;
		 $record->fullname = $val;
		 $record->score = $sco;
		 $sql = $DB->insert_record("leaderboard",$record); 
	}
}

if(!empty($_POST['did'])){
	$did = $_POST['did'];
	$del = $DB->delete_records('leaderboard',array('id'=>$did));
}

 


// print_r($user);
// echo "<br>";
// print_r($score);
