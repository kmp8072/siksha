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

$strheading = "Enrol";//heading of page
$PAGE->set_pagelayout('standard');
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/enrol/dashboard.php'));
$PAGE->set_title($strheading);
$PAGE->navbar->add($strheading);
//$user = $USER->id;

require_login();
echo $OUTPUT->header();
global $SESSION;

class simplehtml_form extends moodleform {
 
    function definition() {
        global $CFG,$USER,$DB;
        $mid = $_GET['mid'];
        $cid = $_GET['cid'];
         $mform = $this->_form; 
         $course_name = $DB->get_record_sql("SELECT fullname FROM {course} WHERE id = ".$cid."");
$manager_name = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id= ".$mid."");
?>
<body>
    <h2>List Of Enrolment Request for <?php echo $course_name->fullname; ?> Course By <?php echo $manager_name->firstname ." ". $manager_name->lastname; ?> </h2>
            
    <?php
         $options = array();
        $sql = $DB->get_records_sql("SELECT * FROM {request_to_enrol} WHERE status = '1' AND manager_id = ".$mid." AND course_id = ".$cid."");
        foreach ($sql as $key => $value) {
            $id = $value->id;
            $user_id = $value->user_id;
            $user_name = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id= ".$user_id."");
            $options[$id]=$user_name->firstname." ".$user_name->lastname;
        }
        $select =  $mform->addElement('select', 'user', get_string('user','local_enrol'),$options);
        $select->setMultiple(true);
        // $mform->addElement('hidden', 'mid', $mid);
        // $mform->addElement('hidden', 'cid', $cid);

      // $mform->addElement('text', 'user', get_string('user','local_enrol'));
        $mform->addElement('button', 'enrol', get_string('enrol','local_enrol'));
        $mform->addElement('button', 'cancel', get_string('cancel','local_enrol'));
        // $this->add_action_buttons($cancel = true, $submitlabel = 'Enrol');
    }
}
$mform = new simplehtml_form();

if($mform->is_cancelled()) {

}else{
    $mform->display();
    echo "<div id = 'success'></div>";
}
    

?>
<script type="text/javascript">
    $(function(){
		
        $('#id_enrol').on('click', function(){
			window.onbeforeunload = null;
            var reqid = $('#id_user').val(); 
            $.ajax({
                url:"save.php",
                data:{request_id:reqid},
                type:"POST",
                success:function(data){
                    // alert(data);
                    //location.reload();
                    $( "#success" ).html(data); 
                    window.setTimeout(function(){location.reload()},3000);

                },error:function(){
                    alert("there some error");
                }

            });
        });
        $('#id_cancel').on('click', function(){
            window.onbeforeunload = null;
            var reqid = $('#id_user').val(); 
            $.ajax({
                url:"cancel.php",
                data:{request_id:reqid},
                type:"POST",
                success:function(data){
                    // alert(data);
                    $( "#success" ).html(data); 
                    window.setTimeout(function(){location.reload()},3000);

                },error:function(){
                    alert("there some error");
                }

            });
        });
       
    })

    //  $('#id_enrol').click(function(){
    //        var enrolid = $('#id_user').val(); 
    //        alert(enrolid);
    // });


</script>
<?php

echo $OUTPUT->footer();