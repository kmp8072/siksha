<?php

// require_once($CFG->wwwroot.'config.php');
// require_once($CFG->libdir . '/coursecatlib.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');



// $PAGE->set_title($SITE->fullname);
$strheading = "Record Of Learning";//heading of page
// $PAGE->set_pagelayout('standard');
// $PAGE->set_context(context_system::instance());
// //$PAGE->set_url(new moodle_url('/local/create_leaderboard/dashboard.php'));
// $PAGE->set_heading($strheading);
// $PAGE->set_title($strheading);
// $PAGE->navbar->add($strheading);
require_login();

echo $OUTPUT->header();
global $USER, $DB,$CFG;
$uid =$USER->id;


$table = new html_table();
// $table->head = array('Course Title', 'Scorm Name', 'Scorm Score','Quiz Name','Quiz Score','Progress');

$course =  $DB->get_records_sql("SELECT c.fullname,c.id FROM {user_enrolments} ue JOIN {enrol} e ON ue.enrolid = e.id JOIN {course} c ON e.courseid = c.id WHERE ue.userid = ".$uid."");
// print_r($course);

foreach ($course as $key => $val) {
	$act = $DB->get_records_sql('SELECT * FROM {course_modules} WHERE course = '.$val->id.' AND module IN (18,20)');
	$actcount[] = count($act);
}
$max = max($actcount);
$coursetitle1 = array();
$coursetitle1[] = "Course Title";
$activities = array();
for($i =1 ;$i <= $max; $i++){
	$activities[] = "Acitivity".$i." Name";
	$activities[] = "Acitivity".$i." Type";
	$activities[] = "Acitivity".$i." Score";
}
$progress = array();
$progress[] = "Progress";
$final = array();
$final = array_merge($coursetitle1, $activities, $progress);
$table->head = $final;
if(!empty($course))
{	foreach ($course as $key => $value) {
		$activity = array();
		$ss = array();
		$test = array();
		$i = 0;
		$coursetitle =  $value->fullname;
		$courseid = $value->id;
		$acivities = $DB->get_records_sql('SELECT * FROM {course_modules} WHERE course = '.$courseid.' AND module IN (18,20)');
		$thiscourseactivitycount = count($acivities);
		$activitycount[] = count($acivities);

		array_push($ss,$value->fullname);

		if($max == $thiscourseactivitycount){
		
			foreach($acivities as $coursemodule){
				
				if($coursemodule->module == '18'){
					$quizname = $DB->get_record_sql('SELECT name from {quiz} where id = ?',array($coursemodule->instance));
					// $quizname = $quizname->name;
					$name = $quizname->name;
					$type = "quiz";
					$quizgradedetails = $DB->get_record_sql('SELECT gg.finalgrade from {grade_grades} as gg INNER JOIN {grade_items} as gi ON gi.id = gg.itemid where courseid = ? AND itemtype = ? AND itemmodule = ? AND userid =?',array($courseid,'mod','quiz',$USER->id));
					$grade = $quizgradedetails->finalgrade;
					

				}elseif($coursemodule->module == '20'){
					$scormname = $DB->get_record_sql('SELECT name from {scorm} where id = ?',array($coursemodule->instance));
					// $scormname = $scormname->name;
					$name =$scormname->name;
					$type = "scorm";
					$scormgradedetails = $DB->get_record_sql('SELECT gg.finalgrade from {grade_grades} as gg INNER JOIN {grade_items} as gi ON gi.id = gg.itemid where courseid = ? AND itemtype = ? AND itemmodule = ? AND userid =?',array($courseid,'mod','scorm',$USER->id));
					$grade = $scormgradedetails->finalgrade;
					
				}

				array_push($ss, $name);
				array_push($ss, $type);
				array_push($ss, $grade);
				$coursecomplition = $DB->get_record_sql("SELECT completionstate FROM {course_modules_completion} WHERE coursemoduleid  = ".$coursemodule->id." AND userid = ".$uid."");
				if($coursecomplition->completionstate != 0){
					$i++;
				}
			}
		}else if( $thiscourseactivitycount < $max){
			foreach($acivities as $coursemodule){
				
				if($coursemodule->module == '18'){
					$quizname = $DB->get_record_sql('SELECT name from {quiz} where id = ?',array($coursemodule->instance));
					// $quizname = $quizname->name;
					$name = $quizname->name;
					$type = "quiz";
					$quizgradedetails = $DB->get_record_sql('SELECT gg.finalgrade from {grade_grades} as gg INNER JOIN {grade_items} as gi ON gi.id = gg.itemid where courseid = ? AND itemtype = ? AND itemmodule = ? AND userid =?',array($courseid,'mod','quiz',$USER->id));
					$grade = $quizgradedetails->finalgrade;
					

				}elseif($coursemodule->module == '20'){
					$scormname = $DB->get_record_sql('SELECT name from {scorm} where id = ?',array($coursemodule->instance));
					// $scormname = $scormname->name;
					$name =$scormname->name;
					$type = "scorm";
					$scormgradedetails = $DB->get_record_sql('SELECT gg.finalgrade from {grade_grades} as gg INNER JOIN {grade_items} as gi ON gi.id = gg.itemid where courseid = ? AND itemtype = ? AND itemmodule = ? AND userid =?',array($courseid,'mod','scorm',$USER->id));
					$grade = $scormgradedetails->finalgrade;
					
				}

				array_push($ss, $name);
				array_push($ss, $type);
				array_push($ss, $grade);
				$coursecomplition = $DB->get_record_sql("SELECT completionstate FROM {course_modules_completion} WHERE coursemoduleid  = ".$coursemodule->id." AND userid = ".$uid."");
				if($coursecomplition->completionstate != 0){
					$i++;
				}
			}
			for($j =0 ; $j<($max- $thiscourseactivitycount);$j++){
				array_push($ss, "");
				array_push($ss, "");
				array_push($ss, "");
			}
		}

	 	$percent = $i/count($acivities)*100;
		$per = '<div class="progress">
					  <div class="progress-bar progress-bar-info" role="progressbar" aria-valuenow='.$percent.'
					  aria-valuemin="0" aria-valuemax="100" style="width:'.$percent.'%">
					    '.$percent.'%
					  </div>
				</div>';
		array_push($ss, $per);		
		$table->data[] = $ss;

 	
	}
	  ?>
    
            <form class="form-horizontal" action="<?php echo "functions.php"; ?>" method="post" name="upload_excel"   

                      enctype="multipart/form-data">

                  <div class="form-group">

                            <div>

                             <input type="submit" name="Export" class="btn btn-success" value="export">

                            </div>

                   </div>                    

            </form>    
    <?php
}else{

}

echo html_writer::table($table);

echo $OUTPUT->footer();
?>
<style type="text/css">
	/*table,tr,td,th,thead,th.header{
		border : 1px solid #ccc;
	}*/
tr:nth-child(even) {background-color: #f2f2f2;}
th {
    background-color: #4cafafc4;
    color: white;
}
.progress-bar.progress-bar-info {
    color: #000000b5;
}
</style>