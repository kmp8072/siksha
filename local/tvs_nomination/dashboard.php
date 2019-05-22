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

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');

require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');

$strheading = "tvs nomination";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/tvs_nomination/dashboard.php'));
$PAGE->set_title($strheading);
$PAGE->navbar->add($strheading);
//$user = $USER->id;
?>
<html>
<head>
<link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
</head>

<?php
require_login();
echo $OUTPUT->header();
global $SESSION;


class simplehtml_form extends moodleform {
 
    function definition() {
        global $CFG,$USER,$DB;
 		// if(is_siteadmin()){
 		// 	echo "admin";
 		// }else{
 		// 	echo"user";
 		// }
        $mform = $this->_form; // Don't forget the underscore! 
        
        $options[] = "Please Select a Course";
        $sql = $DB->get_records_sql('SELECT * FROM {course} WHERE id <> 1');
        foreach ($sql as $key => $value) {
					 $name = $value->fullname;
					 $id = $value->id;
					$options[$id] = $name;
		}
        // print_r($options);

        $mform->addElement('select', 'course', get_string('courses', 'local_tvs_nomination'), $options);
         // $mform->addElement('button', 'assign', get_string('assign', 'local_tvs_nomination'));
        $this->add_action_buttons($cancel = false, $submitlabel = 'Assign');
      }                           // Close the function
}  

$mform = new simplehtml_form();


if ($mform->is_cancelled()) {
	$urltogo= new moodle_url('/local/tvs_nomination/dashboard.php');

  redirect($urltogo);

}else if ($fromform = $mform->get_data()) {
	$courseid = $fromform->course;
	
	$urltogo= new moodle_url('/local/tvs_nomination/script.php',array('courseid' => $courseid));

  redirect($urltogo);


}else{
   $mform->display();
 
}

echo $OUTPUT->footer();

?>
<!-- <script type="text/javascript">

    $("#id_assign").click(function(){
    	var cid = $('#id_course').find(":selected").val();
    	alert(cid);
	    $.ajax({
	        type: 'POST',
	        url: 'script.php',
	        data: {cid1:cid},
	        success: function(data) {
	          //  alert(data);
	           // $('#assign').html(data);

	        }
	    });
   });

</script> -->