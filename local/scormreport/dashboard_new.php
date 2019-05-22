<?php



require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');





require_login();



$strheading = "Report";

$page = optional_param('page', 0, PARAM_INT);

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/scormreport/dashboard_new.php'));

$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);

echo $OUTPUT->header();

global $SESSION;





$usercourseid = $_GET['courseid'];

$scormname = $_GET['scormname'];

$scormid = $DB->get_record_sql('SELECT id FROM {scorm} where name = ? ',array($scormname));			

?>

<h2>Scorm Report</h2>









 <div>

            <form class="form-horizontal" action="functions.php" method="post" name="upload_excel"   

                      enctype="multipart/form-data">

                  <div class="form-group">

                            <div class="col-md-4 col-md-offset-4">

							  <input type="hidden" name="usercourseid" class="btn btn-success" value="<?php echo $usercourseid; ?>">

							  <input type="hidden" name="coursescormname" class="btn btn-success" value="<?php echo $scormname; ?>">

                                <input type="submit" name="Export" class="btn btn-success" value="export to excel">

                            </div>

                   </div>                    

            </form>           

 </div>

 <?php



$params = array('page' => $page);



$baseurl = new moodle_url('/local/scormreport/dashboard_new.php?courseid='.$usercourseid.'&scormname='.$scormname.'', $params);



$fields = 'SELECT  {scorm_scoes_track}.id,{scorm_scoes_track}.attempt,{scorm_scoes_track}.userid,{scorm_scoes_track}.value,{user}.firstname,{user}.lastname,{user}.username';

    $countfields = 'SELECT COUNT(*)';

    $sql = ' FROM {scorm}   



INNER JOIN {scorm_scoes}  ON {scorm_scoes}.scorm = {scorm}.id  

INNER JOIN {scorm_scoes_track}  ON {scorm_scoes_track}.scoid = {scorm_scoes}.id AND {scorm_scoes_track}.scormid = {scorm_scoes}.scorm



INNER JOIN {user}   ON {user}.id = {scorm_scoes_track}.userid

             ';

    $params = array();

    $wheresql = 'WHERE {scorm}.course = '.$usercourseid.'    AND name = "'.$scormname.'" AND sortorder = 2 AND element = "cmi.core.score.raw"';

	

	

	

 $allscormrecords = $DB->count_records_sql($countfields . $sql . $wheresql, $params);



 $scormdatas = $DB->get_records_sql($fields . $sql . $wheresql . '', $params, $page*20, 20);	



 $table = new html_table();

		$table->head = array ('Firstname','Lastname','Phone No.','Attempt','Score','Cumulative Score');

		foreach($scormdatas as $scormdata){
		

		$firstname = $scormdata->firstname;

		$lastname = $scormdata->lastname;

		$username = $scormdata->username;

		$attempt = $scormdata->attempt;

		$score = $scormdata->value;

		if($attempt == '1'){

		 $cumulativescore = $score;

		}elseif($attempt == '2'){

			$cumulativescore = ($score - ($score*(10/100)));

		}elseif($attempt == '3'){

			$cumulativescore = ($score - ($score*(20/100)));

		}elseif($attempt == '4'){

			$cumulativescore = ($score - ($score*(30/100)));

		}elseif($attempt == '5'){

			$cumulativescore = ($score - ($score*(40/100)));

		}elseif($attempt >= '6'){

			$cumulativescore = ($score - ($score*(50/100)));

		}
		
		
		/*$usergamescores = $DB->get_records_sql('SELECT * FROM {user_gamescore} where userid = ? AND scormid = ?',array($scormdata->userid,$scormid->id)); 

		if(!empty($usergamescores)){

		foreach($usergamescores as $usergamescore){

		$gamescore = $usergamescore->gamescore;

		}

		}else{

		$gamescore = 'Game not start';

		}*/

		

		$table->data[] = array($firstname,$lastname,$username,$attempt,$score,$cumulativescore);

		

}

	

		

		

		echo html_writer::table($table);

		echo $OUTPUT->paging_bar($allscormrecords, $page, 20, $baseurl);

	

echo $OUTPUT->footer();	



?>