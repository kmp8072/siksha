<?php

// This file is part of Moodle - http://moodle.org/

//

// Moodle is free software: you can redistribute it and/or modify

// it under the terms of the GNU General Public License as published by

// the Free Software Foundation, either version 3 of the License, or

// (at your option) any later version.

//

// Moodle is distributed in the hope that it will be useful,

// but WITHOUT ANY WARRANTY; without even the implied warranty of

// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the

// GNU General Public License for more details.

//

// You should have received a copy of the GNU General Public License

// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.



/**

 * Register Execution Smart Klass Server Connection

 *

 * @package    local_virtual_class

 * @copyright  KlassData <kttp://www.klassdata.com>

 * @author     Oscar Ruesga <oscar@klassdata.com>

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

 */

//error_reporting(0);

require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');

// require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');

// require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');

$strheading = "Course LeaderBoard";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/course_leaderboard/dashboard.php'));
$PAGE->set_title($strheading);
$PAGE->navbar->add($strheading);
//$user = $USER->id;
?>
<html>
<head>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

</head>

<?php
require_login();
echo $OUTPUT->header();
// global $SESSION;
// global $USER, $DB;
?>
<body>
  <h2>Course LeaderBoard</h2>
</body>
<?php
class LeaderBoard extends moodleform {
    //Add elements to form
    public function definition() {
     
    	global $USER, $DB;

		$mform = $this->_form; 
		$options[] = "Please Select a Course";
		$courses = $DB->get_records_sql("SELECT * FROM {course}");
		foreach ($courses as $key => $value) {
			$id = $value->id;
			$cname = $value->fullname;
			$options[$id] = $cname;		
		}
        // print_r($options);
        $mform->addElement('select', 'courses', get_string('courses', 'local_course_leaderboard'),$options);
         $this->add_action_buttons($cancel = false, $submitlabel = 'Submit');
    }
    
}

$mform = new LeaderBoard();

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
	$courseid = $fromform->courses;
	$mform->display();
  // echo $courseid;
   $details = $DB->get_records_sql("SELECT  gg.userid, gg.finalgrade,u.firstname, u.lastname FROM {grade_grades} gg JOIN {grade_items} gi ON gi.id = gg.itemid JOIN {user} u ON u.id = gg.userid WHERE gi.courseid = ? AND gi.itemtype = ? AND gg.finalgrade <> ? ORDER BY gg.finalgrade DESC LIMIT 10",array($courseid,'course',' '));
   // print_r($details);
 
  if(!empty($details)){
    $table = new html_table();
    $table->head = array('Rank', 'Fullname', 'Score');
    $i=1;
    foreach ($details as $key => $value) {
      $fullname = $value->firstname ." ". $value->lastname ;
      $score = $value->finalgrade;
      $table->data[] = array($i,$fullname,round($score));
      $i++;
      
    }
    
  echo html_writer::table($table);
  }
  //In this case you process validated data. $mform->get_data() returns data posted in form.

} else {
  // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
  // or on the first display of the form.
 
  //Set default data (if any)
 // $mform->set_data($toform);
  //displays the form
  $mform->display();
}


echo $OUTPUT->footer();
?>