<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_login();
global $SESSION,$DB,$CFG;
$id= $_POST['id'];
$cid= $_POST['cid'];
$aid= (explode(",",$id));
print_r($aid);
for($i = 0; $aid[$i] != "";$i++){
	$delete = $DB->execute("DELETE FROM {request_to_enrol} WHERE id =".$aid[$i]."");
}

// $url = "/nucleus/local/tvs_nomination/script.php?courseid=".$cid."";
// redirect($url);