<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
global $USER, $DB;
$keyword =$_POST['q'];
$search_param = "%".$keyword."%";
$pid = $_POST['pid1'];

$users = $DB->get_records_sql("SELECT pa.*,u.firstname,u.lastname FROM {prog_assignment} pa LEFT JOIN {user} u on u.id = pa.assignmenttypeid WHERE pa.programid =".$pid." AND (u.firstname LIKE '".$keyword."%' OR u.lastname LIKE '".$keyword."%') ");
			

			                                     
			// $areanames[] = "";
			$data = array();
			foreach ($users as $areaid => $searcharea) {			  

			  array_push($data,$searcharea->firstname." ".$searcharea->lastname);
			 // $data[] = $searcharea->firstname;                                                                 
			}   
			echo json_encode($data); 
		


