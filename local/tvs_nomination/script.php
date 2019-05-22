<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/moodlelib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/messagelib.php');

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
$cid = $_GET['courseid'];


class simplehtml1_form extends moodleform {
 
    function definition() {
        global $CFG,$USER,$DB;
		$cid = $_GET['courseid'];
		// echo $cid;
        $mform = $this->_form; // Don't forget the underscore! 
        $options = array();
        $selected = array();
        $enrolid = $DB->get_record_sql("SELECT id FROM {enrol} WHERE courseid = ".$cid." AND enrol = 'manual'");

        $select = $DB->get_record_sql("SELECT id FROM {job_assignment} WHERE userid = ?",array($USER->id));
        $uid = $DB->get_records_sql("SELECT * FROM {job_assignment} WHERE managerjaid = ?",array($select->id));
     //  print_r($uid);
        foreach ($uid as $key => $value) {
        	$uid = $value->userid;

            $is_enroled = $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE enrolid = ".$enrolid->id." AND userid = ".$uid."");
            if($is_enroled){

            }else{
                 $is_added = $DB->get_record_sql("SELECT * FROM {request_to_enrol} WHERE course_id = ".$cid." AND user_id = ".$uid."");
                 if($is_added){

                 }else{
            	   $username = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id = ?",array($uid));
            	   $options[$uid] =  $username->firstname." ".$username->lastname;
                }
            }
        }
        $select = $mform->addElement('select', 'users', get_string('user','local_tvs_nomination'),$options);
        $select->setMultiple(true);
        $mform->addElement('button', 'add', get_string("add","local_tvs_nomination"));
        if(empty($cid)){
        }else{
        $query = $DB->get_records_sql("SELECT * FROM {request_to_enrol} WHERE course_id = ".$cid." AND status = '0'");

        foreach ($query as $key => $value) {
            $userid = $value->user_id;
            $id = $value->id;
            $username = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id = ".$value->user_id."");
            $selected[$id] = $username->firstname." ".$username->lastname;  
        }  
        // print_r($selected);
        
        $select = $mform->addElement('select', 'added', get_string('added','local_tvs_nomination'),$selected);
        $select->setMultiple(true);
       
        $mform->addElement('button', 'remove', get_string("remove","local_tvs_nomination"));
       }
        
        $mform->addElement('hidden', 'courseid', $cid);
        $mform->addElement('hidden', 'mname', $USER->username);
        $mform->addElement('hidden', 'memail', $USER->email);
        $mform->addElement('hidden', 'mid', $USER->id);
        // $this->add_action_buttons($cancel = true, $submitlabel = 'Send');
        $mform->addElement('button' ,'Send', get_string("Send","local_tvs_nomination"));
    }
                                 // Close the function
}  
$mform = new simplehtml1_form();

if ($mform->is_cancelled()) {


}else{
    $mform->display();
    $sent = $DB->get_records_sql("SELECT * FROM {request_to_enrol} WHERE course_id = ".$cid." AND status = '1'");
   
        if($sent){
            echo "<h3>Request sent for the following users</h3>";
             $table = new html_table();
            $table->head = array('SNo','Users');
            $i = 1;
            foreach ($sent as $key => $value) {
                $username = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id = ".$value->user_id."");
               $table->data[]= array($i,$username->firstname." ". $username->lastname);
                $i++; 
            }
           echo html_writer::table($table);
        }  
 
}
?>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script type="text/javascript">
    $("#id_Send").css({"background-color": "#3c8dbc", "color": "white"});

    $(function () {
    $('#id_add').on('click', function () {
        window.onbeforeunload = null;
         var enrolid = $('#id_users').val(); 
         var mid = $('input[name=mid]').val();
         //var myJSON = JSON.stringify(userid);
         var courseid = $('input[name=courseid]').val();
       
        $.ajax({
            url: 'add.php',
            data:'eid='+ enrolid+'&cid='+ courseid+'&mid='+mid,
          //  data: {uid:userid, cid:courseid },
            type:"POST",
            success: function(data){
                 // return data;
                 location.reload();
                // alert(data);
            },error:function(){
                alert('error occurs!');
            }
        });
    });
    $('#id_remove').on('click', function () {
        window.onbeforeunload = null;
        var id = $('#id_added').val(); 
        var courseid = $('input[name=courseid]').val();
        $.ajax({
            url: 'delete.php',
            data:'id='+ id+'&cid='+ courseid,
          //  data: {uid:userid, cid:courseid },
            type:"POST",
            success: function(data){
                 // return data;
                 location.reload();
                // alert(data);
            },error:function(){
                alert('error occurs!');
            }
        });
    });
    $('#id_Send').on('click',function(){
        window.onbeforeunload = null;
        var user = $('#id_added').val(); 
        var courseid = $('input[name=courseid]').val();
        // var mid = $('input[name=mid]').val();
        // var memail = $('input[name=memail]').val();
        // var mname = $('input[name=mname]').val();
        // alert("hi");
       // return false;
        window.location.href = "http://139.59.7.236/nucleus/local/tvs_nomination/send.php?courseid="+courseid;
        // $.ajax({"send.php?courseid="+courseid;
        //     url: 'send.php',
        //     cache: false,
        //     data: {cid:courseid},
        //     type:"POST",
        //     // success: function(data){
        //     //     alert(data);
        //     //     location.reload();

        //     //      // return data;
        //     //     // if(data == 1){
        //     //     //      var msg = "Your Request has been sent";
        //     //     //      alert(msg);
        //     //     //      location.reload();
        //     //     // }else{
        //     //     //     var msg ="Your Request has not been sent ";
        //     //     //      alert(msg);
        //     //     //      location.reload();
        //     //     // }
        //     // }
        // });

    })
});
</script>
<?php

echo $OUTPUT->footer();
