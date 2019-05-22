<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

global $DB;

$courseid = $_POST['selectedcourseid'];



$allscorms = $DB->get_records_sql('SELECT * FROM {scorm} where course = ?',array($courseid)); 

foreach($allscorms as $fields=>$allscorm){

$data[] = $allscorm->name;

}

echo json_encode($data);

?>

