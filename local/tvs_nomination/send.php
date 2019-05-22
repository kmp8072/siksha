<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/moodlelib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/messagelib.php');

global $CFG,$USER,$DB;

$cid = $_GET['courseid'];
$query = $DB->get_records_sql("SELECT * FROM {request_to_enrol} WHERE course_id = ".$cid." AND manager_id = ".$USER->id."");


$enrolid = $DB->get_record_sql("SELECT id FROM {enrol} WHERE enrol = 'manual' AND courseid = ".$cid."");


 $toadmin = "sandeep@shezartech.com"; 
 $user = new stdClass();
$user->email     = $toadmin;
$user->firstname = 'admin';
$user->maildisplay = true;
$user->mailformat = 1;
$user->id = 2;



$from = new stdClass();
$from->firstname = $USER->firstname." ".$USER->lastname ;
$from->lastname  = '';
$from->email     = $USER->email;
$from->maildisplay = 1;
$time = time();



$message1= "Requst for enrolment of given users <br><ul>";
foreach ($query as $selectedOption) {
	$status = $selectedOption->status;
	if($status == 0){
	    $sql = $DB->get_record_sql("SELECT username,firstname,lastname FROM {user} WHERE id = ?",array($selectedOption->user_id));
	    $message1.="<li>";
	    $message1.= $sql->firstname." ".$sql->lastname;
	    $message1.="</li>";

	    $record = new stdclass();
		$record->id = $selectedOption->id;
		$record->status = 1;
		$update = $DB->update_record('request_to_enrol',$record);

		if(!empty($update)){

			$user_enrol = new stdclass();
			$user_enrol->status = 1;
			$user_enrol->enrolid = $enrolid->id;
			$user_enrol->userid = $selectedOption->user_id;
			$user_enrol->modifierid = $USER->id;
			$user_enrol->timecreated = $time;
			$user_enrol->timemodified = $time;
			$insert = $DB->insert_record('user_enrolments', $user_enrol);
		}
	}
	
}

$course = $DB->get_record_sql("SELECT fullname FROM {course} WHERE id = ?",array($cid));
$message1.="</ul>for ".$course->fullname." Course.<br><br>";
$message1.="Regards,";
$email_subject = "Approval for enrolment in ".$course->fullname." course";
$emailsubject = $email_subject;
$emailmessage = $message1;
   

    $message = new \core\message\message();
    $message->component = 'moodle';
    $message->name = 'instantmessage';
    $message->userfrom = $USER;
    $message->userto = $user;
    $message->subject = $emailsubject;
    $message->fullmessage = $emailmessage;
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml = '<p>'.$emailmessage.'</p>';
    $message->smallmessage = $emailsubject;
    $message->notification = '0';
   
    $message->replyto = $from;
    $content = array('*' => array('header' => ' Dear Admin, ', 'footer' => $USER->firstname)); // Extra content for specific processor
    $message->set_additional_content('email', $content);
     
     
    $messageid = message_send($message);
print_R($insert);
if(!empty($insert)){
	echo '1';
}else{
	echo '0';
} 
$message = "Request Sent";
echo "<script type='text/javascript'>alert('$message');</script>";   
$url = new moodle_url("/local/tvs_nomination/script.php?courseid=".$cid."");
// sleep(1000);
redirect($url);