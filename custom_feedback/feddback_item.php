<?php
// error_reporting(1);
// ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

require('apiClass.php');

$objAPI = new apiClass();
$action = $_REQUEST['action'];

switch ($action) {
	case 'GETFEEDDETAIL':
		if(isset($_REQUEST['feedback_id']) && $_REQUEST['feedback_id'] != "" && isset($_REQUEST['userid']) && $_REQUEST['userid'] != "") {
				$feedback_id=$_REQUEST['feedback_id'];
				$userid=$_REQUEST['userid'];
				echo $objAPI->jsonConvert($objAPI->getFeedbackDetail($feedback_id,$userid));
		} else {
			echo $objAPI->jsonConvert(array('success' => FALSE, $data => "FeedbackId and userid required"));
		}
		
		break;

		case 'SUBMITFEEDDETAIL':
		if(isset($_REQUEST['feedback_id']) && $_REQUEST['feedback_id'] != "" && isset($_REQUEST['userid']) && $_REQUEST['userid'] != "" && isset($_REQUEST['response']) && $_REQUEST['response'] != "") {
				$feedback_id=$_REQUEST['feedback_id'];
				$userid=$_REQUEST['userid'];
				$response=$_REQUEST['response'];
				

				if(isset($_REQUEST['ispresent'])){
					$ispresent=$_REQUEST['ispresent'];
				}else{
					$ispresent=NULL;
				}

				 // echo $ispresent;
				 // die();

				echo $objAPI->jsonConvert($objAPI->submitFeedbackDetail($feedback_id,$userid,$response,$ispresent));
		} else {
			echo $objAPI->jsonConvert(array('success' => FALSE, $data => "FeedbackId and userid and response required"));
		}
		
		break;
	
	default:
		echo 'Invalid API call';
		break;
}



	
	