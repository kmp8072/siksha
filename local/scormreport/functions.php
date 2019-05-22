<?php

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');



$usercourseid = $_POST['usercourseid'];

$coursescormname = $_POST['coursescormname'];

$scormid = $DB->get_record_sql('SELECT id FROM {scorm} where name = ? ',array($coursescormname)); 



 if(isset($_POST["Export"])){

		 

      header('Content-Type: text/csv; charset=utf-8');  

      header('Content-Disposition: attachment; filename=scormreport.csv');  

      $output = fopen("php://output", "w");  

      fputcsv($output, array ('S.No.','Firstname','Lastname','Username','Attempt','Score','Cumulative Score'));  

	  

	  

	  $allscormsdetails = $DB->get_records_sql('SELECT * FROM {scorm} where course = ? AND name = ?',array($usercourseid,$coursescormname)); 

	foreach($allscormsdetails as $allscormsdetail){

		$scormsdetails = $DB->get_records_sql('SELECT * FROM {scorm_scoes} where scorm = ? AND sortorder = ?',array($allscormsdetail->id,'2')); 

		foreach($scormsdetails as $scormsdetail){

		$scormsscoresdetails = $DB->get_records_sql('SELECT id,userid,attempt,value,element FROM {scorm_scoes_track} where scormid = ? AND scoid = ? AND element = ? ',array($allscormsdetail->id,$scormsdetail->id,'cmi.core.score.raw','cmi.core.lesson_status')); 

		$i=1;

		

		foreach($scormsscoresdetails as $scormsscoresdetail){

		$row = array();

		$userdetails = $DB->get_records_sql('SELECT * FROM {user} where id = ?',array($scormsscoresdetail->userid)); 

		foreach($userdetails as $userdetail){

		/*$usergamescores = $DB->get_records_sql('SELECT gamescore FROM {user_gamescore} where userid = ? AND scormid = ?',array($userdetail->id,$scormid->id)); 

		if(!empty($usergamescores)){

		foreach($usergamescores as $usergamescore){

		$gamescore = $usergamescore->gamescore;

		}

		}else{

		$gamescore = 'Game not start';

		}
*/
		

		$id = $i;

		array_push($row,$id);

		$firstname = $userdetail->firstname;

		array_push($row,$firstname);

		$lastname = $userdetail->lastname;

		array_push($row,$lastname);

		$username = $userdetail->username;

		array_push($row,$username);

		$attempt = $scormsscoresdetail->attempt;

		array_push($row,$attempt);

		$score = $scormsscoresdetail->value;

		array_push($row,$score);

		if($attempt == '1'){

		 $cumulativescore = $score;

		 array_push($row,$cumulativescore);

		}elseif($attempt == '2'){

			$cumulativescore = ($score - ($score*(10/100)));

			array_push($row,$cumulativescore);

		}elseif($attempt == '3'){

			$cumulativescore = ($score - ($score*(20/100)));

			array_push($row,$cumulativescore);

		}elseif($attempt == '4'){

			$cumulativescore = ($score - ($score*(30/100)));

			array_push($row,$cumulativescore);

		}elseif($attempt == '5'){

			$cumulativescore = ($score - ($score*(40/100)));

			array_push($row,$cumulativescore);

			

		}

		//array_push($row,$gamescore);

		}

		fputcsv($output, $row); 

		$i++;

		}

		

		}

	}

 

      fclose($output);  

 }  

 