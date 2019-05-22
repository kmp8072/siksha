<?php
require_once (dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/formslib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/adminlib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/moodlelib.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/outputcomponents.php');
require_once(dirname(dirname(dirname(__FILE__))) . '/lib/messagelib.php');

  global $CFG,$USER,$DB;
  $reqid = $_POST['request_id'];
  //print_r($reqid);
 
 //  // die();
 // // echo "<tabel><tr>";
 //  // echo "string";
  $time = time();
  for($i = 0; $reqid[$i] != "";$i++){

   $req = $reqid[$i];
  	$sql = $DB->get_record_sql("SELECT * FROM {request_to_enrol} WHERE id = ".$req." ");
  	$userid= $sql->user_id;
  	$cid = $sql->course_id;
    // echo $userid;
    // echo $cid;
  	// $enrolid = $DB->get_record_sql("SELECT * FROM {enrol} WHERE courseid = ".$cid." AND enrol = 'manual' ");
   $enrolid = $DB->get_record_sql("SELECT * FROM {enrol} WHERE courseid=? AND enrol=?",array($cid,'manual'));
   //echo $enrolid->id;
    //echo "SELECT * FROM {enrol} WHERE courseid = ".$cid." AND enrol = 'manual'";
  	$enrol = $DB->get_record_sql("SELECT * FROM {user_enrolments} WHERE userid = ".$userid." AND enrolid = ".$enrolid->id." AND status = 1 ");
 //    echo "SELECT * FROM {user_enrolments} WHERE userid = ".$userid." AND enrolid = ".$enrolid->id." AND status = 1 ";
	// echo $enrol->id;

	  $record = new stdclass();
	  $record->id = $enrol->id;
	  $record->status = '0';
    $record->modifierid = $USER->id;
    $record->timemodified = $time;
    
	  $update = $DB->update_record('user_enrolments',$record);

	  $delete = $DB->execute("DELETE FROM {request_to_enrol} WHERE id = ".$req." ");
	  // //echo $enrol->userid;
    $user_en = $DB->get_record_sql("SELECT * FROM {user}  WHERE id = ".$userid."");
    $course = $DB->get_record_sql("SELECT * FROM {course} WHERE id = ".$cid." ");
    

   //  // $toadmin = "parul@shezartech.in"; 
    $user = new stdClass();
    $user->email     = $user_en->email;
    $user->firstname = $user_en->firstname;
    $user->maildisplay = true;
    $user->mailformat = 1;
    $user->id = $user_en->id;

    $from = new stdClass();
    $from->firstname = $USER->username;
    $from->lastname  = '';
    $from->email     = $USER->email;
    $from->maildisplay = 1;

    $message1 = "You are Enroled in '".$course->fullname."'<br>";
    $message1.="Regards,";
    $email_subject = "Your course enrolment application was confirmed.";
    $emailsubject = $email_subject;
    $message1.= $USER->username;
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
   $message->contexturl = ''.$CFG->wwwroot.'/course/view.php?id='.$cid.'';
   $message->contexturlname = 'Context name';
    $message->replyto = $from;
    $content = array('*' => array('header' => ' Dear,', 'footer' => $USER->username)); // Extra content for specific processor
    $message->set_additional_content('email', $content);
     
     
    $messageid = message_send($message);
    echo "<div style ='background-color:rgba(19, 222, 19, 0.58); padding : 10px;'>".$user_en->username. "     Enroled</div>";
  }
  
  //echo "</tr></table>";
