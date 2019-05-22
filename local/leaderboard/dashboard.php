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

$strheading = "LeaderBoard";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/leaderboard/dashboard.php'));
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
  <h2>LeaderBoard</h2>
</body> 
<?php

class LeaderBoard extends moodleform {
    //Add elements to form
    public function definition() {
     
    	global $USER, $DB;

		$mform = $this->_form; 
		$options[] = "Please Select a Program";
    if(is_siteadmin()){
  		$program = $DB->get_records_sql("SELECT * FROM {prog}");
  		foreach ($program as $key => $value) {
  			$id = $value->id;
  			$pname = $value->fullname;
  			$options[$id] = $pname;		
  		}
    }else{
      $program = $DB->get_records_sql("SELECT p.id,p.fullname FROM {prog_assignment} pa JOIN {prog} p ON p.id = pa.programid WHERE pa.assignmenttypeid = ".$USER->id." ");
      foreach ($program as $key => $value) {
        $id = $value->id;
        $pname = $value->fullname;
        $options[$id] = $pname;   
      }
    }
        // print_r($options);
        $mform->addElement('select', 'program', get_string('program', 'local_create_leaderboard'),$options);
         $this->add_action_buttons($cancel = false, $submitlabel = 'Submit');
    }
    
}

$mform = new LeaderBoard();

if ($mform->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $mform->get_data()) {
	$programid = $fromform->program;
   $mform->display();
	//echo $programid;
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