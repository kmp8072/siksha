<?php
require_once('config.php');
if($_GET['type']=='course'){

$courseid = $_POST['courseid'];

$details = $DB->get_records_sql("SELECT  u.firstname,u.lastname,max(qa.sumgrades) as grades,q.name FROM {quiz_attempts} qa JOIN {quiz} q ON q.id = qa.quiz JOIN {user} u ON qa.userid = u.id WHERE q.course = ? GROUP BY qa.userid ORDER BY qa.sumgrades DESC LIMIT 10",array($courseid));
   // print_r($details);
 
  if(!empty($details)){
    $table = new html_table();
    $table->head = array('Rank', 'Fullname','Quiz Name', 'Score');
    $i=1;

    foreach ($details as $key => $value) {
      $fullname = $value->firstname ." ". $value->lastname ;
      $score = $value->grades;
      $quizname = $value->name;
      $table->data[] = array($i,$fullname,$quizname,round($score));
      $i++;
      
    }
    
  echo html_writer::table($table);
  }else{
		 echo "No Data Found";
	}
	
}else if($_GET['type']=='program'){
	$programid = $_POST['programid'];
	  $query = $DB->get_records_sql("SELECT * FROM {leaderboard} WHERE programid = ? ORDER BY score DESC",array($programid));
		  if(!empty($query)){
		  	$table = new html_table();
		    $table->head = array('Rank','Fullname', 'Score');
		    $i=1;
		    foreach ($query as $key => $value) {
		      $fullname = $value->fullname;
		      $score = $value->score;
		      $table->data[] = array($i,$fullname,$score);
		      $i++;
		    }
		    
		    echo html_writer::table($table);
		  }else{
		    echo "No Data Found";
		  }
}
?>