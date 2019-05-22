
<?php 

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, GET, POST");
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

/*@Author: Krishna
*/
require('../config.php');

class apiClass {
	

	public function jsonConvert($object) {
		return json_encode($object);
	}

	public function getFeedbackDetail($feedback_id,$userid) {
		global $DB;

		$query1="select * from {feedback_completed} where feedback=$feedback_id and userid=$userid";
		$record_get_feedback_completed =$DB->get_record_sql($query1);
		
		if ($record_get_feedback_completed->id) {
    
			$output = array('success' => FALSE, 'data'=> "Already submitted the feedback");
			return $output;
		}

		$query="select * from {feedback_item} where feedback=$feedback_id order by position";
		$record_get_feedback_items =$DB->get_records_sql($query);

		$result = array();

		if(count($record_get_feedback_items) > 0 ) {
			foreach ($record_get_feedback_items as $record_get_feedback_item) { 
				$row =array();
				$row['id'] = $record_get_feedback_item->id;
				$row['name'] = $record_get_feedback_item->name;
				//$row['presentation'] = $record_get_feedback_item->presentation;
				$row['typ'] = $record_get_feedback_item->typ;
				$row['position'] = $record_get_feedback_item->position;

				if($record_get_feedback_item->typ=='multichoicerated') {
					$row['option_count'] = count(explode('|',$record_get_feedback_item->presentation));
				}
				if($record_get_feedback_item->typ=='label') {
					$row['presentation']  = strip_tags($record_get_feedback_item->presentation);
				}

				$result[] = $row;
		
			}

			$query="select tagid from {tag_instance} where itemtype='feedback' and itemid=$feedback_id";
                $result1=$DB->get_record_sql($query);

                $tagid=$result1->tagid;

                if ($tagid==1) {

                	$output = array('success' => TRUE, 'data' => $result, 'fedback_type' => 'offline');
                	
                	//$result['fedback_type']='offline';
                }else{

                	$output = array('success' => TRUE, 'data' => $result, 'fedback_type' => 'online');
                }

// print_object($record_get_feedback_items);die;
			//$output = array('success' => TRUE, 'data' => $result);
		} else {
			$output = array('success' => FALSE, 'data' => "Invalid feedback");
		}
		
	return $output;
	}

	public function submitFeedbackDetail($feedback_id,$userid,$response,$ispresent=NULL)
	{

        global $DB;

		$timemodified=time();
		 

	 $query="INSERT INTO {feedback_completed} (feedback, userid, timemodified, random_response, anonymous_response)
            VALUES ($feedback_id, $userid, $timemodified, 0, 2)";
		   
		    $DB->execute($query);
		   	

		    $query1= "SELECT id FROM {feedback_completed} WHERE userid=$userid AND feedback=$feedback_id";
            $id_object =$DB->get_record_sql($query1);  
            $id=$id_object->id;
           
		    $query2="INSERT INTO {feedback_tracking} (feedback, userid, completed,ispresent)
            VALUES ($feedback_id, $userid, $id,$ispresent)";
		    $DB->execute($query2);

		    $response=json_decode($response);

		    
		    foreach ($response as $responses) {
		    	$questionId=$responses->qid;
		    	$answer=$responses->ans;

		    $query3="INSERT INTO {feedback_value} (item, completed, value)
            VALUES ($questionId, $id, '$answer')";
		    
		    if($DB->execute($query3)){

		    	$val="success";

		    }else{

		    	$val="failure";

		    	$query5="DELETE FROM {feedback_completed}
                 WHERE id=$id";
                 $DB->execute($query5);

                 $query6="DELETE FROM {feedback_tracking}
                 WHERE completed=$id";
                 $DB->execute($query6);

                 $query7="DELETE FROM {feedback_value}
                 WHERE completed=$id";
                 $DB->execute($query7);

		    	break;

		    }


		    }

		    if($val=="success"){

		    	$output = array('success' => TRUE, 'data' => "feedback succesfully inserted");

		    }else{

		    	$output = array('success' => FALSE, 'data' => "feedback couldn't be taken");

		    }

		    	return $output;
	}


} 