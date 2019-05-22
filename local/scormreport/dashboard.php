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



$strheading = "Report";

$PAGE->set_pagelayout('standard');

$PAGE->set_context(context_system::instance());

$PAGE->set_url(new moodle_url('/local/scormreport/dashboard.php'));

$PAGE->set_title($strheading);

$PAGE->navbar->add($strheading);





require_login();







echo $OUTPUT->header();

global $SESSION;

?>

<h2>Scorm Report</h2>





<?php







class simplehtml_form extends moodleform {

    //Add elements to form

	

    public function definition() {

        global $CFG,$DB;

		

        $mform = $this->_form; // Don't forget the underscore! 

		

        $all_courses=$DB->get_records_sql('SELECT * FROM {course} where id != ?',array('1'));

		foreach($all_courses as $fields=>$all_course){

		

		$courses['select'] = 'Select';

		$courses[$all_course->id] = $all_course->fullname;

		

		

		}

		

	

			$select = $mform->addElement('select', 'course', get_string('abc','local_scormreport'),$courses);

			$select->setSelected($courses);

			

			

			$select = $mform->addElement('select', 'scorm_name', get_string('sc_name','local_scormreport'));

			

			$mform->addElement('hidden', 'currentscormname');

			$mform->setType('currentscormname', PARAM_NOTAGS);



 $submitlabel = get_string('showreport', 'local_scormreport');

            $this->add_action_buttons(true, $submitlabel);



		}

 

}





$mform = new simplehtml_form();

if ($mform->is_cancelled()) {

  $urltogo= new moodle_url('/local/scormreport/dashboard.php');

  redirect($urltogo);

} else if ($fromform = $mform->get_data()) {



if(!empty($fromform)){

$courseid = $fromform->course;

$scormname = $fromform->currentscormname;



$urltogo= new moodle_url('/local/scormreport/dashboard_new.php',array('courseid' => $courseid,'scormname' => $scormname));

  

  

}

	

redirect($urltogo);

	

	

 

} else {

 

  //$mform->set_data($toform);

  $mform->display();

}







echo $OUTPUT->footer();





?>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>

<script>

var jq = $.noConflict();

jq(document).ready(function(){

jq("#id_scorm_name").hide();

jq('label[for="id_scorm_name"]').hide();

jq('#id_course').on('change', function() {

  var selectedcourseid = this.value;

  jq.ajax({

  		url : "ajax.php",

        type : "POST",

        data: {selectedcourseid:selectedcourseid},

        success : function(data) {

		

		var result = jq.parseJSON(data);

        var options = '';

		options += '<option value="' + 'select'+ '">' + 'Select' + '</option>';

        for (var i = 0; i < result.length; i++) {

		options += '<option value="' + result[i]+ '" >' + result[i] + '</option>';

        }

		jq("#id_scorm_name").html(options);	

		jq("#id_scorm_name").show();

		jq('label[for="id_scorm_name"]').show();

		}

    });	



})

jq('#id_scorm_name').on('change', function() {

  var selectedscormname = this.value;

   jq('input[name="currentscormname"]').val(selectedscormname);

  

  });



});

</script>

